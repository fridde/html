<?php

namespace Fridde;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles as CssTIS;
use Hampe\Inky\Inky;

class HTML
{
    /** @var */
    private $DOC; //the final document
    /* @var */
    private $TWIG;
    /* @var array $VAR The array of variables passed to TWIG->render() that can be used in the template */
    public $VAR;
    /* @var string $Template The name of the template to use */
    private $Template;
    /* @var string $Title The title of the html-doc to be shown in the browser */
    public $Title;
    /* @var array $includables An array to be constructed from an ini-file of includable sources */
    private $includables;
    /* @var string $includables_file_name The path or filename seen from the root directory of this package to the includables */
    public $includables_file_name = "includables.ini";

    /* @var array INCLUDE_TYPES */
    const INCLUDE_TYPES = [
        "abbreviation" => 0,
        "adress" => 1,
        "literal" => 2,
    ];

    /**
     * HTML constructor.
     * @param string|null $template_dir
     * @throws \Exception
     */
    function __construct(string $template_dir = null)
    {
        if (empty($template_dir)) {
            if (!defined('BASE_DIR')) {
                throw new \Exception("No template directory given.");
            }
            $template_dir[] = BASE_DIR."/templates";
            $template_dir[] = BASE_DIR."/templates/mail";
        }
        $loader = new \Twig_Loader_Filesystem($template_dir);
        $this->TWIG = new \Twig_Environment($loader, ["debug" => true]);
        $extension = new TwigExtension($this->TWIG, ["A" => $this]);
        $this->TWIG = $extension->addAll();
        $this->TWIG->addExtension(new \Twig_Extension_Debug());
    }

    /**
     * @param string $key
     * @param mixed|null $variable
     * @return $this
     */
    public function addVariable(string $key, $variable = null)
    {
        $this->VAR[$key] = $variable;

        return $this;
    }


    /**
     * @param string $abbreviation
     * @return HTML
     */
    public function addDefaultJs($abbreviation = "index")
    {
        return $this->addDefaultJsOrCss("js", $abbreviation);
    }

    /**
     * @param string $abbreviation
     * @return HTML
     */
    public function addDefaultCss($abbreviation = "index")
    {
        return $this->addDefaultJsOrCss("css", $abbreviation);
    }

    /**
     * @param $type
     * @param $key
     * @return HTML
     * @throws \Exception
     */
    private function addDefaultJsOrCss($type, $key)
    {
        $array = SETTINGS["defaults"][$type][$key] ?? false;
        if ($array === false) {
            throw new \Exception("The abbreviation <".$key."> could not be resolved");
        }

        return $this->addJsOrCss($type, $array, "abbreviation");
    }

    /**
     * @param string|array $js
     * @param string $type
     * @return HTML
     */
    public function addJS($js, $type = "abbreviation")
    {
        $js = (array) $js;
        return $this->addJsOrCss("js", $js, $type);
    }

    /**
     * @param string|array $css
     * @param string $type
     * @return HTML
     */
    public function addCss($css, $type = "abbreviation")
    {
        $css = (array) $css;

        return $this->addJsOrCss("css", $css, $type);
    }

    /**
     * @param string $cssOrJs Either "css" or "js"
     * @param array $array
     * @param string|int $type
     * @return $this
     */
    private function addJsOrCss(string $cssOrJs, array $array, $type)
    {

        if (!is_integer($type)) {
            $type = self::INCLUDE_TYPES[$type];
        }
        $array = (array)$array;

        foreach ($array as $element) {
            if ($type == 0) { //abbreviation
                $element = $this->getIncludableAddress($element);
            }
            if ($type <= 1 && $cssOrJs == "css") {
                $this->VAR["CssFiles"][] = $element;
            } elseif ($type <= 1 && $cssOrJs == "js") {
                $this->VAR["JsFiles"][] = $element;
            } elseif ($type == 2 && $cssOrJs == "css") {
                $this->VAR["LiteralCss"][] = $element;
            } elseif ($type == 2 && $cssOrJs == "js") {
                $this->VAR["LiteralJs"][] = $element;
            }
        }

        return $this;
    }

    /**
     * @param $file_name
     */
    public function addCssFile(string $file_name)
    {
        $path = BASE_DIR."css\\".$file_name;
        $this->addCss($path, "adress");
    }

