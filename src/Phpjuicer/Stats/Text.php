<?php

namespace Phpjuicer\Stats;

class Text {
    private $data = null;
    
    public function __construct($data) {
        $this->data = $data;
    }

    public function render() {
        $text = '';

        foreach($this->data->versions()->run() as $version) {
            $text .= "Version $version[tag]\n";
            $text .= $this->data->versions($version['id'])->namespaces()->count().' namespaces'.PHP_EOL;
            $text .= $this->data->versions($version['id'])->classes()->count().' classes'.PHP_EOL;
            $text .= $this->data->versions($version['id'])->interfaces()->count().' interfaces'.PHP_EOL;
            $text .= $this->data->versions($version['id'])->traits()->count().' traits'.PHP_EOL;
            $text .= $this->data->versions($version['id'])->class_constants()->count().' class constants'.PHP_EOL;
            $text .= $this->data->versions($version['id'])->properties()->count().' properties'.PHP_EOL;
            $text .= $this->data->versions($version['id'])->methods()->count().' methods'.PHP_EOL;
            $text .= $this->data->versions($version['id'])->functions()->count().' functions'.PHP_EOL;
            $text .= $this->data->versions($version['id'])->constants()->count().' constants'.PHP_EOL;
            $text .= PHP_EOL;
        }
        
        print $text;
    }
}
?>