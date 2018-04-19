<?php

namespace Phpjuicer;

use Phpjuicer\Vcs\Vcs;
use ast;

class Extractor {
    private $databaseName = '';
    private $sqlite = null;
    private $codePath = null;
    private $base = null;
    private $versionId = null;
    private $namespaceId = null;
    
    public function __construct($codePath, $base) {
        $this->codePath = $codePath;
        $this->base = $base;
    }

    public function run() {
        $this->sqlite = new \Sqlite3($this->base.'.sqlite');
        $this->initSqlite();

        $vcs = Vcs::factory($this->codePath);

        $versions = $vcs->getTags();    
        print_r($versions);

        foreach($versions as $version => $tag) {
            print "check version $version\n";
            
            $this->sqlite->query("INSERT INTO versions VALUES (null, \"$version\", \"$tag\")");
            $this->versionId = $this->sqlite->lastInsertRowID();
        
            $this->sqlite->query("INSERT INTO namespaces VALUES (null, '', $this->versionId)");
            $this->namespaceId = $this->sqlite->lastInsertRowID();
        
            $files = $vcs->checkOut($tag);
            
            $total = 0;
            foreach($files as $file) {
                print $file.PHP_EOL;
                ++$total;
                if (preg_match('#/tests?/#i', $file)) { continue; }
                $code = file_get_contents((string) $file);
            
                try {
                    $ast = ast\parse_code($code, 50);
                } catch (\Throwable $e) {
                    print "Parse error in $file. Omitting\n";
                    continue; 
                }
        
                $this->traverseAst($ast->children);
                
                if ($total == 2) {
//                    die();
                }
            }

            print "$version : $total files \n";
        }
    }

        function traverseAst(array $ast) {
            foreach($ast as $node) {
                if ($node->kind === ast\AST_STMT_LIST) {
                    $this->traverseAst($node->children);
                } elseif ($node->kind === ast\AST_CLASS) {
                    $this->traverseClass($node);
                } elseif ($node->kind === ast\AST_FUNC_DECL) {
                    $this->traverseFunction($node);
                } elseif ($node->kind === ast\AST_CONST_DECL) {
                    $this->traverseConstant($node);
                } elseif ($node->kind === ast\AST_NAMESPACE) {
                    $this->traverseNamespace($node);
                } elseif ($node->kind === ast\AST_USE) {
                    $this->traverseUse($node);
                } else {
//                    print 'Default : '.$node->kind.PHP_EOL;
                }
            }
        }

        function traverseExpression($node) {
            if ($node->kind === ast\AST_ARRAY) {
                return $this->traverseArray($node);
            } elseif ($node->kind === ast\AST_ARRAY_ELEM) {
                return $this->traverseArrayElement($node);
            } elseif ($node->kind === ast\AST_BINARY_OP) {
                return $this->traverseBinaryOp($node);
            } else {
//                print 'Default Expression : '.$node->kind.PHP_EOL;
            }
        }

        function traverseClass($class) {
            if ($class->flags & ast\flags\CLASS_INTERFACE) {
                $type = 'interface';
            } elseif ($class->flags & ast\flags\CLASS_TRAIT) {
                $type = 'trait';
            } else {
                $type = 'class';
            }

            $name = $class->children['name'] ?? '';
            $final = (int) (bool) ($class->flags & ast\flags\CLASS_FINAL);
            $abstract = (int) (bool) ($class->flags & ast\flags\CLASS_ABSTRACT);
            if (!empty($class->children['extends'])) {
                $extends = $class->children['extends']->children['name'];
            } else {
                $extends = '';
            }
    
            $this->sqlite->query("INSERT INTO cit VALUES (null, '$name', $final, $abstract, '$type', '$extends', $this->namespaceId)");
            $classId = $this->sqlite->lastInsertRowID();
    
            if (!empty($class->children['implements'])) {
                foreach($class->children['implements']->children as $implements) {
                    $this->sqlite->query("INSERT INTO cit_implements VALUES (null, '$classId', '{$implements->children['name']}')");
                }
            }
    
            if (!empty($class->children['stmts']->children)) {
                $this->traverseStmts($class->children['stmts']->children, $classId);
            }
        }

