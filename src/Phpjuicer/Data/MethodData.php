<?php

namespace Phpjuicer\Data;

class MethodData extends Data {
    private $sqlite = null;
    private $details = null;
    private $myArguments = array();

    public $arguments = array();

    public function __construct($sqlite, $details) {
        return;
        $this->sqlite = $sqlite;
        $this->details = $details;
        
        $this->name = 'function '.$details['name'].'( )';
        
        $res = $this->sqlite->query('SELECT * FROM argumentsMethods WHERE methodId = '.$details['id']);
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