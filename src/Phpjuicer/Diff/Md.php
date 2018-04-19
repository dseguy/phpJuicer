<?php

namespace Phpjuicer\Diff;

class Md {
    private $diff = null;
    private $html = '';

    const IDENT = '    ';
    private $level = 0;
    
    public function __construct() {
    }

    public function start() {
        $this->md = '';
    }
    
    public function end() {
        print $this->md .= PHP_EOL;

        file_put_contents('simple.md', $this->md);
    }

    public function render($diff, $version1, $version2) {
        $this->diff = $diff;
        
        $this->md .= <<<MD
Diff from $version1 to $version2
--------------------------------


MD;
        $all = $diff->namespaces();
        
        $this->md .= 'Namespaces'.PHP_EOL.PHP_EOL;
        $this->md .= '+ total '.count($all).' namespaces '.PHP_EOL;
    
        $added = $diff->namespaces('added');
        if (!empty($added)) {
            $this->md .= '+ '.count($added).' added namespaces '.PHP_EOL
                        .$this->makeList($added).PHP_EOL;
        }

        $removed = $diff->namespaces('removed');
        if (!empty($removed)) {
            $this->md .= '+ '.count($removed).' removed namespaces '.PHP_EOL
                        .$this->makeList($removed).PHP_EOL;
        }

/*
        $updated = $diff->namespaces('updated');
        if (!empty($updated)) {
            $this->md .= '+ '.count($updated).' updated namespaces '.PHP_EOL
                         .'    + `'.implode(PHP_EOL.'`    + `', $updated).'`'.PHP_EOL.PHP_EOL; 
        }
*/

        $updated = $diff->namespaces('updated');
        if (!empty($updated)) {
            $namespaceMd = '';
            foreach($updated as $namespace) {

                $classes = $this->renderClasses($namespace);
                if (!empty($classes)) {
                    $namespaceMd .= $classes; 
                }
            }

            if (!empty($namespaceMd)) {
                $this->md .= '    + namespace `'.$namespace.'`'.PHP_EOL;
                $this->md .= $namespaceMd; 
            }
        }

    }
    
    private function renderClasses($namespace) {
        $this->levelUp();

        $all = $this->diff->classes($namespace, 'all');
        
        $md = '';
    
        $added = $this->diff->classes($namespace, 'added');
        if (!empty($added)) {
            $md .= $this->ident().'+ ' .count($added).' added classes '.PHP_EOL
                        .$this->makeList($added).PHP_EOL;
        }

        $removed = $this->diff->classes($namespace, 'removed');
        if (!empty($removed)) {
            $md .= $this->ident().'+ ' .count($removed).' removed classes '.PHP_EOL
                        .$this->makeList($removed).PHP_EOL;
        }

/*
        $updated = $this->diff->classes($namespace, 'updated');
        if (!empty($updated)) {
            $md .= '        + ' .count($updated).' updated classes '.PHP_EOL
                         .'            + `'.implode("`\n        + `", $updated).'`'.PHP_EOL; 
        }
*/

        $updated = $this->diff->classes($namespace, 'updated');
        if (!empty($updated)) {
            foreach($updated as $class) {

                $classMd = '';

                $classconstants = $this->renderClassConstant($namespace, $class);
                if (!empty($classconstants)) {
                    $classMd .= $classconstants; 
                }

                $properties = $this->renderProperties($namespace, $class);
                if (!empty($properties)) {
                    $classMd .= $properties; 
                }

                if (!empty($classMd)) {
                    $md .= $this->ident().'+ class `'.$namespace.'\\'.$class.'`'.PHP_EOL;
                    $md .= $classMd; 
                }
            }
        }

        if (!empty($md)) {
            $md = $this->ident().'+ '.count($all).' total classes '.PHP_EOL.$md;
        }

        $this->levelDown();
        
        return $md;
    }

