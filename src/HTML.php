<?php

namespace Fridde;

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
    /* @var IncludableReader $IncludableReader */
    private $IncludableReader;

    public const BASE_ASSET_PATH = 'assets/compiled/';

    public const INC_ABBREVIATION = 0;
    public const INC_ADDRESS = 1;
    public const INC_LITERAL = 2;


    public function __construct(array $template_dir = null, array $extensions = [], string $cache_path = null)
    {
        if (empty($template_dir) && !defined('BASE_DIR')) {
           throw new \Exception('Template directory could not be determined.');
        }
        if(empty($template_dir)){
            $template_dir[] = BASE_DIR.'/templates';
        }

        $loader = new \Twig_Loader_Filesystem($template_dir);

        $env_options = ['debug' => self::isDebug()];
        if(!empty($cache_path)){
            $env_options['cache'] = $cache_path;
        }
        $this->TWIG = new \Twig_Environment($loader, $env_options);
        if (self::isDebug()) {
            $extensions[] = new \Twig_Extension_Debug();
        }
        foreach ($extensions as $extension) {
            $this->TWIG->addExtension($extension);
        }
    }

    /**
     * @param string $key
     * @param mixed|null $variable
     * @return $this
     */
    public function addVariable(string $key, $variable = null): self
    {
        $this->VAR[$key] = $variable;

        return $this;
    }


    /**
     * @return HTML
     */
    public function addDefaultJs(): HTML
    {
        return $this->addDefaultJsOrCss('js');
    }

    /**
     * @return HTML
     */
    public function addDefaultCss(): HTML
    {
        return $this->addDefaultJsOrCss('css');
    }

    public function addDefaultFonts(): HTML
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

    private function addDefaultJsOrCss(string $file_type): HTML
    {
        $array = SETTINGS['defaults'][$file_type] ?? false;

        $remote_abbreviations = $array['remote'] ?? [];

        $this->addJsOrCss($file_type, $remote_abbreviations);

        $local_file_names = $array['local'] ?? [];
        array_walk(
            $local_file_names,
            function (&$v){
                $v = self::BASE_ASSET_PATH . $v;
            }
        );

        return $this->addJsOrCss($file_type, $local_file_names, self::INC_ADDRESS);
    }

    /**
     * @param $js_array
     * @param int $default_type
     * @return HTML
     */
    public function addJS($js_array, int $default_type = self::INC_ABBREVIATION): HTML
    {

        return $this->addJsOrCss('js', $js_array, $default_type);
    }

    /**
     * @param $css_array
     * @param int $default_type
     * @return HTML
     */
    public function addCss($css_array, $default_type = self::INC_ABBREVIATION): HTML
    {
        return $this->addJsOrCss('css', $css_array, $default_type);
    }

    private function addJsOrCss(string $cssOrJs, array $array, $default_type = self::INC_ABBREVIATION): self
    {

        $type_translator = [
            self::INC_ADDRESS => ['css' => 'CssFiles', 'js' => 'JsFiles'],
            self::INC_LITERAL => ['css' => 'LiteralCss', 'js' => 'LiteralJs'],
        ];

        foreach ($array as $element) {
            $element = (array)$element;
            $path = $element[0];
            $type = $element[1] ?? $default_type;
            if ($type === self::INC_ABBREVIATION) {
                $this->IncludableReader = $this->IncludableReader ?? new IncludableReader();
                $path = $this->IncludableReader->getPathFor($path, $cssOrJs);
                $type = self::INC_ADDRESS;
            } elseif($type === self::INC_ADDRESS) {
                $path .= '.'.$cssOrJs;
            }

            $this->VAR[$type_translator[$type][$cssOrJs]][] = $path;
        }

        return $this;
    }

    public function addGoogleFonts(array $fonts): HTML
    {
        $base_path = 'https://fonts.googleapis.com/css?family=';

        array_walk_recursive($fonts, 'trim');
        $font_path_array = [];
        foreach ($fonts as $font_and_styles) {
            $font_string = str_replace([' ', '_'], '+', array_shift($font_and_styles));
            if (!empty($font_and_styles)) {
                $font_string .= ':'.implode(',', $font_and_styles);
            }
            $font_path_array[] = $font_string;
        }
        $complete_path = $base_path.implode('|', $font_path_array);

        return $this->addCss([[$complete_path, self::INC_ADDRESS]]);
    }


    /**
     * @return $this
     */
    private function finalCompilation(): self
    {
        $this->addVariable('TITLE', $this->Title);
        $this->DOC = $this->TWIG->render($this->Template, $this->VAR);

        return $this;
    }

    /**
     * @param bool $echo If set to true, echos the document, too
     * @param bool $encode_entities
     * @return string The final html document after all compilations
     */
    public function render(bool $echo = true, $encode_entities = false): string
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
    public function setTemplate(string $template): self
    {
        $template .= substr($template, -5) !== '.twig' ? '.twig' : '';
        $this->Template = $template;

        return $this;
    }

    /**
     * @param string $base
     * @return $this
     */
    public function setBase(string $base = null): self
    {
        $base = $base ?? (defined('APP_URL') ? APP_URL : '');

        $this->VAR['base'] = $base;

        return $this;
    }


    /**
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title = null): self
    {
        $this->Title = $title ?? '';

        return $this;
    }

    public function getTitle(): string
    {
        return $this->Title;
    }


    /**
     * @return mixed
     */
    public function getVAR(): ?array
    {
        return $this->VAR;
    }

    /**
     * @param array $array
     * @param int $columns
     * @param bool $horizontal
     * @return array
     */
    public static function partition(array $array, int $columns = 2, bool $horizontal = true): array
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

    protected static function isDebug(): bool
    {
        return (defined('DEBUG') && !empty(DEBUG)) || !empty($GLOBALS['debug']);
    }
}
