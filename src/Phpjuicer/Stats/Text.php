<?php

namespace Phpjuicer\Stats;

use Phpjuicer\Data;

class Text {
    private $data = null;
    
    public function __construct($data) {
        $this->data = $data;
    }

    public function render() {
        $text = '';

        foreach($this->data->versions()->list() as $version) {
            $text .= "Version $version\n";
            $text .= $this->data->versions($version)->namespaces()->count()                              .' namespaces'.PHP_EOL;
            $text .= $this->data->versions($version)->namespaces()->classes()->count()                   .' classes'.PHP_EOL;
            $text .= $this->data->versions($version)->namespaces()->interfaces()->count()                .' interfaces'.PHP_EOL;
            $text .= $this->data->versions($version)->namespaces()->traits()->count()                    .' traits'.PHP_EOL;
            $text .= $this->data->versions($version)->namespaces()->constants()->count()                 .' constants'.PHP_EOL;
            $text .= $this->data->versions($version)->namespaces()->functions()->count()                 .' functions'.PHP_EOL;
 
            $text .= $this->data->versions($version)->namespaces()->classes()->class_constants()->count().' class constants'.PHP_EOL;
            $text .= $this->data->versions($version)->namespaces()->classes()->properties()->count()     .' properties'.PHP_EOL;
            $text .= $this->data->versions($version)->namespaces()->classes()->methods()->count()        .' methods'.PHP_EOL;

            $text .= PHP_EOL;
        }
        
        print $text;
    }
}
?>