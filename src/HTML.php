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
    public const INC_URL = 1;
    public const INC_LITERAL = 2;
    public const INC_ASSET = 3;
    public const INC_FILE = 4;

    public const FONT_GOOGLE = 0;


    public function __construct(array $template_dir = null, array $extensions = [], string $cache_path = null)
    {
        if (empty($template_dir) && !defined('BASE_DIR')) {
            throw new \Exception('Template directory could not be determined.');
        }
        if (empty($template_dir)) {
            $template_dir[] = BASE_DIR.'/templates';
        }

        $loader = new \Twig_Loader_Filesystem($template_dir);

        $env_options = ['debug' => self::isDebug()];
        if (!empty($cache_path)) {
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

    private function addJsOrCss(string $extension, array $array, int $default_type = self::INC_ABBREVIATION): self
    {

        foreach ($array as $element) {
            $a = (array) $element;
            $resource = array_shift($a);
            $type = array_shift($a) ?? $default_type;

            if ($type !== self::INC_LITERAL) {
                $path = $this->convertToPath($resource, $type, $extension);
                $this->VAR[$extension]['sources'][] = $path;
            } else {
                $this->VAR[$extension]['literal'][] = $resource;
            }
        }

        return $this;
    }

    public function convertToPath(string $resource, int $inc_type, string $file_ext = null): ?string
    {
        $ext = $file_ext ? '.'.$file_ext : '';

        if ($inc_type === self::INC_ABBREVIATION) {
            return $this->getIncludableReader()->getPathFor($resource, $file_ext);
        }
        if ($inc_type === self::INC_URL) {
            return $resource;
        }
        if ($inc_type === self::INC_ASSET) {
            return self::BASE_ASSET_PATH.$resource.$ext;
        }
        if ($inc_type === self::INC_FILE) {
            return $resource.$ext;
        }

        return null;
    }

    public function addFontGroup(array $fonts, int $font_type = self::FONT_GOOGLE): self
    {
        $paths = [];

        if($font_type === self::FONT_GOOGLE){
            $paths = [$this->convertToGoogleFontPath($fonts)];
        }

        return $this->addCss($paths, self::INC_URL);
    }

    public function addFonts(array $fontgroups, int $default_font_type = self::FONT_GOOGLE): self
    {
        foreach($fontgroups as $group){
            $fonts = $group[0];
            $font_type = $group[1] ?? $default_font_type;

            $this->addFontGroup($fonts, $font_type);
        }

        return $this;
    }

    public function convertToGoogleFontPath(array $fonts): string
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
        return $base_path . implode('|', $font_path_array);
    }

    public function addGoogleFonts(array $fonts): self
    {
        return $this->addFonts($fonts, self::FONT_GOOGLE);
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

    public function getIncludableReader(): IncludableReader
    {
        return ($this->IncludableReader ?? new IncludableReader());
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
