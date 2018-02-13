<?php


namespace Fridde;


use Symfony\Component\Yaml\Yaml;

class IncludableReader
{

    public $includables;

    public $includables_file_name = 'includables.yml';

    public function __construct()
    {
        $this->setIncludablesFromFile();
    }


    private function setIncludablesFromFile(string $file_name = null)
    {
        $file_name = $file_name ?? $this->includables_file_name;
        $path = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . $file_name;

        if (is_readable($path)) {
            $this->includables = Yaml::parse(file_get_contents($path));
        } else {
            throw new \Exception("No file for includables found.");
        }
    }

    public function getPathFor($abbreviation, $file_type = 'js', $remote = true)
    {
        $place = $remote ? 'remote' : 'local';

        $value = $this->includables[$file_type][$place][$abbreviation] ?? null;
        if(empty($value)){
            throw new \Exception("The abbreviation $abbreviation could not be found. Check the includable file.");
        }

        if(is_array($value)){
            $base_path = $this->includables['base_paths'][$value[0]] ?? null;
            if(empty($base_path)){
                throw new \Exception('The base path for ' . $value[0] . ' could not be found. Check the includable file.');
            }
            return $base_path . $value[1] . '.' . $file_type;
        } elseif(is_string($value)){
            return $value . '.' . $file_type;
        } else {
            throw new \Exception('The value for ' . $abbreviation . ' has an invalid type format. Check the includable file.');
        }
    }


}
