<?php

namespace Phpjuicer;

class Evolution {
    private $databaseName = '';
    private $sqlite = null;
    
    public function __construct($databaseName) {
        $this->databaseName = $databaseName;
    }

    public function run() {
        $this->sqlite = new \Sqlite3($this->databaseName.'.sqlite');

        $res = $this->sqlite->query("SELECT * FROM versions ORDER BY version");
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $versions[] = $row;
        }

        $nb = count($versions) - 1;
        for($i = 0; $i < $nb; ++$i) {
            $this->displayDiff($versions[$i], $versions[$i + 1]);
        }
    }
        
    function displayDiff($versionA, $versionB) {
        print "From $versionA[tag] to $versionB[tag]\n";

        // Namespace collection
        $query = <<<SQL
    SELECT namespaces.name AS name FROM namespaces
    JOIN versions AS versionsA
        ON namespaces.versionId = versionsA.id AND
           versionsA.version = "$versionA[version]"
SQL;
        $res = $this->sqlite->query($query);

        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $namespacesA[] = $row['name'];
        }

        // Cit collection
        $query = <<<SQL
    SELECT namespaces.name AS name FROM namespaces
    JOIN versions AS versionsB
        ON namespaces.versionId = versionsB.id AND
           versionsB.version = "$versionB[version]"
SQL;
        $res = $this->sqlite->query($query);
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $namespacesB[] = $row['name'];
        }

        $this->displayNCR($namespacesA, $namespacesB, 'namespace');
    
        // Cit collection
        $query = <<<SQL
    SELECT namespacesA.name || "\\" || cit.name AS name, type FROM cit
    JOIN namespaces AS namespacesA
        ON cit.namespaceId = namespacesA.id
    JOIN versions AS versionsA
        ON namespacesA.versionId = versionsA.id AND
           versionsA.version = "$versionA[version]"
SQL;
        $res = $this->sqlite->query($query);

        $citA = array('array' => array(),
                      'interface' => array(),
                      'trait'    => array());
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $citA[$row['type']][] = $row['name'];
        }

        // Cit collection
        $query = <<<SQL
    SELECT namespacesA.name || "\\" || cit.name AS name, type FROM cit
    JOIN namespaces AS namespacesA
        ON cit.namespaceId = namespacesA.id
    JOIN versions AS versionsA
        ON namespacesA.versionId = versionsA.id AND
           versionsA.version = "$versionB[version]"
SQL;
        $res = $this->sqlite->query($query);

        $citB = array('array' => array(),
                      'interface' => array(),
                      'trait'    => array());
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $citB[$row['type']][] = $row['name'];
        }
    
        $this->displayNCR($citA['class'], $citB['class'], 'classe');
        $this->displayNCR($citA['trait'], $citB['trait'], 'trait');
        $this->displayNCR($citA['interface'], $citB['interface'], 'interface');
    
        $methodsA = $this->getMethods($versionA['version']);
        $methodsB = $this->getMethods($versionB['version']);
        $this->displayNCR($methodsA, $methodsB, 'method');

        $methodArgumentsA = $this->getMethodArguments($versionA['version']);
        $methodArgumentsB = $this->getMethodArguments($versionB['version']);
        $this->displayNCR($methodArgumentsA, $methodArgumentsB, 'method argument');

        $classConstantsA = $this->getClassConstants($versionA['version']);
        $classConstantsB = $this->getClassConstants($versionB['version']);
        $this->displayNCR($classConstantsA, $classConstantsB, 'class constant');

        $propertyA = $this->getProperties($versionA['version']);
        $propertyB = $this->getProperties($versionB['version']);
        $this->displayNCR($propertyA, $propertyB, 'property');
    
        print PHP_EOL.PHP_EOL;
    }

    function displayNCR($A, $B, $type = '') {
        if (empty($A) && empty($B)) {
            $new = '';
            $common = 0;
            $removed = 0;
        } else {
            $diff = array_diff($B, $A);
            if (empty($diff) ) {
                $new = 0;
            } else {
                $new = count($diff);
            }
    
            $diff = array_diff($A, $B);
            if (empty($diff) ) {
                $removed = 0;
            } else {
                $removed = -count($diff);
            }
    
            $diff = array_intersect($A, $B);
            if (empty($diff) ) {
                $common = 0;
            } else {
                $common = count($diff);
            }
        }

        echo sprintf("% -20s|% -9d|% -9d|% -9d|\n", $type, $new, $common, $removed);

    }

    function getMethods($version) {
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

        $methods = array();
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $methods[] = $row['name'];
        }
    
        return $methods;
    }

    function getMethodArguments($version) {
        // Method argument collection
        $query = <<<SQL
    SELECT argumentsMethods.name AS name FROM argumentsMethods
    JOIN methods AS methodsA
        ON argumentsMethods.methodId = methodsA.id
    JOIN cit AS citA
        ON methodsA.citId = citA.id
    JOIN namespaces AS namespacesA
        ON citA.namespaceId = namespacesA.id
    JOIN versions AS versionsA
        ON namespacesA.versionId = versionsA.id AND
           versionsA.version = "$version"
SQL;
        $res = $this->sqlite->query($query);

        $methods = array();
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $methods[] = $row['name'];
        }
    
        return $methods;
    }

    function getClassConstants($version) {
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

        $classconstants = array();
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $classconstants[] = $row['name'];
        }
    
        return $classconstants;
    }

    function getProperties($version) {
        $query = <<<SQL
    SELECT namespacesA.name || "\\" || cit.name AS name, type FROM cit
    JOIN namespaces AS namespacesA
        ON cit.namespaceId = namespacesA.id
    JOIN versions AS versionsA
        ON namespacesA.versionId = versionsA.id AND
           versionsA.version = "$version"
SQL;
        $res = $this->sqlite->query($query);

        $properties = array();
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $properties[] = $row['name'];
        }
    
        return $properties;
    }
}
?>