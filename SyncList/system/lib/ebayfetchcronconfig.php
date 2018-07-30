<?php
/*
 * tyo manage, load, parse a cron config
 * the config files parser and config values container for a cron, and config variables manager
 */
class EbayFetchCronConfig implements ConfigInterface {
    
    /*
     * this should contain a stdclass object
     * TODO: change the system to set it an array of config keys
     */
    protected $_Config;
    
    /*
     * cron collection already aprsed the all cron json,
     * so that json parsed into array or object depends on cron collection iterator object
     * @warning: you should validate after instantiation
     */
    function __construct($config) {
        if ($_Config = $this->validate($config)) {
            $this->_Config = $_Config;
        } else {
            trigger_error("config validation failed", E_USER_WARNING);
        }
    }
    /*
     * get a valid instance of this object
     */
    static function init($config) {
        if ($_Config = self::validate($config)) {
            $ME = new self($_Config);
            return $ME;
        } else {
            return null;
        }
    }
    
    function is_valid() {
        if ($this->validate($this->_Config)) {
            return true;
        } else {
            return false;
        }
    }
    
    public function ebay_user() {
        return $this->_Config->ebay_user;
    }
    
    public function api_keys() {
        return $this->_Config->api_keys;
    }
    
    /*
     * you can pass almost anything
     * validate and set(if validation succeeded)
     * validation changes somethings like fetching keys from file and then setting them then returning the config,
     * thats why we are taking return from both validation methods
     * validate_cron_config result is replaced by validate_api_keys
     */
    static function validate($config) {
        if (is_object($config)) {
            if (get_class($config)=='EbayFetchCronConfig') {
                $_Config = $config->get();
            } else {
                $_Config = $config;
            }
        } elseif (is_string($config)) { // can be either json, if not then filepath
            if (!($_Config = Json::json_decode_to_obj($config)) && !($_Config = self::parse_json($config))) { // not valid json
                trigger_error("cannot load config from $config", E_USER_WARNING);
                return null;
            }
        } elseif (is_array($config)) {
            $_Config = arrayHelper::__toObject($config);
        } else {
            //debug_print_backtrace();
            trigger_error("the provided cron config must be a object or json string or url string or array, but you provided ".gettype($config), E_USER_WARNING);
            return false;
        }
        
        if ( ($_Config = self::validate_cron_config($_Config)) && ($API_keys = self::validate_api_keys($_Config))) {
            $_Config->api_keys = $API_keys;
            return $_Config;
        } else {
            return null;
        }
    }
    
    static function parse_json($absolute_filepath) {
        if (!is_file($absolute_filepath)) {
            trigger_error("not a file: $absolute_filepath", E_USER_WARNING);
            return null;
        }
        if (!($config = Json::load_file_to_obj($absolute_filepath))) {
            trigger_error("cannot load cron config from $absolute_filepath", E_USER_WARNING);
            return null;
        } else {
            return $config;
        }
    }
    
    /*
     * set config by this method only
     * it loads api keys, sets config variable, validates config
     * show warning for potential dangerous settings to makes things clear
     * 
     * does some changes over the supplies params if necessary, but this is very very minor
     * as we show error reports for most inconveniences
     *
     * after calling this method, you should use its return value as validated config
     * 
     * @note: if debug mode is turned off then fetch_xml and save_update settings must be true
     * if not then E_USER_ERROR will be shown
     */
    static function validate_cron_config($config) {
        if (!is_object($config)) {
            return null;
        }
        if (isset($config->disabled) && $config->disabled) {
            trigger_error("cron ".$config->classname." is disabled, check the cron config json", E_USER_WARNING);
        }
        if (!$config->ebay_user) {
            trigger_error("privide ebay_user config key and a valid value, in cron config", E_USER_WARNING);
            return null;
        }
        if (!$config->api_keys) {
            trigger_error("privide api_keys config key and api keys, in cron config", E_USER_WARNING);
            return null;
        }
        
        if (!$config->debug) { // debug off
            if (!$config->fetch_xml || !$config->save_update) {
                trigger_error("malformed cron config json: fetch_xml and save_update must be true when debug turned off \n", E_USER_WARNING);
                return null;
            }
        }
        
        if (!$config->fetch_xml && $config->save_xml) {
            trigger_error("malformed cron config json: save_xml must be false when fetch_xml is disabled",E_USER_WARNING);
            return null;
        }
        $settings_report = "from cron config:";
        if (!$config->fetch_xml) {
            $settings_report .= "fetch disabled, will try serve xml from local filesystem,";
        }
        if ($config->save_xml) {
            $settings_report .= "fetched xml will be saved,";
        }
        if (!$config->save_update) {
            $settings_report .= "no updated will be pushed into database,";
        }
        if ($settings_report) {
            trigger_error("$settings_report");
        }
        return $config;
    }
    
    /*
     * validate api keys contained inside this config
     * and return validated api keys
     * load(and validate) api keys settings in the config, 
     * this method should run when initializing object
     * 
     * does some changes over the supplies params if necessary, but this is very very minor
     * as we show error reports for most inconveniences
     *
     * after calling this method, yu should use its return value as validated keys
     * 
     * at object init time, it checks api_keys value, 
     * if its a string
     * then it assumes it as api key containg filapth, the file should be json
     * it then loads the keys from the file parsing json as object
     * that file should have an array of objects, each object contains a ebay user api keys
     * this object structure: object->ebay_user and object->api_keys
     *
     * if not stirng, thats an object then checks validity then sets it
     * call this method after loading config
     */
    static function validate_api_keys($config) {
        if (!is_object($config)) {
            return null;
        }
        $ebay_user = $config->ebay_user;
        $keys = $config->api_keys;
        
        if (($API_keys_user = new EbayAPIKeys($keys, $ebay_user)) && !$API_keys_user->is_valid()) {
            trigger_error("\033[31m ebay api keys for $ebay_user not valid! check config \033[0m", E_USER_WARNING);
            return false;
        }
        return $API_keys_user;
    }
    
    /*
     * to resolve paths in this config variables
     * config variables paths are in context to config dir
     */
    static function resolve_path($path) {
        $root_path = ROOT_DIR.'config';
        $resolved = DirHelper::realpath($path, $root_path);
        return $resolved;
    }
    
    public function get($key) {
        if (!isset($key)) {
            return $this->_Config;
        }
        $value;
        //print("looking for $key in ".print_r($this->_Config, true)."\n");
        if (arrayHelper::search_key($key, $this->_Config, $value)) {
            return $value;
        }
    }
    
    function __get($key) {
        return $this->get($key);
    }
    
    function __isset($key) {
        $value;
        return arrayHelper::search_key($key, $this->_Config, $value);
    }
    
    public function __toArray() {
        $config_arr = array();
        $config_arr['api_keys'] = $this->get('api_keys')->__toArray();
        foreach($this->_Config as $key=>$value) {
            $config_arr[$key] = $value;
        }
        return $config_arr;
    }
}