<?php
/*
 * this is to parse all cron collection json config and build cron objects in an iterator
 * TODO: make it really an iterator
 */
class CronCollection implements IteratorAggregate {
    public $config;
    
    public $cronObjects = array();
    
    /*
     * builds only the enabled cron objects
     */
    function __construct($all_cron_config_filepath, Registry $Registry=null) {
        if (!is_file($all_cron_config_filepath)) {
            trigger_error("all cron config file not found", E_USER_ERROR);
            return null;
        }
        
        $config = Json::json_decode_to_obj(file_get_contents($all_cron_config_filepath));

        $this->config = $config;
        
        if ($config->disable_all_cron) {
            trigger_error("all cron disabled \n", E_USER_WARNING);
            return null;
        };
        
        foreach($config->cron_list as $cron_conf) {
            $classname = $cron_conf->classname;
            if ($cron_conf->disabled) {
                trigger_error("cron $classname is disabled \n", E_USER_WARNING);
                continue;
            }
            if (!$classname) {
                trigger_error("\033[33m cron class name not specified in cron config \033[0m \n", E_USER_ERROR);
                return null;
            }
            print("instantiating cron $classname\n");
            $this->cronObjects[] = new $classname($cron_conf, $Registry);
            trigger_error("cron $classname instantiated!");
        }
    }
    function getIterator() {
        return new ArrayIterator($this->cronObjects);
    }
}