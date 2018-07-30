<?php

/*
 * dont use this
 * this is heavily buggy when calling only functions
 */
class Debugger {
    static function backtrace() {
        $backtrace = debug_backtrace();
        
        return self::beautify($backtrace);
    }
    
    static function beautify(Array $backtrace) {
        $backtrace[0] = '';//remove trace of this class
        $backtrace = array_filter($backtrace);
        //$rev_backtrace = array_reverse($backtrace);
        
        $debug_texts = array();
        foreach($backtrace as $index=>$trace) {
            $file = $trace['file'];
            $line = $trace['line'];
            $class = $trace['class'];
            $function = $trace['function'];
            $object = get_class($trace['object']);
            $calltype = $trace['type'];
            $args = $trace['args'];
            
            $argtypes = array_map(function($arg) {
                if (is_object($arg)) {
                    return "[".get_class($arg)." object]";
                }
                if (is_array($arg)) {
                    return "[Array(".count($arg).")]";
                }
                return gettype($arg);
            }, $args); $argtypes = implode(",", $argtypes);
            
            $dbgtxt = self::indent($index-1)."[$class class] [$object object]"."$calltype"."$function( $argtypes ) in $file on $line";
            
            $debug_texts[] = $dbgtxt;
        }
        
        echo "\n"."Backtrace: \n=========\n".implode("\n", $debug_texts);
    }
    static function indent($level) {
        if (!$level) return "";
        $ind = "    ";
        $level = $level-1;
        $indented = '';
        for($i=1;$i<$level;$i++) {
            $indented .= $ind;
        }
        $indented .= "|".'____ ';
        return $indented;
    }
}