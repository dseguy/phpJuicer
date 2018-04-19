<?php

namespace Phpjuicer\Data;

class PropertyData extends Data {
    private $sqlite = null;
    private $details = null;

    public  $name = '';

    public function __construct($sqlite, $details) {
        $this->sqlite = $sqlite;
        $this->details = $details;
        
        $this->name = '$'.$details['name'];
    }

    public function visibility() {
        return $this->details['visibility'];
    }

    public function value() {
        return $this->details['value'];
    }

    public function doccomment() {
        return $this->details['doccomment'];
    }
}
?>