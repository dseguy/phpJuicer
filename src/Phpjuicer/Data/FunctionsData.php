<?php

namespace Phpjuicer\Data;

class FunctionsData extends Data {
    private $sqlite = null;
    private $details = null;

    private $myArguments = array();

    public $arguments = array();

    public function __construct($sqlite, $details) {
        $this->sqlite = $sqlite;
        $this->details = $details;
        
        $res = $this->sqlite->query('SELECT * FROM argumentsFunctions WHERE functionId = '.$details['id']);
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $this->myArguments[$row['name']] = new ArgumentData($this->sqlite, $row);
            $this->arguments[] = $row['name'];
        }
    }
    
    public function arguments($name = null) {
        return $this->get($this->myArguments, $name);
    }
}
?>