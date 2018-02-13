<?php

namespace Fridde;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles as CssTIS;
use Hampe\Inky\Inky;

/**
 * Class HTML
 * @package Fridde
 */
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
    /* @var IncludableReader $IncludableReader  */
    private $IncludableReader;

    public const INC_ABBREVIATION = 0;
    public const INC_ADDRESS = 1;
    public const INC_LITERAL = 2;


    /**
     * HTML constructor.
     * @param string|null $template_dir
     * @throws \Exception
     */
    public function __construct(string $template_dir = null, array $extensions = [])
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
        $extensions[] = new \Twig_Extension_Debug();
        foreach ($extensions as $extension) {
            $this->TWIG->addExtension($extension);
        }
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

    public function addDefaultFonts()
    {
        $array = SETTINGS['defaults']['fonts'];
        array_walk(
            $array,
            function (&$v, $k) {
                array_unshift($v, $k);
            }
        );
        return $this->addGoogleFonts($array);
    }

    /**
     * @param $type
     * @param $key
     * @return HTML
     * @throws \Exception
     */
    private function addDefaultJsOrCss($file_type, $key)
    {
        $array = SETTINGS['defaults'][$file_type][$key] ?? false;
        if ($array === false) {
            throw new \Exception('The abbreviation <'.$key.'> could not be resolved');
        }
        $remote_abbreviations = $array['remote'] ?? [];
        $this->addJsOrCss($file_type, $remote_abbreviations, self::INC_ABBREVIATION);

        $local_file_names = $array['local'] ?? [];
        array_walk($local_file_names, function(&$v) use ($file_type){
            $v = $file_type . '/' . $v . '.' . $file_type;
        });
        return $this->addJsOrCss($file_type, $local_file_names, self::INC_ADDRESS);
    }

    /**
     * @param string|array $js
     * @param int $type
     * @return HTML
     */
    public function addJS($js, int $type = self::INC_ABBREVIATION)
    {
        $js = (array) $js;

        return $this->addJsOrCss("js", $js, $type);
    }

    /**
     * @param string|array $css
     * @param string $type
     * @return HTML
     */
    public function addCss($css, int $type = self::INC_ABBREVIATION)
    {
        $css = (array)$css;

        return $this->addJsOrCss("css", $css, $type);
    }

    /**
     * @param string $cssOrJs Either "css" or "js"
     * @param array $array
     * @param int $type
     * @return $this
     */
    private function addJsOrCss(string $cssOrJs, array $array, int $type)
    {

        $type_translator = [
            self::INC_ADDRESS => ['css' => 'CssFiles', 'js' => 'JsFiles'],
            self::INC_LITERAL => ['css' => 'LiteralCss', 'js' => 'LiteralJs']
        ];

        $array = (array)$array;

        foreach ($array as $element) {
            $type_index = $type;
            if ($type === self::INC_ABBREVIATION) {
                $this->IncludableReader = $this->IncludableReader ?? new IncludableReader();
                $element = $this->IncludableReader->getPathFor($element, $cssOrJs);
                $type_index = self::INC_ADDRESS;
            }

            $this->VAR[$type_translator[$type_index][$cssOrJs]][] = $element;
        }

        return $this;
    }

    /**
     * @param $file_name
     */
    public function addCssFile(string $file_name)
    {
        $path = BASE_DIR."css\\".$file_name . '.css';
        $this->addCss($path, self::INC_ADDRESS);
    }


    public function addGoogleFonts(array $fonts)
    {
        $base_path = 'https://fonts.googleapis.com/css?family=';

        array_walk_recursive($fonts, 'trim');
        $font_path_array = [];
        foreach ($fonts as $font_and_styles) {
            $font_string = str_replace([' ', '_'], '+', array_shift($font_and_styles));
            $styles = $font_and_styles;
            if (!empty($font_and_styles)) {
                $font_string .= ':'.implode(',', $font_and_styles);
            }
            $font_path_array[] = $font_string;
        }
        $complete_path = $base_path.implode('|', $font_path_array);
        return $this->addCss($complete_path, self::INC_ADDRESS);
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
    public function render(bool $echo = true, $encode_entities = false)
    {
        if (empty($this->DOC)) {
            $this->finalCompilation();
        }
        if ($encode_entities) {
            $this->DOC = htmlentities($this->DOC, ENT_COMPAT | ENT_XHTML);
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
        $this->Title = $title ?? '';

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
