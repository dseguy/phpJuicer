<?php

namespace Phpjuicer\Data;

abstract class Data {
    protected function get($what, $name = null) {
        if ($name === null) {
            return $what;
        }
        
        if (isset($what[$name])) {
            return $what[$name];
        }
        
        die("No such '$name'");
    }
}
?>