    private function renderClassConstant($namespace, $class) {
        $this->levelUp();

        $all = $this->diff->classconstants($namespace, $class, 'all');
        
        $md = '';
    
        $added = $this->diff->classconstants($namespace, $class, 'added');
        if (!empty($added)) {
            $md .= '            + ' .count($added).' added class constants '.PHP_EOL
                         .implode("\n+ ", $added).PHP_EOL; 
        }

        $removed = $this->diff->classconstants($namespace, $class, 'removed');
        if (!empty($removed)) {
            $md .= '            + ' .count($removed).' removed class constants '.PHP_EOL
                         .implode("\n+ ", $removed).PHP_EOL; 
        }

        $updated = $this->diff->classconstants($namespace, $class, 'updated');
        if (!empty($updated)) {
            foreach($updated as $cc) {
                $visibility = $this->diff->classConstantsVisibility($namespace, $class, $cc);
                if (!empty($visibility)) {
                    $md .= '            + ' ."$cc : visiblity change from $visibility[0] to $visibility[1]".PHP_EOL;
                }
                $value = $this->diff->classConstantsValue($namespace, $class, $cc);
                if (!empty($value)) {
                    $md .= '            + ' ."$cc : value change from $value[0] to $value[1]".PHP_EOL;
                }
            }
        }

        if (!empty($md)) {
            $md = '        + '.count($all).' total class constants '.PHP_EOL.$md;
        }

        $this->levelDown();
        
        return $md;
    }

    private function renderProperties($namespace, $class) {
        $this->levelUp();
        $all = $this->diff->properties($namespace, $class, 'all');
        
        $md = '';
    
        $added = $this->diff->properties($namespace, $class, 'added');
        if (!empty($added)) {
            $added = array_map(function($x) { return '$'.$x; }, $added);
            $md .= $this->ident().'+ ' .count($added).' added properties '.PHP_EOL
                        .$this->makeList($added);
        }

        $removed = $this->diff->properties($namespace, $class, 'removed');
        if (!empty($removed)) {
            $removed = array_map(function($x) { return '$'.$x; }, $removed);
            $md .= $this->ident().'+ ' .count($removed).' removed properties '.PHP_EOL
                        .$this->makeList($removed);
        }

        $updated = $this->diff->properties($namespace, $class, 'updated');
        if (!empty($updated)) {
            $propertyChanges = array();
            foreach($updated as $property) {
                $visibility = $this->diff->PropertyVisibility($namespace, $class, $property);
                if (!empty($visibility)) {
                    $propertyChanges[] = '$' ."$property : visiblity changed from $visibility[0] to $visibility[1]";
                }
                $value = $this->diff->propertyValue($namespace, $class, $property);
                if (empty($value)) { continue; }
                if ($value[0] === null) {
                    $value[0] = '<none>';
                } elseif ($value[0] === '') {
                    $value[0] = "''";
                }
                if ($value[1] === null) {
                    $value[1] = '<none>';
                } elseif ($value[1] === '') {
                    $value[1] = "''";
                }
                $propertyChanges[] = '$' ."$property : value changed from $value[0] to $value[1]";
            }
            if (!empty($propertyChanges)) {
                $md .= $this->ident().'+ property changes'.PHP_EOL;
                $md .= $this->makeList($propertyChanges);
            }
        }

        if (!empty($md)) {
            $md = $this->ident().'+ '.count($all).' total properties '.PHP_EOL.$md;
        }

        $this->levelDown();
        
        return $md;
    }


/*

Namespace
  Constant
     Value
  Function
     Signature
     Return
  Class
     Constants
       Visibility
       Value
     Properties
       Visibility
       Value
     Methods
       Visibility
       Static
       Signature
     Traits
     Extends
     Implements
     Abstract
     Final
  Interface
     Constants
       Value
     Methods
       Signature
     Extends
  Trait
     Properties
       Visibility
       Value
     Methods
       Signature
       Visibility
     Traits
  

*/

