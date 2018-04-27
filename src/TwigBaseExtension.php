<?php

namespace Fridde;

class TwigBaseExtension extends \Twig_Extension
{
    public $functions;
    public $filters;
    public $tests;

    public function __construct()
    {
        $this->functions = $this->defineFunctions();
        $this->filters = $this->defineFilters();
        $this->tests = $this->defineTests();
    }

    public function getFilters()
    {
        return $this->get('filters');
    }

    public function getFunctions()
    {
        return $this->get('functions');
    }

    public function getTests()
    {
        return $this->get('tests');
    }

    private function get(string $type)
    {
        $return = [];
        foreach($this->$type as $name => $callback){
            switch($type){
                case 'functions':
                    $r = new \Twig_Function($name, $callback);
                    break;
                case 'tests':
                    $r = new \Twig_Test($name, $callback);
                    break;
                case 'filters':
                    $r = new \Twig_Filter($name, $callback);
                    break;
            }
            $return[] = $r;
        }
        return $return;
    }


    public function defineFunctions()
    {
        return [];
    }

    public function defineFilters()
    {
        return [];
    }

    public function defineTests()
    {
        return [];
    }

    protected function getDefinitionArray(array $names)
    {
        $return = [];
        foreach ($names as $name_and_callback) {
            $name_and_callback = (array) $name_and_callback;
            $name = $name_and_callback[0];
            $callback = $name_and_callback[1] ?? $name;
            $return[$name] = [$this, $callback];
        }
        return $return;
    }


}
