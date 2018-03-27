<?php

namespace Phpjuicer\Stats;

class Csv {
    private $data = null;
    
    public function __construct($data) {
        $this->data = $data;
    }

    public function render() {
        $rows = array(array('Version', 'Namespaces', 'Classes', 'Interfaces', 'Traits', 'Class Constants', 'Properties', 'Methods', 'Functions', 'Constants'), );

        foreach($this->data->versions()->run() as $version) {
            $row   = array($version[tag]);
            $row[] = $this->data->versions($version['id'])->namespaces()->count();
            $row[] = $this->data->versions($version['id'])->classes()->count();
            $row[] = $this->data->versions($version['id'])->interfaces()->count();
            $row[] = $this->data->versions($version['id'])->traits()->count();
            $row[] = $this->data->versions($version['id'])->class_constants()->count();
            $row[] = $this->data->versions($version['id'])->properties()->count();
            $row[] = $this->data->versions($version['id'])->methods()->count();
            $row[] = $this->data->versions($version['id'])->functions()->count();
            $row[] = $this->data->versions($version['id'])->constants()->count();
            
            $rows[] = $row;
        }
        
        $rows = array_map(function($r) { return '"'.join('", "', $r).'"';}, $rows);
        $rows = implode(PHP_EOL, $rows).PHP_EOL;

        print $rows;
    }
}
?>