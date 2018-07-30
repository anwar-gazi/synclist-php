<?php

class ConfigContainer {
    public $_Config;
    function __construct() {
        
    }
    
    static function load_json($filepath) {
        if (!is_file($filepath)) {
            trigger_error("$filepath is not api key valid filepath for $ebay_user \n", E_USER_ERROR);
            return;
        }
        
        $json = file_get_contents($filepath);
        $config = Json::json_decode_to_obj($json);
        if ($json_error = Json::json_last_error()) {
            trigger_error("keys json file parse error $json_error", E_USER_ERROR);
            return null;
        }
        
        return $config;
    }
    
    public function get($key='') {
        if (!$key) {
            return $this->user_keys;
        }
        if (isset($this->user_keys->$key)) {
            return $this->user_keys->$key;
        } else {
            return null;
        }
    }
    
    function __get($key) {
        if ($key) {
            if ($key=='ebay_user') {
                return $this->$key;
            }
            return $this->get($key);
        }
    }
    
    function __toArray() {
        $keys = array();
        $keys['ebay_user'] = $this->ebay_user;
        foreach($this->user_keys as $key=>$value) {
            $keys[$key] = $value;
        }
        return $keys;
    }
}