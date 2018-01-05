<?php

$codePath = $argv[1];
$base = $argv[2];
print "Extracting all in $codePath into $base.sqlite\n";

$sqlite = new Sqlite3($base.'.sqlite');
initSqlite($sqlite);

include("Vcs.php");
$vcs = new Vcs($codePath);

$configs = json_decode(file_get_contents('frameworks.json'));

if (!isset($configs->$base)) {
    die( "No configuration for '$base' in frameworks.json.\nUpdate this file first\n");
}
$versions = $configs->$base->versions;

foreach($versions as $version => $tag) {
    print "check version $version\n";
    global $versionId, $namespaceId;
    
    $sqlite->query("INSERT INTO versions VALUES (null, \"$version\", \"$tag\")");
    $versionId = $sqlite->lastInsertRowID();

    $sqlite->query("INSERT INTO namespaces VALUES (null, '', $versionId)");
    $namespaceId = $sqlite->lastInsertRowID();

    $files = $vcs->checkOut($tag);
    
    $total = 0;
    foreach($files as $file) {
        print $file.PHP_EOL;
        ++$total;
        if (preg_match('#/tests?/#i', $file)) { continue; }
        $code = file_get_contents((string) $file);
    
        $ast = ast\parse_code($code, 50);

        traverseAst($ast->children);
        
        if ($total == 2) {
//            die();
        }
    }

    print "$version : $total files \n";
}

function traverseAst($ast) {
    foreach($ast as $node) {
        if ($node->kind === ast\AST_STMT_LIST) {
            traverseAst($node->children);
        } elseif ($node->kind === ast\AST_CLASS) {
            traverseClass($node);
        } elseif ($node->kind === ast\AST_FUNC_DECL) {
            traverseFunction($node);
        } elseif ($node->kind === ast\AST_CONST_DECL) {
            traverseConstant($node);
        } elseif ($node->kind === ast\AST_NAMESPACE) {
            traverseNamespace($node);
        } elseif ($node->kind === ast\AST_USE) {
            traverseUse($node);
        } else {
//            print 'Default : '.$node->kind.PHP_EOL;
        }
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
    
    global $sqlite, $namespaceId;
    
    $sqlite->query("INSERT INTO cit VALUES (null, '$name', $final, $abstract, '$type', '$extends', $namespaceId)");
    $classId = $sqlite->lastInsertRowID();
    
    if (!empty($class->children['implements'])) {
        foreach($class->children['implements']->children as $implements) {
            $sqlite->query("INSERT INTO cit_implements VALUES (null, '$classId', '{$implements->children['name']}')");
        }
    }
    
    if (!empty($class->children['stmts']->children)) {
        traverseStmts($class->children['stmts']->children, $classId);
    }
}

function traverseStmts($stmts, $classId) {
    global $sqlite;

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

                $name = $sqlite->escapeString($name);
                $value = $sqlite->escapeString($value);
                $doccomment = $sqlite->escapeString($doccomment);
                $sqlite->query("INSERT INTO class_constants VALUES (null, '$name', '$classId', '$modifier', '$value', '$doccomment')");
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
        
            $name = $sqlite->escapeString($name);
            $doccomment = $sqlite->escapeString($doccomment);
            $sqlite->query("INSERT INTO methods VALUES (null, '$name', '$classId', '$static', '$final', '$abstract', '$modifier', '$returntype', '$doccomment')");
            
            $methodId = $sqlite->lastInsertRowID();
            foreach($stmt->children['params']->children as $param) {
                $name = $param->children['name'];
                $typehint = getTypeHint($param->children['type']);
                if ($param->children['default'] instanceof ast\Node) {
                    // Ignore ATM
                    $value = '';
                } else {
                    $value = '';
                }
                
                $sqlite->query("INSERT INTO argumentsMethods VALUES (null, '$name', '$methodId', '$value', '$typehint')");
            }
        } elseif ($stmt->kind === ast\AST_USE_TRAIT) {
            foreach($stmt->children['traits']->children as $declaration) {
                $name = $declaration->children['name'];
                $sqlite->query("INSERT INTO use_trait VALUES (null, '$name', '$classId')");
            }
        } elseif ($stmt->kind === ast\AST_PROP_DECL) {
            foreach($stmt->children as $declaration) {
                $name = $declaration->children['name'];
                if (isset($declaration->children['value'])) {
                   $value = $declaration->children['value'];
                } else {
                    $value = '';
                }
                $doccomment = $declaration->children['docComment'];

                $value = $sqlite->escapeString($value);
                $doccomment = $sqlite->escapeString($doccomment);
                $sqlite->query("INSERT INTO property VALUES (null, '$name', '$classId', '$modifier', '$value', '$doccomment')");
            }
        } else {
            print 'STMT UNKNOWN ' .$stmt->kind.PHP_EOL;
        }
    }
}

