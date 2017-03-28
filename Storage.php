<?php

class Storage {
    private $sqlite = null;
    private $tables = array('components', 'classes', 'interfaces', 'namespaces', 'releases' ,'traits', 'deprecated');
    
    public function __construct($path) {
        $this->sqlite = new \Sqlite3($path);
    }
    
    public function saveComponent($component) {
        $this->sqlite->query('INSERT INTO components VALUES (null, "'.$component.'")');
        return $this->sqlite->lastInsertRowID();
    }

    public function init() {
        $queries = array(
'DROP TABLE IF EXISTS "classes"',
'CREATE TABLE "classes" (
	 "id" integer PRIMARY KEY AUTOINCREMENT,
	 "class" text,
	 "namespace_id" integer
)',
'DROP TABLE IF EXISTS "components"',
'CREATE TABLE "components" (
	 "id" integer NOT NULL,
	 "component" text,
	PRIMARY KEY("id")
)',
'DROP TABLE IF EXISTS "deprecated"',
'CREATE TABLE "deprecated" (
	 "id" integer NOT NULL,
	 "namespace_id" integer,
	 "type" varchar,
	 "cit" varchar,
	 "name" varchar,
	PRIMARY KEY("id"),
	CONSTRAINT "release" FOREIGN KEY ("namespace_id") REFERENCES "namespaces" ("id")
)',
'DROP TABLE IF EXISTS "interfaces"',
'CREATE TABLE "interfaces" (
	 "id" integer PRIMARY KEY AUTOINCREMENT,
	 "interface" text,
	 "namespace_id" integer
)',
'DROP TABLE IF EXISTS "namespaces"',
'CREATE TABLE "namespaces" (
	 "id" integer PRIMARY KEY AUTOINCREMENT,
	 "namespace" text,
	 "release_id" integer
)',
'DROP TABLE IF EXISTS "releases"',
'CREATE TABLE "releases" (
	 "id" integer PRIMARY KEY AUTOINCREMENT,
	 "release" text,
	 "component_id" integer,
	CONSTRAINT "component" FOREIGN KEY ("component_id") REFERENCES "components" ("id")
)',
'DROP TABLE IF EXISTS "traits"',
'CREATE TABLE "traits" (
	 "id" integer PRIMARY KEY AUTOINCREMENT,
	 "trait" text,
	 "namespace_id" integer
)'
    );
    
        foreach($queries as $query) {
            $this->sqlite->query($query);
        }
    }
    
    public function save($table, $value, $parentId = null) {
//        $res = $this->sqlite->querySingle('SELECT * FROM '.$table.' WHERE '.$where);

//        if (!empty($res)) {
//            return $res->fetchArray(SQLITE3_NUM)[0];
//        }

        $cols = array('id');
        $values = array('null');
        
        $cols = join(', ', $cols);
        $values = join(', ', $values);
        if ($parentId !== null) {
            $parentId = ', '.$parentId;
        }
        $query = "INSERT INTO $table VALUES (null, '$value' $parentId)";
        $this->sqlite->query($query);
        
        return $this->sqlite->lastInsertRowID();
    }
}

?>