    public function render2($version1, $version2) {
        $this->md .= <<<MD
From $version1 to $version2
---------------------------

MD;

        $namespaces1 = $this->data->versions($version1)->namespaces()->list();
        $namespaces2 = $this->data->versions($version2)->namespaces()->list();
        $this->md .= $this->makeTitle('Namespaces');
        $this->md .= $this->display($namespaces1, $namespaces2);

        $namespaces = array_intersect($namespaces1, $namespaces2);

        $md_classes = '';
        foreach($namespaces as $namespace) {
            print "\nnamespace : $namespace\n";
            $classes1 = $this->data->versions($version1)->namespaces($namespace)->classes()->list();
            $classes2 = $this->data->versions($version2)->namespaces($namespace)->classes()->list();
            
            $md = $this->display($classes1, $classes2);
            if (!empty($md)) {
                $md_classes .= $this->makeTitle('Classes in '.($namespace === '' ? '\\' : $namespace), 2);
                $md_classes .= $md;
            } else {
                $md_classes .= 'No class changes';
            }

            $classes = array_intersect($classes1, $classes2);
            foreach($classes as $classe) {
                $md_classes .= $this->makeTitle('class '.($namespace === '' ? '\\' : $namespace).'\\'.$classe, 2);
                print "class $classe\n";
                $class_constant1 = $this->data->versions($version1)->namespaces($namespace)->classes($classe)->class_constants()->list();
                $class_constant2 = $this->data->versions($version2)->namespaces($namespace)->classes($classe)->class_constants()->list();
            
                $md = $this->display($class_constant1, $class_constant2);
                if (!empty($md)) {
                    $md_classes .= $this->makeTitle('Constants in '.($namespace === '' ? '\\' : $namespace).'\\'.$classe, 2);
                    $md_classes .= $md;
                } else {
                    $md_classes .= 'No class constant changes'.PHP_EOL;
                }

                $class_constant = array_intersect($class_constant1, $class_constant2);
                foreach($class_constant as $cc) {
                    print 'Constants visibility for '.($namespace === '' ? '\\' : $namespace).'\\'.$classe.'::'.$cc.PHP_EOL;
                    
                    $class_constant_visibility1 = $this->data->versions($version1)->namespaces($namespace)->classes($classe)->class_constants($cc)->visibility();
                    $class_constant_visibility2 = $this->data->versions($version2)->namespaces($namespace)->classes($classe)->class_constants($cc)->visibility();

                    $md = $this->displayString($cc.' visibility', $class_constant_visibility1, $class_constant_visibility2);
                    if (empty($md)) {
                        $md_classes .= 'No changes in class constant visibility'.PHP_EOL;
                    } else {
                        $md_classes .= $this->makeTitle('Constants visibility in '.($namespace === '' ? '\\' : $namespace).'\\'.$classe.'::'.$cc, 2);
                        $md_classes .= $md;
                    }

                    $class_constant_value1 = $this->data->versions($version1)->namespaces($namespace)->classes($classe)->class_constants($cc)->value();
                    $class_constant_value2 = $this->data->versions($version2)->namespaces($namespace)->classes($classe)->class_constants($cc)->value();
                    
                    $md = $this->displayString($cc.' value', $class_constant_value1, $class_constant_value2);
                    if (empty($md)) {
                        $md_classes .= 'No changes in class constant value'.PHP_EOL;
                    } else {
                        $md_classes .= $this->makeTitle('Constants value in '.($namespace === '' ? '\\' : $namespace).'\\'.$classe.'::'.$cc, 2);
                        $md_classes .= $md;
                    }
                }

                $properties1 = $this->data->versions($version1)->namespaces($namespace)->classes($classe)->properties()->list();
                $properties2 = $this->data->versions($version2)->namespaces($namespace)->classes($classe)->properties()->list();
            
                $md = $this->display($properties1, $properties2);
                if (!empty($md)) {
                    $md_classes .= $this->makeTitle('Properties in '.($namespace === '' ? '\\' : $namespace).'\\'.$classe, 2);
                    $md_classes .= $md;
                } else {
                    $md_classes .= 'No properties changes'.PHP_EOL;
                }

                $methods1 = $this->data->versions($version1)->namespaces($namespace)->classes($classe)->methods()->list();
                $methods2 = $this->data->versions($version2)->namespaces($namespace)->classes($classe)->methods()->list();
                
                $md = $this->display($methods1, $methods2);
                if (!empty($md)) {
                    $md_classes .= $this->makeTitle('Methods in '.($namespace === '' ? '\\' : $namespace).'\\'.$classe, 2);
                    $md_classes .= $md;
                } else {
                    $md_classes .= 'No method changes'.PHP_EOL;
                }
                
                $md_classes .= PHP_EOL;
            }
        }

        // always full
        $this->md .= $this->makeTitle('Classes');
        $this->md .= $md_classes;

        $md_interfaces = '';
        foreach($namespaces as $namespace) {
            $interfaces1 = $this->data->versions($version1)->namespaces($namespace)->interfaces()->list();
            $interfaces2 = $this->data->versions($version2)->namespaces($namespace)->interfaces()->list();
            
            $md = $this->display($interfaces1, $interfaces2);
            if (!empty($md)) {
                $md_interfaces .= $this->makeTitle('Interfaces in '.($namespace === '' ? '\\' : $namespace), 2);
                $md_interfaces .= $md;
            }
        }
        if (!empty($md_traits)) {
            $this->md .= $this->makeTitle('Interfaces');
            $this->md .= $md_interfaces;
        }

        $md_traits = '';
        foreach($namespaces as $namespace) {
            $traits1 = $this->data->versions($version1)->namespaces($namespace)->traits()->list();
            $traits2 = $this->data->versions($version2)->namespaces($namespace)->traits()->list();
            
            $md = $this->display($traits1, $traits2);
            if (!empty($md)) {
                $this->md .= $this->makeTitle('Traits in '.($namespace === '' ? '\\' : $namespace), 2);
                $this->md .= $md;
            }
        }
        if (!empty($md_traits)) {
            $this->md .= $this->makeTitle('Traits');
            $this->md .= $md_traits;
        }

        return;
/*
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

    private function makeTitle($title, $level = 1) {
        return str_repeat('#', $level).' '.$title.PHP_EOL;
    }

    private function displayString($name, $in, $out) {
        if ($in === $out) {
            return '';
        }
        return "$name changed from $in to $out\n";
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
        $md = '';

        $countOld    = count($old);
        $countNew    = count($new);
        $countCommon = count($common);

        $listOld    = $this->toList($old);
        $listNew    = $this->toList($new);
        $listCommon = $this->toList($common);
        
        if ($countCommon > 0) {
            $common = <<<MD
$countCommon common.


MD;
        } else {
            $common = <<<MD
MD;
        }


        if ($countNew > 0) {
            $new = <<<MD
$countNew new : 
$listNew 

MD;
        } else {
            $new = '';
        }

        if ($countOld > 0) {
            $old = <<<MD
$countOld removed : 
$listOld 

MD;
        } else {
            $old = '';
        }

        $md .= <<<MD

$common$new$old
MD;

        return $md;
    }
    
    private function toList($array) {
        return PHP_EOL.'* '.join(PHP_EOL."* ", $array).PHP_EOL;
    }
    
    private function ident() {
        return str_repeat(self::IDENT, $this->level);
    }
    
    private function levelUp() {
        ++$this->level;
    }

    private function levelDown() {
        --$this->level;
    }

    private function makeList($list) {
        $this->levelUp();
        $return = $this->ident().'+ '.implode(PHP_EOL.$this->ident().'+ ', $list).PHP_EOL;
        $this->levelDown();
        
        return $return;
    }
}
?>