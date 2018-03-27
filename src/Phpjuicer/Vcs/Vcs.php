<?php

namespace Phpjuicer\Vcs;

class Vcs {
    public static function factory($path) {
        if (file_exists("$path/.git")) {
            return new Git($path);
        } else {
            return new None($path);
        }
    }
}