        function traverseStmts($stmts, $classId) {
            foreach($stmts as $stmt) {
                if ($stmt->flags & ast\flags\MODIFIER_PUBLIC) {
                    $modifier = 'public';
                } elseif ($stmt->flags & ast\flags\MODIFIER_PROTECTED) {
                    $modifier = 'protected';
                } elseif ($stmt->flags & ast\flags\MODIFIER_PRIVATE) {
                    $modifier = 'private';
                } else {
                    $modifier = '';
                }

                $static = (int) (bool) ($stmt->flags & ast\flags\MODIFIER_STATIC);
                $final = (int) (bool) ($stmt->flags & ast\flags\MODIFIER_FINAL);
                $abstract = (int) (bool) ($stmt->flags & ast\flags\MODIFIER_ABSTRACT);
        
                if ($stmt->kind === ast\AST_CLASS_CONST_DECL) {
                    foreach($stmt->children as $declaration) {
                        $name = $declaration->children['name'];
                        if ($declaration->children['value'] instanceof ast\Node) {
                            // TODO
                            $value = '';
                        } else {
                            $value = $declaration->children['value'];
                        }
                        $doccomment = $declaration->children['docComment'];

                        $name = $this->sqlite->escapeString($name);
                        $value = $this->sqlite->escapeString($value);
                        $doccomment = $this->sqlite->escapeString($doccomment);
                        $this->sqlite->query("INSERT INTO class_constants VALUES (null, '$name', '$classId', '$modifier', '$value', '$doccomment')");
                    }
                } elseif ($stmt->kind === ast\AST_METHOD) {
                    $name = $stmt->children['name'];
                    if (is_object($stmt->children['returnType'])) {
                        if (isset($stmt->children['returnType']->children['name'])) {
                           $returntype = $stmt->children['returnType']->children['name'];
                        } else {
                           $returntype = '';
                        }
                    } else {
                        $returntype = '';
                    }
                    $doccomment = $stmt->children['docComment'];
        
                    $name = $this->sqlite->escapeString($name);
                    $doccomment = $this->sqlite->escapeString($doccomment);
                    $this->sqlite->query("INSERT INTO methods VALUES (null, '$name', '$classId', '$static', '$final', '$abstract', '$modifier', '$returntype', '$doccomment')");
            
                    $methodId = $this->sqlite->lastInsertRowID();
                    foreach($stmt->children['params']->children as $param) {
                        $name = $param->children['name'];
                        $typehint = $this->getTypeHint($param->children['type']);
                        if ($param->children['default'] instanceof ast\Node) {
                            // Ignore ATM
                            $value = '';
                        } else {
                            $value = '';
                        }
                
                        $this->sqlite->query("INSERT INTO argumentsMethods VALUES (null, '$name', '$methodId', '$value', '$typehint')");
                    }
                } elseif ($stmt->kind === ast\AST_USE_TRAIT) {
                    foreach($stmt->children['traits']->children as $declaration) {
                        $name = $declaration->children['name'];
                        $this->sqlite->query("INSERT INTO use_trait VALUES (null, '$classId', '$name')");
                    }
                } elseif ($stmt->kind === ast\AST_PROP_DECL) {
                    foreach($stmt->children as $declaration) {
                        $name = $declaration->children['name'];
                        if ($declaration->children['default'] === null) {
                            $value = 'null';
                        } elseif (is_object($declaration->children['default'])) {
                            $value = "'something'";
                        } else {
                            $value = "'".$this->sqlite->escapeString($declaration->children['default'])."'";
                        }
                        $doccomment = $declaration->children['docComment'];

                        $doccomment = $this->sqlite->escapeString($doccomment);
                        $this->sqlite->query("INSERT INTO properties VALUES (null, '$name', '$classId', '$modifier', $value, '$doccomment')");
                    }
                } else {
                    print 'STMT UNKNOWN ' .$stmt->kind.PHP_EOL;
                }
            }
        }

        function traverseFunction($node) {
            $name = $node->children['name'];
            if (is_object($node->children['returnType'])) {
               $returntype = $node->children['returnType']->children['name'];
            } else {
                $returntype = '';
            }
            $doccomment = $node->children['docComment'];

            $name = $this->sqlite->escapeString($name);
            $returntype = $this->sqlite->escapeString($returntype);
            $doccomment = $this->sqlite->escapeString($doccomment);
            $this->sqlite->query("INSERT INTO functions (id, name, returntype, doccomment, namespaceId) VALUES (null, '$name', '$returntype', '$doccomment', $this->namespaceId)");
    
            $functionId = $this->sqlite->lastInsertRowID();
            foreach($node->children['params']->children as $param) {
                $name = $param->children['name'];
                if (is_object($param->children['type'])) {
                    $typehint = $this->getTypeHint($param->children['type']);
                } else {
                    $typehint = '';
                }
                $value = $this->traverseElement($param->children['default']);
                if ($value instanceof ast\Node) {
                    $value = $value;
                }
    
                $this->sqlite->query("INSERT INTO argumentsFunctions VALUES (null, '$name', '$functionId', '$value', '$typehint')");
            }
        }

