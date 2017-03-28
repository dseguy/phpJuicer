<?php

class Vcs {
    private $path = null;
    private $tags = array();
    
    public function __construct($path) {
        $this->path = $path;
        
        if (!file_exists($path.'/.git')) {
            throw new \Exception('No git in this code');
        }
    }
    
    public function getVersions() {
        $res = shell_exec('cd '.$this->path.'; git fetch --all --quiet ; git tag -l');
        $versions = explode("\n", trim($res));
        
        $versions = array_filter($versions, function($x) { return preg_match('/\d+\.\d+.0$/', $x); });
        print_r($versions);

        foreach($versions as $version) {
            preg_match('/(\d+\.\d+.0)$/', $version, $r);
            $this->tags[substr($r[1], 0, 3)] = $version;
        }
        
        return array_keys($this->tags);
    }

    public function checkOut($version) {
        $res = shell_exec('cd  '.$this->path.'; git checkout --quiet '.$this->tags[$version]);

        $directory = new RecursiveDirectoryIterator($this->path);
        $iterator = new RecursiveIteratorIterator($directory);
        $files = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::MATCH);
        
        return $files;
    }
}