    /**
     * @param $abbreviation
     * @return mixed
     * @throws \Exception
     */
    private function getIncludableAddress(string $abbreviation)
    {
        if (empty($this->includables)) {
            $this->setIncludablesFromFile();
        }
        $categories = array_filter(
            $this->includables,
            function ($cat) use ($abbreviation) {
                return !empty($cat[$abbreviation]);
            }
        );
        $key = key($categories);
        $values = array_shift($categories);

        if (count($categories) > 0) {
            throw new \Exception("The abbreviation $abbreviation was not unique. Check the includable file.");
        } elseif (empty($values)) {
            throw new \Exception("The abbreviation $abbreviation could not be found. Check the includable file.");
        }

        return $values[$abbreviation];
    }

    /**
     * @param string|null $file_name
     * @throws \Exception
     */
    private function setIncludablesFromFile(string $file_name = null)
    {
        $file_name = $file_name ?? $this->includables_file_name;
        $path = dirname(__FILE__, 2). DIRECTORY_SEPARATOR .$file_name;

        if (is_readable($path)) {
            $this->includables = parse_ini_file($path, true);
        } else {
            throw new \Exception("No file for includables found.");
        }
    }

    /**
     * @return $this
     */
    public function addInlineCss()
    {
        $css = "";
        foreach ($this->VAR["CssFiles"] as $path) {
            $css .= file_get_contents($path);
        }
        $cssTIS = new CssTIS();
        if (empty($this->DOC)) {
            $this->finalCompilation();
        }
        $this->DOC = $cssTIS->convert($this->DOC, $css);

        return $this;
    }

    /**
     * @param array|string|null $input
     * @return $this
     * @throws \Exception
     */
    public function addNav($role = null)
    {

        $nav = new Navigation();
        $role = $role ?? $nav->getUserRole();
        $nav_items = $nav->getMenuForRole($role);

        $this->addVariable("nav_items", $nav_items);

        return $this;

    }

    /**
     * @return $this
     */
    public function inkify()
    {
        if (empty($this->DOC)) {
            $this->finalCompilation();
        }
        $inky = new Inky();
        $this->DOC = $inky->releaseTheKraken($this->DOC);

        return $this;
    }

    /**
     * @return $this
     */
    private function finalCompilation()
    {
        $this->addVariable("TITLE", $this->Title);
        $this->DOC = $this->TWIG->render($this->Template, $this->VAR);

        return $this;
    }

    /**
     * @param bool $echo If set to true, echos the document, too
     * @return string The final html document after all compilations
     */
    public function render(bool $echo = true)
    {
        if (empty($this->DOC)) {
            $this->finalCompilation();
        }
        if ($echo) {
            echo $this->DOC;
        }

        return $this->DOC;
    }


    /**
     * @param $template
     * @return $this
     */
    public function setTemplate($template)
    {
        $template = substr($template, -5) == '.twig' ?: $template.'.twig';
        $this->Template = $template;

        return $this;
    }

    /**
     * @param null $base
     * @return $this
     */
    public function setBase($base = null)
    {
        $base = $base ?? APP_URL;
        $this->VAR["base"] = "//".$base;

        return $this;
    }


    /**
     * @param null $title
     * @return $this
     */
    public function setTitle($title = null)
    {
        $this->Title = $title ?? (SETTINGS["defaults"]["title"] ?? "");

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->Title;
    }

    /**
     * @return mixed
     */
    public function getCssFiles()
    {
        return $this->CssFiles;
    }

    /**
     * @return mixed
     */
    public function getLiteralCss()
    {
        return $this->LiteralCss;
    }

    /**
     * @return mixed
     */
    public function getVAR()
    {
        return $this->VAR;
    }

    /**
     * @param array $array
     * @param int $columns
     * @param bool $horizontal
     * @return array
     */
    public static function partition(array $array, int $columns = 2, bool $horizontal = true)
    {
        if ($horizontal) {
            $partition = [];
            $i = 0;
            foreach ($array as $key => $value) {
                $partition[$i % $columns][$key] = $value;
                $i++;
            }
        } else {
            $partition = array_chunk($array, ceil(count($array) / $columns), true);
        }

        return $partition;
    }
}
