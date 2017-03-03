<?php

namespace Fridde;

use Fridde\TwigExtension;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles as CssTIS;
use Hampe\Inky\Inky;

class HTML
{
    private $SETTINGS;
    private $DOC; //the final document
    private $TWIG;
    public $VAR;
    private $Template;
    public $Title;
    private $includables;
    public $includables_file_name = "includables.ini";

    const INCLUDE_TYPES =  ["abbreviation" => 0, "adress" => 1, "literal" => 2];


    function __construct ($template_dir = null)
    {
        if(empty($template_dir)){
            if(!defined('BASE_DIR')){
                throw new \Exception("No template directory given.");
            }
            $template_dir[] = BASE_DIR . "/templates";
            $template_dir[] = BASE_DIR . "/templates/mail";
        }
        $loader = new \Twig_Loader_Filesystem($template_dir);
        $this->TWIG = new \Twig_Environment($loader, ["debug" => true]);
        $expander = new TwigExtension($this->TWIG, ["A" => $this]);
        $this->TWIG = $expander->addAll();
        $this->TWIG->addExtension(new \Twig_Extension_Debug());
        $this->SETTINGS = defined('SETTINGS') ? SETTINGS : null;
    }

    public function addVariable($key, $variable = null)
    {
        $this->VAR[$key] = $variable;
        return $this;
    }

    public function addDefaultJs($abbreviation = "index")
    {
        return $this->addDefaultJsOrCss("js", $abbreviation);
    }

    public function addDefaultCss($abbreviation = "index")
    {
        return $this->addDefaultJsOrCss("css", $abbreviation);
    }

    private function addDefaultJsOrCss($type, $key)
    {
        $array = SETTINGS["defaults"][$type][$key] ?? false;
        if($array === false){
            throw new Exception("The abbreviation " . $key . " could not be resolved");
        }
        return $this->addJsOrCss($type, $array, "abbreviation");
    }

    public function addJS($js = [], $type = "abbreviation")
    {
        return $this->addJsOrCss("js", $js, $type);
    }

    public function addCss($css = [], $type = "abbreviation")
    {
        $css = $css ?? [];
        return $this->addJsOrCss("css", $css, $type);
    }

    private function addJsOrCss($cssOrJs = "css", $array, $type)
    {

        if(!is_integer($type)){
            $type = self::INCLUDE_TYPES[$type];
        }
        $array = (array) $array;

        foreach($array as $element){
            if($type == 0){ //abbreviation
                $element = $this->getIncludableAddress($element);
            }
            if($type <= 1 && $cssOrJs == "css"){
                $this->VAR["CssFiles"][] = $element;
            } elseif($type <= 1 && $cssOrJs == "js"){
                $this->VAR["JsFiles"][] = $element;
            } elseif($type == 2 && $cssOrJs == "css"){
                $this->VAR["LiteralCss"][] = $element;
            } elseif($type == 2 && $cssOrJs == "js"){
                $this->VAR["LiteralJs"][] = $element;
            }
        }
        return $this;
    }

    public function addCssFile($file_name)
    {
        $path = BASE_DIR . "css\\" . $file_name;
        $this->addCss($path, "adress");
    }

    private function getIncludableAddress($abbreviation)
    {
        if(empty($this->includables)){
            $this->setIncludablesFromFile();
        }
        $categories = array_filter($this->includables, function($cat) use ($abbreviation){
            return !empty($cat[$abbreviation]);
        });
        $key = key($categories);
        $values = array_shift($categories);

        if(count($categories) > 0){
            throw new \Exception("The abbreviation $abbreviation was not unique. Check the includable file.");
        } elseif(empty($values)) {
            throw new \Exception("The abbreviation $abbreviation could not be found. Check the includable file.");
        }
        return $values[$abbreviation];
    }

    private function setIncludablesFromFile($file_name = null)
    {
        $file_name = $file_name ?? $this->includables_file_name;
        $path = dirname( __FILE__ , 2) . '\\' . $file_name;

        if(is_readable($path)){
            $this->includables = parse_ini_file($path, true);
        } else {
            throw new \Exception("No file for includables found.");
        }
    }

    public function addInlineCss()
    {
        $css = "";
        foreach($this->VAR["CssFiles"] as $path){
            $css .= file_get_contents($path);
        }
        $cssTIS = new CssTIS();
        if(empty($this->DOC)){
            $this->finalCompilation();
        }
        $this->DOC = $cssTIS->convert($this->DOC, $css);
        return $this;
    }

    public function inkify()
    {
        if(empty($this->DOC)){
            $this->finalCompilation();
        }
        $inky = new Inky();
        $this->DOC = $inky->releaseTheKraken($this->DOC);
        return $this;
    }

    private function finalCompilation()
    {
        $this->addVariable("TITLE", $this->Title);
        $this->DOC = $this->TWIG->render($this->Template, $this->VAR);
        return $this;
    }

    public function render($echo = true)
    {
        if(empty($this->DOC)){
            $this->finalCompilation();
        }
        if($echo){
            echo $this->DOC;
        }
        return $this->DOC;
    }


    public function setTemplate($template)
    {
        $template = substr($template, -5) == '.twig' ?: $template . '.twig';
        $this->Template = $template;
        return $this;
    }

    public function setBase($base = null)
    {
        $base = $base ?? APP_URL;
        $this->VAR["base"] = "//" . $base;
        return $this;
    }


    public function setTitle($title = null)
    {
        $this->Title = $title ?? (SETTINGS["defaults"]["title"] ?? "");
        return $this;
    }

    public function getTitle()
    {
        return $this->Title;
    }

    public function getCssFiles()
    {
        return $this->CssFiles;
    }

    public function getLiteralCss()
    {
        return $this->LiteralCss;
    }

    public function getVAR()
    {
        return $this->VAR;
    }

    public static function partition($array, $columns = 2, $horizontal = true)
    {
        if($horizontal){
            $partition = [];
            $i = 0;
            foreach($array as $key => $value){
                $partition[$i % $columns][$key] = $value;
                $i++;
            }
        }
        else {
            $partition = array_chunk($array, ceil(count($array)/$columns), true);
        }
        return $partition;
    }
}
