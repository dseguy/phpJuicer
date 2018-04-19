<?php

namespace Phpjuicer;

class Version {
    public function __construct() {
    
    }

    public function run() {
        print "PHP Juicer version ".Phpjuicer::VERSION."\n";

        if (version_compare('7.0.0', PHP_VERSION) > 0){
            print "Warning : phpJuicer requires PHP 7.0 and more recent. '".PHP_VERSION."' was provided.\n";
        }
        
        if (!extension_loaded('sqlite3')){
            print "Warning : phpJuicer needs ext/sqlite3. Please, install it in your PHP : see http://www.php.net/sqlite3.\n";
        }

        if (!extension_loaded('ast')){
            print "Warning : phpJuicer needs ext/ast. Please, install it in your PHP : see https://pecl.php.net/package/ast.\n";
        }
    }
}
?>