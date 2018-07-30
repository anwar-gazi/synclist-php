<?php

class Json {
    static function load_file_to_obj($absolute_filepath) {
        $json = file_get_contents($absolute_filepath);
        return self::json_decode_to_obj($json);
    }
    static function json_decode_to_obj($json_str) {
        $obj = json_decode($json_str);
        if ($err = json_last_error()) {
            return null;
        }
        return $obj;
    }
    static function json_decode_to_array($json_str) {
        return json_decode($json_str, true);
    }
    static function json_last_error() {
        return json_last_error();
    }
}