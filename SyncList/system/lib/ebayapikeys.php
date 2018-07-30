<?php

/*
 * represents api keys of a ebay seller but contains some helper methods too
 * use this class methods to load ebay api keys from json file because this class knows the file structure
 */
class EbayAPIKeys implements ConfigInterface {
    /*
     * this should be a stdclass object which was created from json
     */
    private $api_keys;
    private $ebay_user;
    
    function __construct($user_keys, $ebay_user) {
        if (!$ebay_user) {
            trigger_error("you must provide the ebay seller for whome the api keys are for", E_USER_WARNING);
            return null;
        }
        // now validation
        if (!($user_keys=$this->validate($user_keys, $ebay_user))) {
            trigger_error("ebay spi keys validation failed", E_USER_WARNING);
            return false;
        } else { // validation success
            $this->api_keys = $user_keys;
            $this->ebay_user = $ebay_user;
        }
    }
    
    function is_valid() {
        if ($this->validate($this->api_keys)) {
            return true;
        } else {
            return false;
        }
    }
    
    /*
     * api keys validate/parse/modify/load from json/show errors
     * 
     * if validated properly, you should use the keys returned by this method
     * 
     */
    public static function validate($keys, $ebay_user='') {
        $error = false;
        // no check the supplied varable type
        if (is_string($keys)) { // you provided keys filepath,
            trigger_error("assuming $keys as api key filepath", E_USER_NOTICE);
            $fullpath = EbayFetchCronConfig::resolve_path($keys);
            if (!is_file($fullpath)) {
                trigger_error("couldnt find api keys: $fullpath is not a file", E_USER_WARNING);
                return false;
            }
            $user_keys = self::load_user_keys_from_file($ebay_user, $fullpath);
        } elseif (is_object($keys)) { // nah, api keys are really inside the cron config
            if ($keys instanceof EbayAPIKeys) {
                $user_keys = $keys->api_keys;
                if ($ebay_user != $keys->ebay_user) {
                    trigger_error("you provided ebay_user:$ebay_user but inside your provided api_keys it is:".$keys->ebay_user);
                }
                if ($keys->api_keys) $ebay_user = $keys->ebay_user;
            } elseif ($keys instanceof stdclass) {
                $user_keys = $keys;
            } else {
                trigger_error("couldnt find api keys: unknow object type ".gettype($api_keys), E_USER_WARNING);
                return false;
            }
        } elseif (is_array($keys)) {
            trigger_error("couldnt find api keys: we dont support array yet", E_USER_WARNING);
            return false;
        } else {
            trigger_error("cannot find api keys: we dont support ".gettype($keys)." to instantiate EbayAPIKeys object", E_USER_WARNING);
            return false;
        }
        
        if (isset($user_keys->api_keys)) {
            $api_keys = $user_keys->api_keys;
        } else {
            $api_keys = $user_keys;
        }
        if (!isset($api_keys->siteID)) {
            trigger_error("siteID not present", E_USER_WARNING);
            $error = true;
        }
        if (!$api_keys->debug) {
            trigger_error("debug not present", E_USER_WARNING);
            $error = true;
        }
        if (!$api_keys->compatabilityLevel) {
            trigger_error("compatabilityLevel not present", E_USER_WARNING);
            $error = true;
        }
        if (!$api_keys->devID) {
            trigger_error("devID not present", E_USER_WARNING);
            $error = true;
        }
        if (!$api_keys->appID) {
            trigger_error("appID not present", E_USER_WARNING);
            $error = true;
        }
        if (!$api_keys->certID) {
            trigger_error("certID not present", E_USER_WARNING);
            $error = true;
        }
        if (!$api_keys->serverUrl) {
            trigger_error("serverUrl not present", E_USER_WARNING);
            $error = true;
        }
        if (!$api_keys->userToken) {
            trigger_error("userToken not present", E_USER_WARNING);
            $error = true;
        }
        if (!$error) {
            return $api_keys;
        } else {
            return false;
        }
    }
    
    static function load_user_keys_from_file($ebay_user, $filepath) {
        if (!is_file($filepath)) {
            trigger_error("$filepath is not api key valid filepath for $ebay_user", E_USER_ERROR);
            return;
        }
        
        $json = file_get_contents($filepath);
        $all_keys = Json::json_decode_to_obj($json);
        if ($json_error = Json::json_last_error()) {
            trigger_error("keys json file parse error $json_error", E_USER_ERROR);
            return null;
        }
        
        $user_keys;
        foreach($all_keys as $keys) {
            if ($keys->ebay_user == $ebay_user) {
                $user_keys = $keys->api_keys;
                break;
            }
        }
        return $user_keys;
    }
    public function get($key='') {
        if (!$key) {
            return $this->api_keys;
        }
        if (property_exists($this, $key)) {
            return $this->$key;
        }
        $value;
        if (arrayHelper::search_key($key, $this->api_keys, $value)) {
            return $value;
        }
    }
    
    function __get($key) {
        return $this->get($key);
    }
    
    function __isset($key) {
        return arrayHelper::search_key($key, $this->api_keys, $value);
    }
    
    public function __toArray() {
        $keys = array();
        $keys['ebay_user'] = $this->ebay_user;
        foreach($this->api_keys as $key=>$value) {
            $keys[$key] = $value;
        }
        return $keys;
    }
}