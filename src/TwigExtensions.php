<?php

namespace Fridde;

class TwigExpansions
{
    public $TWIG;
    private $globals;
    private $functions;
    private $filters;
    private $tests;

    function __construct($twig_environment, $globals = [])
    {
        $this->TWIG = $twig_environment;
        $this->globals = $globals;
    }

    public function addAll()
    {
        $this->defineAll();
        $this->addGlobals();
        $this->addFunctions();
        $this->addFilters();
        $this->addTests();

        return $this->TWIG;
    }

    private function addGlobals()
    {
        foreach($this->globals as $key => $variable){
            $this->TWIG->addGlobal($key, $variable);
        }
    }

    private function addFunctions()
    {
        $functions = $this->functions ?? [];
        array_walk($functions, function($function, $name){
            $this->TWIG->addFunction(new Twig_SimpleFunction($name, $function));
        });
    }

    private function addFilters()
    {
        $filters = $this->filters ?? [];
        array_walk($filters, function($filter, $name){
            $this->TWIG->addFilter(new Twig_SimpleFilter($name, $filter));
        });

    }

    private function addTests()
    {
        $tests = $this->tests ?? [];
        array_walk($tests, function($test, $name){
            $this->TWIG->addTest(new Twig_SimpleTest($name, $test));
        });
    }

    private function add($type, $function_name, $name = null)
    {
        $name = $name ?? $function_name;
        $variable_name = strtolower($type) . "s";
        $this->$variable_name[$name] = [$this, $function_name];
    }

    private function defineAll()
    {
        $this->add("test", "integer");
    }

    public function is_integer($var)
    {
        return is_integer($var);
    }
}
