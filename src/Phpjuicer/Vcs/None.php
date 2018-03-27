<?php

namespace PhpJuicer\Vcs;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use RecursiveRegexIterator;

class None {
    private $path = null;
    private $tags = array();
    
    public function __construct($path) {
        $this->path = $path;
    }
    
    public function getVersions() {
        return array('standalone');
    }

    public function checkOut($version) {
        $directory = new RecursiveDirectoryIterator($this->path);
        $iterator = new RecursiveIteratorIterator($directory);
        $files = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::MATCH);
        
        return $files;
    }
    
    public function getTags() {
        return array('standalone');
    }
}