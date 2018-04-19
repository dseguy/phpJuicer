<?php

namespace Phpjuicer\Data;

class DiffData {
    private $data = null;
    private $version1 = '';
    private $version2 = '';

    public $namespaces               = array();
    public $classes                  = array();
    public $classconstants           = array();
    public $classconstantsvisibility = array();
    public $classconstantsvalue      = array();
    
    public $interfaceconstants           = array();
    public $interfaceconstantsvalue      = array();
    public $interfacemethod              = array();
    

    public function __construct($data) {
        $this->data = $data;
    }
    
    public function load($version1, $version2) {
        $this->version1 = $version1;
        $this->version2 = $version2;
    }
    
    public function namespaces($what = 'all') {
        if (empty($this->namespaces)) {
            $namespaces1 = $this->data->versions($this->version1)->namespaces()->list();
            $namespaces2 = $this->data->versions($this->version2)->namespaces()->list();
    
            $this->namespaces['added']   = array_diff($namespaces1, $namespaces2) ?? [];
            $this->namespaces['removed'] = array_diff($namespaces1, $namespaces2) ?? [];
            $this->namespaces['updated'] = array_intersect($namespaces1, $namespaces2) ?? [];
        }

        return $this->select($this->namespaces, $what);
    }
        
    public function classes($namespace, $what = 'all') {
        if (!isset($this->classes[$namespace])) {
            $classes1 = $this->data->versions($this->version1)->namespaces($namespace)->classes()->list();
            $classes2 = $this->data->versions($this->version2)->namespaces($namespace)->classes()->list();

            $this->classes[$namespace]['added']   = array_diff($classes1, $classes2) ?? [];
            $this->classes[$namespace]['removed'] = array_diff($classes2, $classes1) ?? [];
            $this->classes[$namespace]['updated'] = array_intersect($classes1, $classes2) ?? [];
        }
        
        return $this->select($this->classes[$namespace], $what);
    }

    public function classconstants($namespace, $class, $what = 'all') {
        $path = $namespace.'\\'.$class;
        if (!isset($this->classes[$path])) {
            $class_constant1 = $this->data->versions($this->version1)->namespaces($namespace)->classes($class)->class_constants()->list();
            $class_constant2 = $this->data->versions($this->version2)->namespaces($namespace)->classes($class)->class_constants()->list();

            $this->classconstants[$path]['added']   = array_diff($class_constant1, $class_constant2) ?? [];
            $this->classconstants[$path]['removed'] = array_diff($class_constant2, $class_constant1) ?? [];
            $this->classconstants[$path]['updated'] = array_intersect($class_constant1, $class_constant2) ?? [];
        }
        
        return $this->select($this->classconstants[$path], $what);
    }

    public function classConstantsValue($namespace, $class, $const) {
        $value = array(
                $this->data->versions($this->version1)->namespaces($namespace)->classes($class)->class_constants($const)->value(),
                $this->data->versions($this->version2)->namespaces($namespace)->classes($class)->class_constants($const)->value(),
                );
        if ($value[0] === $value[1]) {
            return [];
        } else {
            return $value;
        }
    }

    public function classConstantsVisibility($namespace, $class, $const) {
        $visibility = array(
                $this->data->versions($this->version1)->namespaces($namespace)->classes($class)->class_constants($const)->visibility(),
                $this->data->versions($this->version2)->namespaces($namespace)->classes($class)->class_constants($const)->visibility(),
                );
        if ($visibility[0] === $visibility[1]) {
            return [];
        } else {
            return $visibility;
        }
    }

    public function properties($namespace, $class, $what = 'all') {
        $path = $namespace.'\\'.$class;
        if (!isset($this->classes[$path])) {
            $property1 = $this->data->versions($this->version1)->namespaces($namespace)->classes($class)->properties()->list();
            $property2 = $this->data->versions($this->version2)->namespaces($namespace)->classes($class)->properties()->list();

            $this->properties[$path]['added']   = array_diff($property1, $property2) ?? [];
            $this->properties[$path]['removed'] = array_diff($property2, $property1) ?? [];
            $this->properties[$path]['updated'] = array_intersect($property1, $property2) ?? [];
        }
        
        return $this->select($this->properties[$path], $what);
    }

    public function propertyValue($namespace, $class, $property) {
        $value = array(
                $this->data->versions($this->version1)->namespaces($namespace)->classes($class)->properties($property)->value(),
                $this->data->versions($this->version2)->namespaces($namespace)->classes($class)->properties($property)->value()
                );
        if ($value[0] === $value[1]) {
            return [];
        } else {
            return $value;
        }
    }

    public function propertyVisibility($namespace, $class, $property) {
        $visibility = array(
                $this->data->versions($this->version1)->namespaces($namespace)->classes($class)->properties($property)->visibility(),
                $this->data->versions($this->version2)->namespaces($namespace)->classes($class)->properties($property)->visibility(),
                );
        if ($visibility[0] === $visibility[1]) {
            return [];
        } else {
            return $visibility;
        }
    }

    public function interfaces($namespace, $what = 'all') {
        if (!isset($this->interfaces[$namespace])) {
            $interfaces1 = $this->data->versions($this->version1)->namespaces($namespace)->interfaces()->list();
            $interfaces2 = $this->data->versions($this->version2)->namespaces($namespace)->interfaces()->list();

            $this->interfaces[$namespace]['added']   = array_diff($interfaces1, $interfaces2) ?? [];
            $this->interfaces[$namespace]['removed'] = array_diff($interfaces2, $interfaces1) ?? [];
            $this->interfaces[$namespace]['updated'] = array_intersect($interfaces1, $interfaces2) ?? [];
        }
        
        return $this->select($this->interfaces[$namespace], $what);
    }

    public function interfaceconstants($namespace, $interface, $what = 'all') {
        $path = $namespace.'\\'.$interface;
        if (!isset($this->interfaces[$path])) {
            $interface_constant1 = $this->data->versions($this->version1)->namespaces($namespace)->interfaces($interface)->interface_constants()->list();
            $interface_constant2 = $this->data->versions($this->version2)->namespaces($namespace)->interfaces($interface)->interface_constants()->list();

            $this->interfaces[$path]['added']   = array_diff($interface_constant1, $interface_constant2) ?? [];
            $this->interfaces[$path]['removed'] = array_diff($interface_constant2, $interface_constant1) ?? [];
            $this->interfaces[$path]['updated'] = array_intersect($interface_constant1, $interface_constant2) ?? [];
        }
        
        return $this->select($this->interfaces[$path], $what);
    }


    private function select($array, $what) {
        switch($what) {
            case 'added' : 
                $return = $array['added'];
                break;

            case 'removed' : 
                $return = $array['removed'];
                break;

            case 'updated' : 
                $return = $array['updated'];
                break;
            
            case 'all' : 
            default : 
                $return = array_merge($array['added'],
                                      $array['removed'],
                                      $array['updated']);
        }
        
        return $return;
    }
}
?>