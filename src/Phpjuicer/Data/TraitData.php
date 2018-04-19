<?php

namespace Phpjuicer\Data;

class TraitData extends Data {
    private $sqlite = null;
    private $details = null;
    
    private $myMethods    = array();
    private $myProperties = array();

    public $methods = array();
    public $properties = array();

    public function __construct($sqlite, $details) {
        $this->sqlite  = $sqlite;
        $this->details = $details;
        $this->name    = $details['name'];
        
        $res = $this->sqlite->query('SELECT * FROM properties WHERE citId = '.$details['id']);
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $this->myProperties[$row['name']] = new PropertyData($this->sqlite, $row);
            $this->properties[] = $row['name'];
        }

        $res = $this->sqlite->query('SELECT * FROM methods WHERE citId = '.$details['id']);
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $this->myMethods[$row['name']] = new MethodData($this->sqlite, $row);
            $this->methods[] = $row['name'];
        }
    }
    
    public function methods($name = null) {
        return $this->get($this->myMethods, $name);
    }

    public function properties($name = null) {
        return $this->get($this->myPropertis, $name);
    }
}
?>