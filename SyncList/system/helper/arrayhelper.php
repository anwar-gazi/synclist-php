<?php

class arrayHelper {
    function array_map() {
        $new = [];
        $args = func_get_args();
        if ( !((count($args)>=2) && is_callable(end($args))) ) {
            trigger_error($error_msg);
            return null;
        }
        $data = reset($args);
        $callback = end($args);
        $more_data = array_slice($args, 1, count($args)-2);
        
        foreach ($data as $key=>$value) {
            $vals = [$value];
            foreach ($more_data as $d) {
                $args[] = $d[$key];
            }
            $new[$key] = call_user_func_array($callback, $vals);
        }
        return $new;
    }
    function array_map_filter() {
        $new = [];
        $args = func_get_args();
        if ( !((count($args)>=2) && is_callable(end($args))) ) {
            trigger_error($error_msg);
            return null;
        }
        $data = reset($args);
        $callback = end($args);
        $more_data = array_slice($args, 1, count($args)-2);
        
        foreach ($data as $key=>$value) {
            $vals = [$value];
            foreach ($more_data as $d) {
                $args[] = $d[$key];
            }
            if ($v = call_user_func_array($callback, $args)) {
                $new[] = call_user_func_array($callback, $args);
            }
        }
        return $new;
    }
    static function array_to_object(Array $arr) {
        return \json_decode(json_encode($arr));
    }
    /**
     * uniquify a two dimensional(array of element arrays) array by a key of the associative element arrays
     */
    static function array_unique_2d_by_key(Array $arr, $key) {
        $arr_uniq = array();
        $values_uniq = array();
        foreach($arr as $a) {
            if (!in_array($a[$key], $values_uniq)) {
                $values_uniq[] = $a[$key];
                $arr_uniq[] = $a;
            }
        }
        return $arr_uniq;
    }
    static function is_iterable($thing) {
        if (is_array($thing) || is_object($thing)) {
            return true;
        } else {
            return false;
        }
    }
    /*
     * search for a key recustively in an array and get the value of that key by setting in your passed variable
     */
    static function search_key($key, $array_or_object, &$value_container=null) {
        $value_found = false;
        if (!self::is_iterable($array_or_object)) {
            return false;
        }
        foreach($array_or_object as $k=>$value) {
            if ( ($key==$k) || self::search_key($key, $value, $value_container) ) {
                if (!$value_found) {
                    $value_found = true;
                    $value_container = $value;
                }
                return true;
            }
        }
        return false;
    }
    /*
     * bypass the hack like json_decode(json_encode($arr)) unless you are sure of the characters encoding inside your array
     * this method is to avoid the ut8 issue with php's json support
     */
    static function __toObject(Array $arr) {
        $obj = new stdClass();
        foreach($arr as $key=>$val) {
            if (is_array($val)) {
                $val = self::__toObject($val);
            }
            $obj->$key = $val;
        }

        return $obj;
    }
}