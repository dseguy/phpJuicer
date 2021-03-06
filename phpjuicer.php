#!/usr/bin/php
<?php

spl_autoload_register('phpjuicer_autoloader');


if (!isset($argv[1])) {
    $version = new Phpjuicer\Version();
    $version->run();
} elseif ($argv[1] === 'extract') {
    $extractor = new Phpjuicer\Extractor($argv[2], $argv[3]);
    $extractor->run();
} elseif ($argv[1] === 'stats') {
    $stats = new Phpjuicer\Stats($argv[2]);
    $stats->run();
} elseif ($argv[1] === 'diff') {
    $evolution = new Phpjuicer\Diff($argv[2], $argv[3], $argv[4]);
    $evolution->run();
} elseif ($argv[1] === 'list') {
    $evolution = new Phpjuicer\ListVersions($argv[2]);
    $evolution->run();
} else {
    $version = new Phpjuicer\Version();
    $version->run();
}

function phpjuicer_autoloader($class) {
    include 'src/' . str_replace('\\', '/', $class) . '.php';
}

?>