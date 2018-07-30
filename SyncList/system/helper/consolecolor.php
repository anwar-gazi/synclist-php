<?php
// "[red] [/red]"
// TODO: write it
class ConsoleColor {
    const $colors = array(
        'red'=>"\033[32m",
        'yellow'=>"\033[36m",
        'clear'=>"\033[0m"
    );
    function parse_format_string($str) {
        foreach(self::$colors as $key=>$value) {
            $col = "[$key]";
            $colend = "[/$key]";
            
            $pos = strpos($str, $col);
            $endpos = strrpos($str, $col);
            
            if ($pos !== FALSE) {
                
            }
        }
    }
}