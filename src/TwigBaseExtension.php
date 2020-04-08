<?php

namespace Fridde;

class TwigBaseExtension extends \Twig\Extension\AbstractExtension
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

    public function getFilters(): array
    {
        return $this->get('filters');
    }

    public function getFunctions(): array
    {
        return $this->get('functions');
    }

    public function getTests(): array
    {
        return $this->get('tests');
    }

    private function get(string $type): array
    {
        $return = [];
        foreach($this->$type as $name => $callback){
            switch($type){
                case 'functions':
                    $r = new \Twig\TwigFunction($name, $callback);
                    break;
                case 'tests':
                    $r = new \Twig\TwigTest($name, $callback);
                    break;
                case 'filters':
                    $r = new \Twig\TwigFilter($name, $callback);
                    break;
            }
            $return[] = $r;
        }
        return $return;
    }


    public function defineFunctions(): array
    {
        return [];
    }

    public function defineFilters(): array
    {
        return [];
    }

    public function defineTests(): array
    {
        return [];
    }

    protected function getDefinitionArray(array $names): array
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
