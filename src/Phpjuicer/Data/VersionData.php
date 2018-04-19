<?php

namespace Phpjuicer\Data;

class VersionData extends Data {
    private $sqlite = null;
    private $details = null;
    private $myNamespaces = null;

    public function __construct($sqlite, $details) {
        $this->sqlite = $sqlite;
        
        $this->myNamespaces = new ListData();
        $res = $this->sqlite->query('SELECT * FROM namespaces WHERE versionId = '.$details['id']);
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $this->myNamespaces[$row['name']] = new NamespaceData($this->sqlite, $row);
        }
    }
    
    public function namespaces($name = null) {
        if ($name === null) {
            return $this->myNamespaces;
        } else {
            return $this->myNamespaces[$name];
        }
    }
}
?>