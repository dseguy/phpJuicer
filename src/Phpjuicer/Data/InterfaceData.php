<?php

namespace Phpjuicer\Data;

class InterfaceData extends Data {
    private $sqlite = null;
    private $details = null;

    public $myMethods            = array();
    public $myInterfaceConstants = array();

    public function __construct($sqlite, $details) {
        $this->sqlite = $sqlite;
        $this->details = $details;
        
        $this->myMethods            = new ListData();
        $this->myInterfaceConstants = new ListData();

        $res = $this->sqlite->query('SELECT * FROM methods WHERE citId = '.$details['id']);
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $this->myMethods[$row['name']] = new MethodData($this->sqlite, $row);
        }

        $res = $this->sqlite->query('SELECT * FROM class_constants WHERE citId = '.$details['id']);
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $this->myInterfaceConstants[$row['name']] = new ClassConstantData($this->sqlite, $row);
        }
    }
    
    public function methods($name = null) {
        if ($name === null) {
            return $this->myMethods;
        } else {
            return $this->myMethods[$name];
        }
    }

    public function interface_constants($name = null) {
        if ($name === null) {
            return $this->myInterfaceConstants;
        } else {
            return $this->myInterfaceConstants[$name];
        }
    }
}
?>