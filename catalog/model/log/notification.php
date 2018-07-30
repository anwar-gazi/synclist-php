<?php

class ModelLogNotification extends Model {
    
    function count_unread_notifications() {
        $query = $this->db->query("SELECT * FROM ".EVENT_LOGS_TABLE." WHERE `logtype_fatal_error`=1 AND `viewed`=0 ORDER BY log_time DESC");
        return $query->num_rows;
    }
    
    function all_error_notifications() {
        $query = $this->db->query("SELECT * FROM ".EVENT_LOGS_TABLE." WHERE `logtype_fatal_error`=1 ORDER BY log_time DESC");
        return $query->rows;
    }
    
}

