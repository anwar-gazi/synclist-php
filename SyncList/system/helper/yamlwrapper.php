<?php

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Dumper;

class YamlWrapper {

    function __construct() {
        
    }

    static function toarray($file_path) {
        $yaml = new Parser();
        return $yaml->parse(file_get_contents($file_path));
    }

    static function toobject($file_path) {
        $yaml = new Parser();
        $val = $yaml->parse(file_get_contents($file_path));
        return json_decode(json_encode($val));
    }

    static function toyaml(Array $data) {
        $dumper = new Dumper();
        return $dumper->dump($data);
    }

    static function edit($filepath, $callback) {
        if (file_exists($filepath)) {
            $yml = self::toarray($filepath);
        } else {
            $yml = [];
        }
        $yml = $callback($yml);
        if (is_array($yml)) {
            file_put_contents($filepath, self::toyaml($yml));
            return true;
        } else {
            return false;
        }
    }

    /**
     * read and compile a yml file for functions
     * @param type $filepath
     * @return type
     */
    static function compile($filepath, $toobject = false) {
        $is_rel_path = function($path) {
            if (\strpos($path, '/') === 0) {
                return false;
            } else {
                return true;
            }
        };
        $realpath = function($rel_path, $context_abspath) use(&$realpath, $is_rel_path) {
            if (!$is_rel_path($rel_path)) {
                //print("***not relative path:$rel_path\n");
                return $rel_path;
            }
            if ($rel_path && ($context_abspath !== '/')) {
                $context_abspath = \rtrim($context_abspath, '/');
            }
            if (\strpos($rel_path, '../', 0) !== FALSE) {
                $rel_path = \substr_replace($rel_path, '', 0, 3);
                $context_abspath = \dirname($context_abspath);
                $path = $realpath($rel_path, $context_abspath);
            } else {
                $path = $context_abspath.($rel_path?"/$rel_path":"");
            }
            return $path;
        };
        $match = function($pattern, $subject) {
            $matches = [];
            if (preg_match($pattern, $subject, $matches) === 1) {
                array_shift($matches);
                return $matches;
            } else {
                return 0;
            }
        };
        
        $translate = function($var) use($match, $realpath, $filepath) {
            if ($m = $match('#@import\((.+?)' . ((strrpos($var, '::') === FALSE) ? '' : '::(.+?)') . '\)#', $var)) { //look for @import
                $file = reset($m);
                if ($file=='__SELF__') {
                    $path = $filepath;
                    $arr = self::toarray($path);
                } else {
                    $path = $realpath($file, \dirname($filepath));
                    $arr = self::compile($path);
                }
                if (count($m) > 1) {
                    $indices = explode('->', next($m));
                    foreach ($indices as $ind) {
                        $arr = $arr[$ind];
                    }
                }
                return $arr;
            } else {
                return $var;
            }
        };
        $compile = function($var) use (&$compile, $translate) {
            if (is_array($var)) {
                foreach ($var as $ind => $val) {
                    $var[$ind] = $compile($val);
                }
                return $var;
            } elseif (is_string($var)) {
                return $translate($var);
            } else {
                return $var;
            }
        };
        $data = $compile(self::toarray($filepath));
        if ($toobject) {
            return \json_decode(json_encode($data));
        } else {
            return $data;
        }
    }

}
