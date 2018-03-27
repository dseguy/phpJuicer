<?php

namespace Phpjuicer\Stats;

class Data {
    private $sqlite = null;
    
    private $tables = array('versions',
                            'namespaces',
                            'classes',
                            'interfaces',
                            'traits',
                            'class_constants',
                            'properties',
                            'methods',
                            'functions',
                            'constants',
                           );

    private $cols = array('tag',
                          'version',
                           );
                           
    private $joins = array('versions'   => array('namespaces'      => ' JOIN namespaces ON versions.id = namespaces.versionId ',),
                           'namespaces' => array('classes'         => ' JOIN cit ON namespaces.id = cit.namespaceId AND type="class" ',
                                                 'interfaces'      => ' JOIN cit ON namespaces.id = cit.namespaceId AND type="interface" ',
                                                 'traits'          => ' JOIN cit ON namespaces.id = cit.namespaceId AND type="trait" ',
                                                 'functions'       => ' JOIN functions ON namespaces.id = functions.namespaceId ',
                                                 'constants'       => ' JOIN constants ON namespaces.id = constants.namespaceId ',
                                               ),
                           'classes'    => array('class_constants' => ' JOIN class_constants ON cit.id = class_constants.citId AND (cit.type="class" AND cit.type = "interface")',
                                                 'properties'      => ' JOIN properties ON cit.id = properties.citId AND (cit.type="class" OR cit.type = "trait")',
                                                 'methods'         => ' JOIN methods ON cit.id = methods.citId ',
                                                 'use_trait'       => ' JOIN use_trait ON cit.id = use_trait.citId ',
                                                ),
                            );

    private $table = '';
    private $column = array();
    private $join = array();
    private $where = array();

    public function __construct($sqlite) {
        $this->sqlite = $sqlite;
    }
    
    public function __call($name, $args) {
        assert(in_array($name, $this->tables), "$name is not a SQL table\n");
        
        if (empty($this->table) ) {
            $this->table = $name;
        } else {
            $this->join[] = $this->getJoins($this->table, $name);
        }
        
        if (!empty($args)) {
            $this->where[] = $name.'.id = '.$args[0];
        }
        
        return $this;
    }
    
    private function getJoins($origin, $destination) {
        assert(in_array($origin, $this->tables), "$origin is not a SQL table\n");
        assert(in_array($destination, $this->tables), "$destination is not a SQL table\n");

        $joins = array();
        
        if (!isset($this->joins[$origin])) {
            return '';
        }
        
        if (isset($this->joins[$origin][$destination])) {
            return $this->joins[$origin][$destination];
        }
        
        foreach($this->joins[$origin] as $middleman => $join) {
            $second = $this->getJoins($middleman, $destination);
            if (!empty($second)) {
                return $join.' '.$second;
            }
        }
        
        print "$origin - $destination\n";
        die(__METHOD__);
    }

    public function __get($name) {
        assert(in_array($name, $this->cols), "$name is not a SQL column\n");
        
        $this->column[] = $name;
        
        return $this->run();
    }
    
    public function count() {
        $this->column = ['COUNT(*)'];
        
        $return = $this->run();
        return $return[0];
    }
    
    public function run() {
        $query = 'SELECT '.(empty($this->column) ? '*' : join(', ', $this->column)).' FROM '.$this->table;

        if (!empty($this->join)) {
            $query .= join(' AND ', $this->join);
        }

        if (!empty($this->where)) {
            $query .= ' WHERE '.join(' AND ', $this->where);
        }

        
        print "Query : $query\n";
        $res = $this->sqlite->query($query);
        $return = array();
        while($row = $res->fetchArray(\SQLITE3_ASSOC)) {
            $return[] = $row;
        }

        if (count($this->column) === 1) {
            $return = array_column($return, array_pop($this->column));
        }
        
        $this->table = '';
        $this->column = array();
        $this->join = array();
        $this->where = array();
        
        return $return;
    }
}
?>