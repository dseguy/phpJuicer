<?php

namespace Phpjuicer\Diff;

class Text {
    private $data = null;
    
    const FORMAT = "% -20s|% -9d|% -9d|% -9d|\n";
    
    public function __construct($data) {
        $this->data = $data;
    }

    public function render($versionA, $versionB) {
        $text = sprintf("% -20s|% -9s|% -9s|% -9s|\n", '', $versionA, 'Common', $versionB);

        $namespacesA = $this->data->versions(['tag' => $versionA])->namespaces()->name;
        $namespacesB = $this->data->versions(['tag' => $versionB])->namespaces()->name;
        $text .= $this->displayNCR($namespacesA, $namespacesB, 'Namespaces');

        $classesA = $this->data->versions(['tag' => $versionA])->classes()->name;
        $classesB = $this->data->versions(['tag' => $versionB])->classes()->name;
        $text .= $this->displayNCR($classesA, $classesB, 'Classes');

        $traitsA = $this->data->versions(['tag' => $versionA])->traits()->name;
        $traitsB = $this->data->versions(['tag' => $versionB])->traits()->name;
        $text .= $this->displayNCR($traitsA, $traitsB, 'Traits');

        $interfacesA = $this->data->versions(['tag' => $versionA])->interfaces()->name;
        $interfacesB = $this->data->versions(['tag' => $versionB])->interfaces()->name;
        $text .= $this->displayNCR($interfacesA, $interfacesB, 'Interfaces');

        $class_constantsA = $this->data->versions(['tag' => $versionA])->class_constants()->name;
        $class_constantsB = $this->data->versions(['tag' => $versionB])->class_constants()->name;
        $text .= $this->displayNCR($class_constantsA, $class_constantsB, 'Class constants');

        $propertiesA = $this->data->versions(['tag' => $versionA])->properties()->name;
        $propertiesB = $this->data->versions(['tag' => $versionB])->properties()->name;
        $text .= $this->displayNCR($propertiesA, $propertiesB, 'Properties');

        $methodsA = $this->data->versions(['tag' => $versionA])->methods()->name;
        $methodsB = $this->data->versions(['tag' => $versionB])->methods()->name;
        $text .= $this->displayNCR($methodsA, $methodsB, 'Methods');

        $functionsA = $this->data->versions(['tag' => $versionA])->functions()->name;
        $functionsB = $this->data->versions(['tag' => $versionB])->functions()->name;
        $text .= $this->displayNCR($functionsA, $functionsB, 'Functions');

        $constantsA = $this->data->versions(['tag' => $versionA])->constants()->name;
        $constantsB = $this->data->versions(['tag' => $versionB])->constants()->name;
        $text .= $this->displayNCR($constantsA, $constantsB, 'Constants');

        $text .= PHP_EOL;
        
        print $text;
    }

    function displayNCR($A, $B, $type = '') {
        if (empty($A) && empty($B)) {
            $new = '';
            $common = 0;
            $removed = 0;
        } else {
            $diff = array_diff($B, $A);
            if (empty($diff) ) {
                $new = 0;
            } else {
                $new = count($diff);
            }
    
            $diff = array_diff($A, $B);
            if (empty($diff) ) {
                $removed = 0;
            } else {
                $removed = -count($diff);
            }
    
            $diff = array_intersect($A, $B);
            if (empty($diff) ) {
                $common = 0;
            } else {
                $common = count($diff);
            }
        }

        return sprintf(self::FORMAT, $type, $new, $common, $removed);
    }
}
?>