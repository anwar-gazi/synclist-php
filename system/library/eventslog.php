<?php

class EventsLog {
    
    private $db;
    
    //opencart registry
    function __construct(Registry $registry) {
        $this->config = $registry->get('config');
		$this->db = $registry->get('db');
    }
    
    function all_logs_arr() {
        $query = $this->db->query("SELECT * FROM ".EVENT_LOGS_TABLE." ORDER BY log_time DESC");
        if ($query->num_rows) {
            return $query->rows;
        } else {
            return array();
        }
    }
    
    function event_logs() {
        $query = $this->db->query("SELECT * FROM ".EVENT_LOGS_TABLE." WHERE logtype_cron_finished<>1 ORDER BY log_time DESC");
        if ($query->num_rows) {
            return $query->rows;
        } else {
            return array();
        }
    }
    
    function cron_logs() {
        $query = $this->db->query("SELECT * FROM ".EVENT_LOGS_TABLE." WHERE logtype_cron_finished=1 ORDER BY log_time DESC");
        if ($query->num_rows) {
            return $query->rows;
        } else {
            return array();
        }
    }
    
    /**
     * get latest 2 logs entry
     */
    function recent_cron_logs() {
        //echo 'here';
        $query = $this->db->query("SELECT * FROM ".EVENT_LOGS_TABLE." WHERE logtype_cron_finished=1 ORDER BY id DESC LIMIT 2");
        if ($query->num_rows) {
            return $query->rows;
        } else {
            return array();
        }
    }
    
    function recent_event_logs() {
        //echo 'here';
        $query = $this->db->query("SELECT * FROM ".EVENT_LOGS_TABLE." WHERE logtype_cron_finished<>1 ORDER BY log_time DESC LIMIT 4");
        if ($query->num_rows) {
            return $query->rows;
        } else {
            return array();
        }
    }
    
    function write($log_text) {
        $dt = Carbon\Carbon::now();
        $time = $dt->toDateTimeString();
        $tz_obj = $dt->getTimeZone();
        $tz_name = $tz_obj->getName();
        $tz_offset = $tz_obj->getOffset($dt);
        
        $this->write_log($log_text, $time, $tz_name, $tz_offset);
        
    }
    
    function write_log($some_text, $time, $tz_name, $tz_offset) {
        $this->db->query("INSERT INTO ".EVENT_LOGS_TABLE." SET ".
            " text='".$this->db->escape($some_text)."'".
            ",log_time='".$this->db->escape($time)."'".
            ",timezone='".$this->db->escape($tz_name)."'".
            ",timezone_offset='".$this->db->escape($tz_offset)."'"
        );
    }
}

?>