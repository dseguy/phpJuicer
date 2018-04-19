<?php

namespace Phpjuicer\Data;

class NamespaceData extends Data {
    private $sqlite = null;
    private $details = null;

    private $myClasses    = null;
    private $myInterfaces = null;
    private $myTraits     = null;
    private $myConstants  = null;
    private $myFunctions  = null;

    public function __construct($sqlite, $details) {
        $this->sqlite = $sqlite;
        $this->details = $details;
        
        $this->myClasses    = new ListData();
        $this->myInterfaces = new ListData();
        $this->myTraits     = new ListData();
        $res = $this->sqlite->query('SELECT * FROM cit WHERE namespaceId = '.$details['id']);
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            if ($row['type'] === 'class') {
                $this->myClasses[$row['name']] = new ClassData($this->sqlite, $row);
            } elseif ($row['type'] === 'interface') {
                $this->myInterfaces[$row['name']] = new InterfaceData($this->sqlite, $row);
            } elseif ($row['type'] === 'trait') {
                $this->myTraits[$row['name']] = new TraitData($this->sqlite, $row);
            } 
        }

        $this->myConstants    = new ListData();
        $res = $this->sqlite->query('SELECT * FROM constants WHERE namespaceId = '.$details['id']);
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $this->myConstants[$row['name']] = new ConstantData($this->sqlite, $row);
        }

        $this->myFunctions    = new ListData();
        $res = $this->sqlite->query('SELECT * FROM functions WHERE namespaceId = '.$details['id']);
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $this->myFunctions[$row['name']] = new FunctionsData($this->sqlite, $row);
        }
    }
    
    public function classes($name = null) {
        if ($name === null) {
            return $this->myClasses;
        } else {
            return $this->myClasses[$name];
        }
    }

    public function interfaces($name = null) {
        if ($name === null) {
            return $this->myInterfaces;
        } else {
            return $this->myInterfaces[$name];
        }
    }

    public function traits($name = null) {
        if ($name === null) {
            return $this->myTraits;
        } else {
            return $this->myTraits[$name];
        }
    }

    public function constants($name = null) {
        if ($name === null) {
            return $this->myClasses;
        } else {
            return $this->myClasses[$name];
        }
    }

    public function functions($name = null) {
        if ($name === null) {
            return $this->myFunctions;
        } else {
            return $this->myFunctions[$name];
        }
    }
}
?>