<?php

namespace Phpjuicer\Data;

class ConstantData extends Data {
    private $sqlite = null;
    private $details = null;

    public function __construct($sqlite, $details) {
        $this->sqlite = $sqlite;
        $this->details = $details;
    }
}
?>