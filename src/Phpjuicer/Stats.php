<?php

namespace Phpjuicer;

use Phpjuicer\Data;
use Phpjuicer\Stats\Csv;
use Phpjuicer\Stats\Text;

class Stats {
    private $databaseName = '';
    private $sqlite = null;
    
    public function __construct($databaseName) {
        $this->databaseName = $databaseName;
    }

    public function run() {
        $this->sqlite = new \Sqlite3($this->databaseName.'.sqlite');
        $data = new Data($this->sqlite);
        
//        $render = new Text($data);
        $render = new Csv($data);
        $render->render();
    }
}
?>