<?php

namespace Phpjuicer;

use Phpjuicer\Stats\Data;
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
        /*

        $res = $this->sqlite->query("SELECT * FROM versions ORDER BY version");
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $versions[] = $row;
        }
        
        $nb = count($versions);
        for($i = 0; $i < $nb; ++$i) {
            $cits[$versions[$i]['tag']] = $this->collectVersion($versions[$i]['version']);
        }
        
        $this->displayText($cits);
//        $this->displayCSV($cits);
        */
    }

    function collectVersion($version) {
        $cit = array('namespace'      => array(),
                     'class'          => array(),
                     'interface'      => array(),
                     'trait'          => array(),
                     'function'       => array(),
                     'method'         => array(),
                     'class_constant' => array(),
                     'property'       => array(),
                     );

        // Namespace collection
        $query = <<<SQL
    SELECT namespacesA.name AS name FROM namespaces AS namespacesA
    JOIN versions AS versionsA
        ON namespacesA.versionId = versionsA.id AND
           versionsA.version = "$version"
SQL;
        $res = $this->sqlite->query($query);

        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $cit['namespace'][] = $row['name'];
        }
        
        // Cit collection
        $query = <<<SQL
    SELECT namespacesA.name || "\\" || cit.name AS name, type FROM cit
    JOIN namespaces AS namespacesA
        ON cit.namespaceId = namespacesA.id
    JOIN versions AS versionsA
        ON namespacesA.versionId = versionsA.id AND
           versionsA.version = "$version"
SQL;
        $res = $this->sqlite->query($query);

        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $cit[$row['type']][] = $row['name'];
        }

        // Class Constant collection
        $query = <<<SQL
    SELECT namespacesA.name || "\\" || citA.name || "::" || class_constants.name AS name FROM class_constants
    JOIN cit AS citA
        ON class_constants.citId = citA.id
    JOIN namespaces AS namespacesA
        ON citA.namespaceId = namespacesA.id
    JOIN versions AS versionsA
        ON namespacesA.versionId = versionsA.id AND
           versionsA.version = "$version"
SQL;
        $res = $this->sqlite->query($query);

        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $cit['class_constant'][] = $row['name'];
        }

        // Method collection
        $query = <<<SQL
    SELECT namespacesA.name || "\\" || citA.name || "::" || methods.name AS name FROM methods
    JOIN cit AS citA
        ON methods.citId = citA.id
    JOIN namespaces AS namespacesA
        ON citA.namespaceId = namespacesA.id
    JOIN versions AS versionsA
        ON namespacesA.versionId = versionsA.id AND
           versionsA.version = "$version"
SQL;
        $res = $this->sqlite->query($query);

        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $cit['method'][] = $row['name'];
        }
    
        // Property collection
        $query = <<<SQL
    SELECT namespacesA.name || "\\" || citA.name || "::" || properties.name AS name FROM properties
    JOIN cit AS citA
        ON properties.citId = citA.id
    JOIN namespaces AS namespacesA
        ON citA.namespaceId = namespacesA.id
    JOIN versions AS versionsA
        ON namespacesA.versionId = versionsA.id AND
           versionsA.version = "$version"
SQL;
        $res = $this->sqlite->query($query);

        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $cit['property'][] = $row['name'];
        }
    
        // Constant collection
        // Omitted at the moment

        // Functions collection
        $query = <<<SQL
SELECT namespacesA.name || "\\" || functions.name AS name FROM functions
JOIN namespaces AS namespacesA
    ON functions.namespaceId = namespacesA.id
JOIN versions AS versionsA
    ON namespacesA.versionId = versionsA.id AND
       versionsA.version = "$version"
SQL;
        $res = $this->sqlite->query($query);

        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $cit['function'][] = $row['name'];
        }
    
        return $cit;
    }

    function displayText($cits) {
        foreach($cits as $version => $cit) {
            print "Version $version\n";
            print count($cit['namespace']).' namespaces'.PHP_EOL;
            print count($cit['class']).' classes'.PHP_EOL;
            print count($cit['interface']).' interfaces'.PHP_EOL;
            print count($cit['trait']).' traits'.PHP_EOL;
            print count($cit['class_constant']).' class constants'.PHP_EOL;
            print count($cit['property']).' properties'.PHP_EOL;
            print count($cit['method']).' methods'.PHP_EOL;
            print count($cit['function']).' functions'.PHP_EOL;
            print PHP_EOL;
        }
    }

    function displayCSV($cits) {
        foreach($cits as $version => &$cit) {
            foreach($cit as $type => &$c) { 
                $c = count($c);
            }
            $cit['version'] = $version;
        }

        $cits = $this->transpose($cits);
    
    //    $first = next($cits);
    //    print "version\t".implode("\t", array_keys($cit)).PHP_EOL;
        foreach($cits as $version => $cit) {
            print $version."\t".implode("\t", array_values($cit)).PHP_EOL;
        }
    }

    function transpose($array) {
        $return = array();
        foreach($array as $x => $cols) {
            foreach($cols as $y => $v) {
                $return[$y][$x] = $v;
            }
        }
    
        return $return;
    }
}
?>