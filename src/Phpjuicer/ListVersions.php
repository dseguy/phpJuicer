<?php

namespace Phpjuicer;

class ListVersions {
    public function __construct($databaseName) {
        $this->databaseName = $databaseName;
    }

    public function run() {
        $this->sqlite = new \Sqlite3($this->databaseName.'.sqlite');
        $data = new Data($this->sqlite);

        $versions = $data->versions()->list();
        
        print implode(',', $versions).PHP_EOL;
    }
}
?>