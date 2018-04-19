<?php

namespace Phpjuicer\Data;

class InterfaceData extends Data {
    private $sqlite = null;
    private $details = null;

    public $methods = array();

    public function __construct($sqlite, $details) {
        $this->sqlite = $sqlite;
        $this->details = $details;
        
        /*
        $res = $this->sqlite->query('SELECT * FROM namespaces WHERE versionId = '.$details['id']);
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            if ($row['type'] === 'class') {
                $this->myClasses[$row['name']] = new ClassData($this->sqlite, $row);
                $this->classes[] = $row['name'];
            }
        }
        */
    }
    
    public function methods($name = null) {
        return $this->get($this->myMethods, $name);
    }
}
?>