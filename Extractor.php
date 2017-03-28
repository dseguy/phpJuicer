<?php

class Extractor {
    private $return = array('Class'     => array(),
                            'Interface' => array(),
                            'Trait'     => array(),
                            'Namespace' => array());
                            
    const GLOBAL = '\\';

    public function processFile($file) {
        $tokens = token_get_all(file_get_contents($file));
        
        $namespace = self::GLOBAL;
        
        foreach($tokens as $id => $token) {
            if (is_array($token)) {
                switch($token[0]) {
                    case T_NAMESPACE : 
                        $namespace = '';
                        for ($i = $id + 2; ($tokens[$i] != ';') && ($tokens[$i] != '{') && ($i - $id < 20); $i++) {
                            if (is_array($tokens[$i])) {
                                $namespace .= $tokens[$i][1];
                            } else {
                                $namespace .= $tokens[$i];
                            }
                        }
                        $namespace = trim($namespace);
                        break;
    
                    case T_CLASS : 
                        // skip ::class 
                        if ($tokens[$id - 1][1] == '::') { break 1; }
    
                        // skip anonymous class : 
                        if (!is_array($tokens[$id + 2])) { break 1; }
                        if ($tokens[$id + 2][0] != T_STRING) { break 1; }
    
                        $this->return['Class'][$namespace][] = $tokens[$id + 2][1];
                        $this->return['Namespace'][$namespace] = 1;
                        break;
    
                    case T_INTERFACE : 
                        $this->return['Interface'][$namespace][] = $tokens[$id + 2][1];
                        $this->return['Namespace'][$namespace] = 1;
                        break;
    
                    case T_TRAIT : 
                        $this->return['Trait'][$namespace][] = $tokens[$id + 2][1];
                        $this->return['Namespace'][$namespace] = 1;
                        break;
                    
                    default : 
                        // nothing to do
                    
                }
            }
        }
        
        return true;
    }
    
    public function get($type = 'Namespace') {
        if ($type === 'Namespace') {
            return array_keys($this->return[$type]);
        } else {
            return $this->return[$type];
        }
    }
}
?>