function traverseFunction($node) {
    global $sqlite, $namespaceId;
    
    $name = $node->children['name'];
    if (is_object($node->children['returnType'])) {
       $returntype = $node->children['returnType']->children['name'];
    } else {
        $returntype = '';
    }
    $doccomment = $node->children['docComment'];

    $name = $sqlite->escapeString($name);
    $returntype = $sqlite->escapeString($returntype);
    $doccomment = $sqlite->escapeString($doccomment);
    $sqlite->query("INSERT INTO functions (id, function, returntype, doccomment, namespace) VALUES (null, '$name', '$returntype', '$doccomment', $namespaceId)");
    
    $functionId = $sqlite->lastInsertRowID();
    foreach($node->children['params']->children as $param) {
        $name = $param->children['name'];
        if (is_object($param->children['type'])) {
            $typehint = getTypeHint($param->children['type']);

        } else {
            $typehint = '';
        }
        $value = $param->children['default'];
        if ($value instanceof ast\Node) {
            $value = $value->children['name'];
        }
        if ($value instanceof ast\Node) {
            $value = $value->children['name'];
        }
    
        $sqlite->query("INSERT INTO argumentsFunctions VALUES (null, '$name', '$functionId', '$value', '$typehint')");
    }
}

function traverseConstant($node) {
    global $sqlite;
    
    foreach($node->children as $declaration) {
        $name = $declaration->children['name'];
        $value = $declaration->children['value'];
        $doccomment = $declaration->children['docComment'];
        $sqlite->query("INSERT INTO constants VALUES (null, '$name','$value', '$doccomment')");
    }

}

function traverseNamespace($node) {
    global $sqlite, $namespaceId, $versionId;

    $res = $sqlite->query("SELECT id FROM namespaces WHERE name='{$node->children['name']}' and versionId='$versionId'");
    if ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $namespaceId = $row['id'];
    } else {
        $sqlite->query("INSERT INTO namespaces VALUES (null, '{$node->children['name']}', '$versionId')");
    
        $namespaceId = $sqlite->lastInsertRowID();
    }
}

function traverseUse($node) {
    global $uses;
    
    if (!isset($uses)) {
        $uses = array(ast\flags\USE_CONST => array(),
                      ast\flags\USE_FUNCTION => array(),
                      ast\flags\USE_NORMAL => array(),
                    );
    }
    
    foreach($node->children as $children) {
        $uses[$node->flags] = $uses[$node->flags] + traverseAlias($children);
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

function initSqlite($sqlite) {
        $sqlite->query('DROP TABLE IF EXISTS versions');
        $sqlite->query('CREATE TABLE versions (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                version TEXT,
                                                tag TEXT
                                                 )');
        $sqlite->query('DROP TABLE IF EXISTS namespaces');
        $sqlite->query('CREATE TABLE namespaces (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                            name TEXT,
                                            versionId INTEGER
                                          )');

        $sqlite->query('DROP TABLE IF EXISTS cit');
        $sqlite->query('CREATE TABLE cit (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                            name TEXT,
                                            abstract INTEGER,
                                            final INTEGER,
                                            type TEXT,
                                            extends TEXT DEFAULT "",
                                            namespaceId INTEGER DEFAULT 1
                                          )');

        $sqlite->query('DROP TABLE IF EXISTS cit_implements');
        $sqlite->query('CREATE TABLE cit_implements (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                       cit INTEGER,
                                                       implements TEXT
                                                 )');

        $sqlite->query('DROP TABLE IF EXISTS class_constants');
        $sqlite->query('CREATE TABLE class_constants (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                name TEXT,
                                                citId INTEGER,
                                                visibility TEXT,
                                                value TEXT,
                                                doccomment TEXT
                                                 )');

        // Methods
        $sqlite->query('DROP TABLE IF EXISTS methods');
        $sqlite->query('CREATE TABLE methods (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                method INTEGER,
                                                citId INTEGER,
                                                static INTEGER,
                                                final INTEGER,
                                                abstract INTEGER,
                                                visibility TEXT,
                                                returntype TEXT,
                                                doccomment TEXT
                                                 )');
        $sqlite->query('DROP TABLE IF EXISTS argumentsMethods');
        $sqlite->query('CREATE TABLE argumentsMethods (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                name INTEGER,
                                                methodId INTEGER,
                                                value INTEGER,
                                                typehint INTEGER
                                                 )');

        $sqlite->query('DROP TABLE IF EXISTS functions');
        $sqlite->query('CREATE TABLE functions (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                function INTEGER,
                                                returntype TEXT,
                                                doccomment TEXT,
                                                namespace INTEGER
                                                 )');

        $sqlite->query('DROP TABLE IF EXISTS argumentsFunctions');
        $sqlite->query('CREATE TABLE argumentsFunctions (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                name INTEGER,
                                                methodId INTEGER,
                                                value INTEGER,
                                                typehint INTEGER
                                                 )');

        $sqlite->query('DROP TABLE IF EXISTS use_trait');
        $sqlite->query('CREATE TABLE use_trait (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                  cit INTEGER,
                                                  trait TEXT
                                                 )');

        $sqlite->query('DROP TABLE IF EXISTS property');
        $sqlite->query('CREATE TABLE property (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                property TEXT,
                                                citId INTEGER,
                                                visibility TEXT,
                                                value TEXT,
                                                doccomment TEXT
                                                 )');

        $sqlite->query('DROP TABLE IF EXISTS constants');
        $sqlite->query('CREATE TABLE constants (  id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                name TEXT,
                                                value TEXT,
                                                doccomment TEXT
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