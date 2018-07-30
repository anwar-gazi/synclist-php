<?php

class PHPDocParser {

    private $docstr;

    function __construct($docstr) {
        $this->docstr = $docstr;
    }

    function __get($var) {
        $regex = "#@{$var}(.+)#";
        $matches = [];
        if (preg_match($regex, $this->docstr, $matches)) {
            $val = $matches[1];
        } else {
            $val = '';
        }

        return trim($val);
    }

}
