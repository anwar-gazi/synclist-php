<?php

class SqlHelper {
    /**
    * make a keyed array into the sql qury string transposing $key=$value
    * @param array 
    * @return string the query string
    */
    function array_to_qstr(Array $order) {
        global $EFKernel;
        $qstr = '';
        //$EFKernel->DB->set_charset_utf8true();
        foreach($order as $key=>$value) {
            if (!$value) continue;
            if (is_array($value)) {
                $value = serialize($value);
            }
            $qstr = $qstr . "$key='".$EFKernel->DB->escape($value)."',";
        }
        $qstr = rtrim($qstr, ',');
        return $qstr;
    }
}