        function traverseConstant($node) {
            foreach($node->children as $declaration) {
                $name = $declaration->children['name'];
                $value = $declaration->children['value'];
                $doccomment = $declaration->children['docComment'];
                $this->sqlite->query("INSERT INTO constants VALUES (null, '$name','$value', '$doccomment', $this->namespaceId)");
            }
        }

        function traverseNamespace($node) {
            $res = $this->sqlite->query("SELECT id FROM namespaces WHERE name='{$node->children['name']}' and versionId='$this->versionId'");
            if ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                $this->namespaceId = $row['id'];
            } else {
                $this->sqlite->query("INSERT INTO namespaces VALUES (null, '{$node->children['name']}', '$this->versionId')");
    
                $this->namespaceId = $this->sqlite->lastInsertRowID();
            }
            
            if (isset($node->children['stmts']->children)) {
                $this->traverseAst($node->children['stmts']->children);
            }
        }

        private function traverseArrayElement($node) {
            return $this->traverseElement($node);
        }

        private function traverseElement($node) {
            if ($node instanceof ast\Node) {
                return $this->traverseExpression($node->children['value']);
            } else {
                return $node;
            }
        }
        
        private function traverseBinaryOp($node) {
            $operators = array(
                ast\flags\BINARY_BITWISE_OR     => '|',
                ast\flags\BINARY_BITWISE_AND    => '&',
                ast\flags\BINARY_BITWISE_XOR    => '^',
                ast\flags\BINARY_CONCAT         => '.',
                ast\flags\BINARY_ADD            => '+',
                ast\flags\BINARY_SUB            => '-',
                ast\flags\BINARY_MUL            => '*',
                ast\flags\BINARY_DIV            => '/',
                ast\flags\BINARY_MOD            => '%',
                ast\flags\BINARY_POW            => '**',
                ast\flags\BINARY_SHIFT_LEFT     => '<<',
                ast\flags\BINARY_SHIFT_RIGHT    => '>>',
         );
            
            return $this->traverseElement($node->children['left']) .
                   ' '.$operators[$node->flags].' ' .
                   $this->traverseElement($node->children['right']);
        }        
        
        private function traverseArray($node) {
            $args = array();
            foreach($node->children as $child) {
                $args[] = $this->traverseExpression($child);
            }
            
            $return = implode(', ', $args);
            
            if ($node->flags === ast\flags\ARRAY_SYNTAX_SHORT) {
                $return = '['.$return.']';
            } else {
                $return = 'array('.$return.')';
            }
            
            return $return;
        }

        function traverseUse($node) {
            if (!isset($uses)) {
                $uses = array(ast\flags\USE_CONST => array(),
                              ast\flags\USE_FUNCTION => array(),
                              ast\flags\USE_NORMAL => array(),
                            );
            }
    
            foreach($node->children as $children) {
                $uses[$node->flags] = $uses[$node->flags] + $this->traverseAlias($children);
            }
        }

        function traverseAlias($node) {
            if (isset($node->children['alias'])) {
                return [$node->children['alias'] => $node->children['name']];
            } else {
                $x = explode('\\', $node->children['name']);
                $alias = array_pop($x);
                return [$alias => $node->children['name']];
            }
        }

        function initSqlite() {
                $this->sqlite->query('DROP TABLE IF EXISTS versions');
                $this->sqlite->query('CREATE TABLE versions ( id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                        version TEXT,
                                                        tag TEXT
                                                       )');
                $this->sqlite->query('DROP TABLE IF EXISTS namespaces');
                $this->sqlite->query('CREATE TABLE namespaces ( id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                          name TEXT,
                                                          versionId INTEGER REFERENCES versions(id) ON DELETE CASCADE
                                                  )');

                $this->sqlite->query('DROP TABLE IF EXISTS cit');
                $this->sqlite->query('CREATE TABLE cit (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                    name TEXT,
                                                    abstract INTEGER,
                                                    final INTEGER,
                                                    type TEXT,
                                                    extends TEXT DEFAULT "",
                                                    namespaceId INTEGER DEFAULT 1  REFERENCES namespaces(id) ON DELETE CASCADE
                                                  )');

                $this->sqlite->query('DROP TABLE IF EXISTS cit_implements');
                $this->sqlite->query('CREATE TABLE cit_implements (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                               citId INTEGER  REFERENCES cit(id) ON DELETE CASCADE,
                                                               implements TEXT
                                                         )');

                $this->sqlite->query('DROP TABLE IF EXISTS class_constants');
                $this->sqlite->query('CREATE TABLE class_constants ( id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                               name TEXT,
                                                               citId INTEGER REFERENCES cit(id) ON DELETE CASCADE,
                                                               visibility TEXT,
                                                               value TEXT,
                                                               doccomment TEXT
                                                         )');

                // Methods
                $this->sqlite->query('DROP TABLE IF EXISTS methods');
                $this->sqlite->query('CREATE TABLE methods (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                        name INTEGER,
                                                        citId INTEGER REFERENCES cit(id) ON DELETE CASCADE,
                                                        static INTEGER,
                                                        final INTEGER,
                                                        abstract INTEGER,
                                                        visibility TEXT,
                                                        returntype TEXT,
                                                        doccomment TEXT
                                                         )');
                $this->sqlite->query('DROP TABLE IF EXISTS argumentsMethods');
                $this->sqlite->query('CREATE TABLE argumentsMethods ( id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                                name TEXT,
                                                                methodId INTEGER REFERENCES methods(id) ON DELETE CASCADE,
                                                                value INTEGER,
                                                                typehint INTEGER
                                                         )');

                $this->sqlite->query('DROP TABLE IF EXISTS functions');
                $this->sqlite->query('CREATE TABLE functions (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                          name TEXT,
                                                          returntype TEXT,
                                                          doccomment TEXT,
                                                          namespaceId INTEGER REFERENCES namespaces(id) ON DELETE CASCADE
                                                         )');

                $this->sqlite->query('DROP TABLE IF EXISTS argumentsFunctions');
                $this->sqlite->query('CREATE TABLE argumentsFunctions ( id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                                  name INTEGER,
                                                                  functionId INTEGER REFERENCES functions(id) ON DELETE CASCADE,
                                                                  value INTEGER,
                                                                  typehint INTEGER
                                                         )');

                $this->sqlite->query('DROP TABLE IF EXISTS use_trait');
                $this->sqlite->query('CREATE TABLE use_trait (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                          citId INTEGER REFERENCES cit(id) ON DELETE CASCADE,
                                                          trait TEXT
                                                         )');

                $this->sqlite->query('DROP TABLE IF EXISTS properties');
                $this->sqlite->query('CREATE TABLE properties ( id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                          name TEXT,
                                                          citId INTEGER REFERENCES cit(id) ON DELETE CASCADE,
                                                          visibility TEXT,
                                                          value TEXT,
                                                          doccomment TEXT
                                                         )');

                $this->sqlite->query('DROP TABLE IF EXISTS constants');
                $this->sqlite->query('CREATE TABLE constants ( id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                         name TEXT,
                                                         value TEXT,
                                                         doccomment TEXT,
                                                         namespaceId INTEGER DEFAULT 1  REFERENCES namespaces(id) ON DELETE CASCADE
                                                         )');
        }
        
        function getTypeHint($node) {
            if (isset($node->children['name'])) {
                return $node->children['name'];
            } elseif (!isset($node->flags)) {
                return ''; // None
            } elseif ($node->flags === ast\flags\TYPE_VOID) {
                return 'void';
            } elseif ($node->flags === ast\flags\TYPE_BOOL) {
                return 'boolean';
            } elseif ($node->flags === ast\flags\TYPE_LONG) {
                return 'integer';
            } elseif ($node->flags === ast\flags\TYPE_DOUBLE) {
                return 'float';
            } elseif ($node->flags === ast\flags\TYPE_STRING) {
                return 'string';
            } elseif ($node->flags === ast\flags\TYPE_ITERABLE) {
                return 'iterable';
            } elseif ($node->flags === ast\flags\TYPE_ARRAY) {
                return 'array';
            } elseif ($node->flags === ast\flags\TYPE_CALLABLE) {
                return 'callable';
            } elseif ($node->flags === ast\flags\TYPE_OBJECT) {
                return 'object';
            } else {
                return '';
                print_r($node);
                die(TYPEHINT);
            }
        }
}
?>