<?php

namespace Phpjuicer\Diff;

class SimpleTable {
    private $data = null;
    private $html = '';
    
    public function __construct($data) {
        $this->data = $data;
    }

    public function start() {
        $this->html = <<<HTML
<html>
  <head>
    <meta charset="utf-8">
  </head>

  <body>
    <table style="border: 1px solid black;">

HTML;
    }
    
    public function end() {
        $this->html .= <<<HTML
    </table>
  </body>
</html>
HTML;
        file_put_contents('simpletable.html', $this->html);
    }

    public function render($versionA, $versionB) {
        $this->html .= <<<HTML
    <tr>
        <td colspan="2">$versionA->name to $versionB->name</td>
    </tr>
HTML;

        // SUMMARY_TRACE

        $namespacesA = $this->data->getNamespaces($versionA);
        $namespacesB = $this->data->getNamespaces($versionB );
        $this->html .= $this->display($namespacesA, $namespacesB, 'Namespaces');
        
        $namespaces = array_intersect($namespacesA, $namespacesB);
/*
        foreach($namespaces as $namespace) {
            $classesA = $versionA->namespaces($namespace)->classes;
            $classesB = $versionB->namespaces($namespace)->classes;
            $this->html .= $this->display($classesA, $classesB, 'Classes');

            $classes = array_intersect($classesA, $classesB);
            foreach($classes as $class) {
                $classHtml = '';

                $methodsA = $versionA->namespaces($namespace)->classes($class)->methods;
                $methodsB = $versionB->namespaces($namespace)->classes($class)->methods;
                $methodsHtml = $this->display($methodsA, $methodsB, 'Class Methods');

                $methods = array_intersect($methodsA, $methodsB);
                $argumentsHtml = '';
                foreach($methods as $method) {
                    $argumentsA = $versionA->namespaces($namespace)->classes($class)->methods($method)->arguments;
                    $argumentsB = $versionB->namespaces($namespace)->classes($class)->methods($method)->arguments;
                    $argumentsHtml .= $this->display($argumentsA, $argumentsB, 'Class Methods Arguments');
                }
                if (!empty($argumentsHtml) && !empty($methodsHtml)) {
                    $classHtml .= $this->makeTitle('Class Methods '.$namespace.'\\'.$class).$argumentsHtml.$methodsHtml;
                }

                $propertyA = $versionA->namespaces($namespace)->classes($class)->properties;
                $propertyB = $versionB->namespaces($namespace)->classes($class)->properties;
                $propertiesHtml = $this->display($propertyA, $propertyB, 'Class Property');
                if (!empty($propertiesHtml)) {
                    $classHtml .= $this->makeTitle('Class Properties '.$namespace.'\\'.$class).$propertiesHtml;
                }

                $cconstantA = $versionA->namespaces($namespace)->classes($class)->constants;
                $cconstantB = $versionB->namespaces($namespace)->classes($class)->constants;
                $constantsHtml = $this->display($cconstantA, $cconstantB, 'Class Constant');
                if (!empty($constantsHtml)) {
                    $classHtml .= $this->makeTitle('Class Constants '.$namespace.'\\'.$class).$constantsHtml;
                }

                if (!empty($classHtml)) {
                    $classHtml = $this->makeTitle('Class '.$namespace.'\\'.$class).$classHtml;
                    $this->html .= $classHtml;
                }
            }

            $interfacesA = $versionA->namespaces($namespace)->interfaces;
            $interfacesB = $versionB->namespaces($namespace)->interfaces;
            $this->html .= $this->display($interfacesA, $interfacesB, 'Interfaces');

            $interfaces = array_intersect($interfacesA, $interfacesB);
            foreach($interfaces as $interface) {
                $methodsA = $versionA->namespaces($namespace)->interfaces($interface)->methods;
                $methodsB = $versionB->namespaces($namespace)->interfaces($interface)->methods;
                $this->html .= $this->display($methodsA, $methodsB, 'Interface Methods');

                $methods = array_intersect($methodsA, $methodsB);
                foreach($methods as $method) {
                    $argumentsA = $versionA->namespaces($namespace)->interfaces($interface)->methods($method)->arguments;
                    $argumentsB = $versionB->namespaces($namespace)->interfaces($interface)->methods($method)->arguments;
                    $this->html .= $this->display($argumentsA, $argumentsB, 'Interface Methods Arguments');
                }

                $cconstantA = $versionA->namespaces($namespace)->classes($class)->properties;
                $cconstantB = $versionB->namespaces($namespace)->classes($class)->properties;
                $this->html .= $this->display($cconstantA, $cconstantB, 'Class Constant');
            }

            $traitsA = $versionA->namespaces($namespace)->traits;
            $traitsB = $versionB->namespaces($namespace)->traits;
            $this->html .= $this->display($traitsA, $traitsB, 'Traits');

            $traits = array_intersect($traitsA, $traitsB);
            foreach($traits as $trait) {
                $methodsA = $versionA->namespaces($namespace)->traits($trait)->methods;
                $methodsB = $versionB->namespaces($namespace)->traits($trait)->methods;
                $this->html .= $this->display($methodsA, $methodsB, 'Trait Methods');

                $methods = array_intersect($methodsA, $methodsB);
                foreach($methods as $method) {
                    $argumentsA = $versionA->namespaces($namespace)->traits($trait)->methods($method)->arguments;
                    $argumentsB = $versionB->namespaces($namespace)->traits($trait)->methods($method)->arguments;
                    $this->html .= $this->display($argumentsA, $argumentsB, 'Trait Methods Arguments');
                }

                $propertyA = $versionA->namespaces($namespace)->traits($trait)->properties;
                $propertyB = $versionB->namespaces($namespace)->traits($trait)->properties;
                $this->html .= $this->display($propertyA, $propertyB, 'Trait Property');
            }

            $constantA = $versionA->namespaces($namespace)->constants;
            $constantB = $versionB->namespaces($namespace)->constants;
            $this->html .= $this->display($constantA, $constantB, 'Constant');

            $functionsA = $versionA->namespaces($namespace)->functions;
            $functionsB = $versionB->namespaces($namespace)->functions;
            $this->html .= $this->display($functionsA, $functionsB, 'Functions');
 
            $functions = array_intersect($functionsA, $functionsB);
            foreach($functions as $function) {
                $argumentsA = $versionA->namespaces($namespace)->functions($function)->arguments;
                $argumentsB = $versionB->namespaces($namespace)->functions($function)->arguments;
                $this->html .= $this->display($argumentsA, $argumentsB, 'Class Methods Arguments');
            }
        }
*/
        $this->html .= PHP_EOL;
    }

    private function makeTitle($title) {
        return <<<HTML
    <tr>
        <th>$title</th>
    </tr>

HTML;
    }
        
    private function display($in, $out) {
        if (empty($in) && empty($out)) {
            return '';
        }

        $old = array_diff($out, $in);
        $new = array_diff($in, $out);
        $common = array_intersect($in, $out);

        if (empty($old) && empty($new)) {
            return '';
        }
        $html = '';

        $countOld    = count($old);
        $countNew    = count($new);
        $countCommon = count($common);

        $listOld    = $this->toList($old);
        $listNew    = $this->toList($new);
        $listCommon = $this->toList($common);

        $html .= <<<HTML
    <tr>
        <td style="border: 1px solid black">$countOld</td>
        <td style="border: 1px solid black">$countNew</td>
    </tr>
    <tr>
        <td>$listOld</td>
        <td>$listNew</td>
    </tr>
    
HTML;

        return $html;
    }
    
    private function toList($array) {
        $list  = "<ul>\n";
        $array = array_map(function($x) { return "<li>$x</li>\n"; }, $array);
        $list .= join("\n", $array);
        $list .= "</ul>\n";
        
        return $list;
    }
}
?>