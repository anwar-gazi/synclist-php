<?php
/*
 *
 * each cron class must extend this class
 * a cron class must have a method named run inside which you put your cron codes
 * and you specified thatcron class in the cron config json
 * 
 */
Class EbayFetchCronBase {
    /*
     * cron config
     * this config object contains cron config+api keys
     * api keys are in $this->config->api_keys
     */
    protected $_Config;
    
    /*
     * overloaded at runtime when cronbase object is created
     */
    //public $Registry;
    
    /*
     * php returns parsed json as stdclass object
     * so @param $cron_config should be an object
     *
     /*
     * process cron config variables and build a cron object accordingly
     * a cron object is created from a class you created, 
     * in which there is a method named 'run', inside this method you put your cron codes, 
     * and you specified that class name in cron config json
     * that class of you must extend this class,
     * 
     * the class must be inside currently registered autoload path. 
     * If not then register the path in cron runnner script
     *
     * if you dont provide appropriate config, then there is no way to use this object properly
     * because: thats the point of building this object
     * 
     */
    function __construct($cron_config, Registry $Registry=null) {
        if ($config = EbayFetchCronConfig::init($cron_config)) {
            $this->_Config = $config;
        } else {
            trigger_error("cannot set config", E_USER_WARNING);
            return false;
        }
        if (!$Registry) {
            trigger_error("[Registry] object injection required");
        }
        // dependency injection
        $this->Registry = $Registry;
    }
    // you must call this after creation of this object
    function is_valid() {
        if (is_object($this->_Config) && ($this->_Config instanceof EbayFetchCronConfig) && $this->_Config->is_valid()) {
            return true;
        } else {
            return false;
        }
    }
    
    static function init($cron_config, EbayFetchRegistry $Registry=null) {
        $ME = new self($cron_config, $Registry);
        if ($ME->is_valid()) {
            return $ME;
        } else {
            return false;
        }
    }
    
    /*
     * TODO: abide save settings
     */
    protected function db_query($sql) {
        return $this->db->query($sql);
    }
    
    /*
     * get a config variable by key name
     * @param string $key
     * @important: currently nested keys support not added
     */
    function config($key='') {
        if (!$key) return $this->_Config;
        return $this->_Config->$key;
    }
    /*
     * get the current ebay user the keys set for
     */
    public function ebay_user() {
        return $this->config('ebay_user');
    }
    public function api_keys() {
        return $this->config('api_keys');
    }
    /*
     * the class that has the run method that is running
     */
    public function classname() {
        return get_class($this);
    }
    /*
     * cron name
     */
    public function cron_name() {
        return $this->config('classname');
    }
    public function name() {
        return $this->cron_name();
    }
    /*
     * to check that debug is on
     */
    protected function debug_on() {
        return $this->config('debug');
    }
    
    /*
     * TODO: we dont yet supprt intervals
     */
    protected function is_scheduled_to_run() {
        $interval = $this->config('interval');
        if (($now - $this->last_run_time)>=$interval) {
            return true;
        }
    }
    
    /*
     * just to make your day easier
     * get an api object from the api class you wrote
     * (this is usually kept same as the implemented ebay api name)
     * we properly init the api object with config
     * if you want to create api object on your own then no problem
     *
     * @return object
     * @visibility proected becasue we dont want it to be inherited in EbayAPI class
     */
    public function get_api($api_name, Array $api_options=array()) {
        if (!$api_name) {
            trigger_error("ERROR: provide an api name to get the api object", E_USER_WARNING);
            return null;
        }
        if (!class_exists($api_name)) {
            trigger_error("cannot get an api, $api_name class not exits", E_USER_WARNING);
            return null;
        }
        $API = new $api_name();
        $API->Request = new SettingsObeyingEbayXmlRequest($this->_Config);
        return $API;
    }
    
    /*
     * log something
     */
    function log($logtxt, $logtype) {
        $this->Registry->logger->log($logtxt, $logtype);
    }
    
    /*
     * to get/set the cron state progress percentage feedback
     * this automatically checks if the state object is properly instantiated for this cron
     * by matching cron_name
     * @param int optional $percentage, the percentage completed to set, 
     * if you dont pass this parameter then the state object will be returned
     *
     * @return 
     */
    function progress($percentage=null, $msg='') {
        $state = $this->Registry->state;
        if (!$state->valid($this->cron_name())) { // check init 
            $state->reset($this->cron_name());
        }
        if ($percentage===null) { // state object get
            return $state;
        } else { // set
            $state->set($percentage, $msg);
        }
    }
    
    function __get($key) {
        if (property_exists($this->Registry, $key)) {
            return $this->Registry->$key;
        }
        
        return $this->get_api($key);
    }
    
    function __destruct() {
        
    }
}