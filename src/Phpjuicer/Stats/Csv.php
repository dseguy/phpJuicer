<?php

namespace Phpjuicer\Stats;

use Phpjuicer\Data;

class Csv {
    private $data = null;
    
    public function __construct($data) {
        $this->data = $data;
    }

    public function render() {
        $rows = array(array('Version', 
                            'Namespaces', 
                            'Classes', 
                            'Interfaces', 
                            'Traits', 
                            'Functions', 
                            'Constants',
                            'Class Constants', 
                            'Properties', 
                            'Methods', 
                            ), 
                        );

        foreach($this->data->versions()->list() as $version) {
            $row   = array($version);
            $row[] = $this->data->versions($version)->namespaces()->count()                              ;
            $row[] = $this->data->versions($version)->namespaces()->classes()->count()                   ;
            $row[] = $this->data->versions($version)->namespaces()->interfaces()->count()                ;
            $row[] = $this->data->versions($version)->namespaces()->traits()->count()                    ;
            $row[] = $this->data->versions($version)->namespaces()->constants()->count()                 ;
            $row[] = $this->data->versions($version)->namespaces()->functions()->count()                 ;

            $row[] = $this->data->versions($version)->namespaces()->classes()->class_constants()->count();
            $row[] = $this->data->versions($version)->namespaces()->classes()->properties()->count()     ;
            $row[] = $this->data->versions($version)->namespaces()->classes()->methods()->count()        ;

            $rows[] = $row;
        }
        
        $rows = array_map(function($r) { return '"'.implode('", "', $r).'"';}, $rows);
        $rows = implode(PHP_EOL, $rows).PHP_EOL;

        print $rows;
    }
}
?>