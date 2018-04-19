<?php

namespace Phpjuicer\Data;

class ClassData extends Data {
    private $sqlite = null;
    private $details = null;

    private $myMethods         = null;
    private $myProperties      = null;
    private $myClassConstants  = null;

    public function __construct($sqlite, $details) {
        $this->sqlite = $sqlite;
        $this->details = $details;
        
        $this->myMethods         = new ListData();
        $this->myProperties      = new ListData();
        $this->myClassConstants  = new ListData();
        $res = $this->sqlite->query('SELECT * FROM properties WHERE citId = '.$details['id']);
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $this->myProperties[$row['name']] = new PropertyData($this->sqlite, $row);
        }

        $res = $this->sqlite->query('SELECT * FROM methods WHERE citId = '.$details['id']);
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $this->myMethods[$row['name']] = new MethodData($this->sqlite, $row);
        }

        $res = $this->sqlite->query('SELECT * FROM class_constants WHERE citId = '.$details['id']);
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $this->myClassConstants[$row['name']] = new ClassConstantData($this->sqlite, $row);
        }
    }
    
    public function methods($name = null) {
        if ($name === null) {
            return $this->myMethods;
        } else {
            return $this->myMethods[$name];
        }
    }

    public function properties($name = null) {
        if ($name === null) {
            return $this->myProperties;
        } else {
            return $this->myProperties[$name];
        }
    }

    public function class_constants($name = null) {
        if ($name === null) {
            return $this->myClassConstants;
        } else {
            return $this->myClassConstants[$name];
        }
    }
}
?>