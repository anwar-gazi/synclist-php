<?php

class Logger extends SyncListModule
{

    private $db;
    private $table_name;

    function __construct(\SyncListKernel $kernel, \stdClass $config, $module_path, DBMySQLi $db, $table_name)
    {
        parent::__construct($kernel, $config, $module_path);
        $this->db = $db;
        $this->table_name = $table_name;
    }

    public function log_cronstate($text)
    {
        $type = 'cronstate';
        $this->db->query("insert into sl_synclist_logs set log='{$text}', time='" . ServerTime::now()->toISO8601String() . "', type='cronstate'");
    }

    public function cronstate_logs()
    {
        $type = 'cronstate';
        return $this->db->query("select * from sl_synclist_logs where `type`='$type' order by `time` desc")->rows;
    }

    public function event_logs()
    {
        return $this->db->query("SELECT * FROM sl_synclist_logs WHERE `type`<>'cronstate' ORDER BY `time` DESC")->rows;
    }
}
