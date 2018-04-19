<?php

namespace Phpjuicer;

use Phpjuicer\Data\VersionData;
use Phpjuicer\Data\ListData;

class Data {
    private $sqlite = null;
    private $myVersions = null;
    
    public function __construct($sqlite) {
        $this->sqlite = $sqlite;
        
        $this->myVersions = new ListData();
        $res = $this->sqlite->query('SELECT * FROM versions');
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $this->myVersions[$row['tag']] = new Data\VersionData($this->sqlite, $row);
        }
    }
    
    public function versions($version = null) {
        if ($version === null) {
            return $this->myVersions;
        } else {
            return $this->myVersions[$version];
        }
    }
}
?>