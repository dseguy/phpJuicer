<?php

namespace Phpjuicer\Data;

class ClassConstantData extends Data {
    private $sqlite = null;
    private $details = null;

    public function __construct($sqlite, $details) {
        $this->sqlite = $sqlite;
        $this->details = $details;
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