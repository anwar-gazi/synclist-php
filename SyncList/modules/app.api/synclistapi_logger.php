<?php

class SyncListApiLogger
{

    /** @var Logger */
    private $log;
    private $api;
    private $db;

    function __construct(SyncListApi $api)
    {
        $this->log = $api->log;
        $this->api = $api;
        $this->db = $api->db;
    }

    /**
     * @param $start_index
     * @param $end_index
     * @return array
     */
    function cronlogs($start_index, $end_index)
    {
        $count = $end_index - $start_index;
        return $this->db->query("select * from sl_synclist_logs WHERE type='cronstate' LIMIT $start_index,$count")->rows;
    }

    function count_cronlogs()
    {
        return $this->db->query("SELECT count(*) AS num FROM sl_synclist_logs WHERE type='cronstate'")->row['num'];
    }

    function otherlogs($start_index, $end_index)
    {
        $count = $end_index - $start_index;
        return $this->db->query("select * from sl_synclist_logs WHERE type<>'cronstate' LIMIT $start_index,$count")->rows;
    }

    function count_otherlogs()
    {
        return $this->db->query("SELECT count(*) AS num FROM sl_synclist_logs WHERE type<>'cronstate'")->row['num'];
    }

    function recent_cron_logs()
    {
        $logs = $this->log->cronstate_logs();
        return array_slice($logs, 0, 5);
    }

    function recent_event_logs()
    {
        $logs = $this->log->event_logs();
        return array_slice($logs, 0, 5);
    }

    function last_id()
    {
        return $this->db->query("select id from {$this->api->table_name('synclist_logs')} order by id desc limit 1")->row['id'];
    }

    function logs_after($after_log_id)
    {
        return $this->db->query("select * from {$this->table_name} where id>$after_log_id")->rows;
    }

    function cronlogs_after($after_log_id)
    {
        return $this->db->query("select * from {$this->table_name} where id>$after_log_id and type='cron'")->rows;
    }

    function otherlogs_after($after_log_id)
    {
        return $this->db->query("select * from {$this->table_name} where id>$after_log_id and type<>'cron'")->rows;
    }

    function event_logs()
    {
        return $this->log->event_logs();
    }

    function cron_logs()
    {
        return $this->log->cronstate_logs();
    }

    function log_inventory_change($text)
    {
        $table = $this->api->table_name('synclist_logs');
        $this->db->query("insert into sl_synclist_logs set log='$text', time='" . ServerTime::now()->toISO8601String() . "', type='inventory'");
        return $this->db->countAffected();
    }

    function __get($key)
    {
        switch ($key) {
            case 'table_name':
                $val = $this->api->table_name('synclist_logs');
                break;
        }
        return $val;
    }

}
