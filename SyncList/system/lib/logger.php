<?php
/**
 * save and pull the veents log, for notification purpose
 * TODO: change the filename
 */

class Logger {
    
    public $db;
    
    /**
     * save log to db
     * @param string or constants $logtype: E_USER_ERROR, 'cron_finish', 
     */
    public function log($some_text, $logtype) {
        $logtype_field = "";
        switch($logtype) {
            case E_USER_ERROR:
                $logtype_field = "logtype_fatal_error=1";
                break;
            case "cron_finish":
            case E_USER_NOTICE:
                $logtype_field = "logtype_cron_finished=1";
                break;
            default:
                trigger_error("specify a log type");
                return;
                break;
        }
        
        $sql = "INSERT INTO ".EVENT_LOGS_TABLE." SET ".
            " text='$some_text'".
            ",log_time='".CarbonX::now()->toDateTimeString()."'".
            ",timezone='".CarbonX::tz_name()."'".
            ",timezone_offset='".CarbonX::tz_offset()."'"
            .($logtype_field?",$logtype_field":"");
        //print("$sql\n");
        $this->db->query($sql);
        
        if (!$this->db->getLastId()) { //if save failed
            trigger_error('cannot save error log, '.$this->db->getLastError(), E_USER_WARNING);
        }
    }
}

?>