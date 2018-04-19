<?php

namespace Phpjuicer\Data;

class ListData extends Data implements \ArrayAccess {
    private $theList = array();
    private $theClass = null;

    public function __construct() {
    
    }
    
    public function count() {
        return count($this->theList);
    }
    
    public function list() {
        return array_keys($this->theList);
    }
    
    public function __call($name, $args) {
        if (method_exists($this->theClass, $name)) {
            $res = array();
            foreach($this->theList as $id => $element) {
                $res[] = $element->$name(...$args);
            }

            if ($res[0] instanceof self) {
                $return = array_pop($res);
                if (!empty($res)) {
                    $return->merge(...$res);
                }
            } elseif (is_array($res[0])) {
                $return = array_merge( ...$res);
                print_r($return);
            } else {
                $return = $res;
            }

        } else {
            print "No such method as $name\n";
            die();
        }
        
        return $return;
    }
    
    private function merge(...$args) {
        $this->theList = array_merge($this->theList, ...array_column($args, 'theList'));
    }
    
/* ArrayAccess Methods */
    public function offsetExists($offset ) {
        return isset($this->theList[$offset]);
    }

    public function offsetGet($offset) {
        if (isset($this->theList[$offset])) { 
            $return = new self();
            $return[$offset] = $this->theList[$offset];
            return $return;
        } else {
            return null;
        }
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->theList[] = $value;
        } else {
            if (empty($this->theList)) {
                $this->theClass = get_class($value);
            }
            if (!$value instanceof $this->theClass) {
                die("Wrong class : $this->theClass expected, ".get_class($value)." inserted\n");
            }
            $this->theList[$offset] = $value;
        }
    }
    
    public function offsetUnset($offset) {
        unset($this->theList[$offset]);
    }
}
?>