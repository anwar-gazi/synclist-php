<?php

class Log
{
    private $error_handle;
    private $syslog_handle;

    public function __construct($error_filefullpath, $syslog_filefullpath)
    {
        $this->error_handle = fopen($error_filefullpath, 'a');
        $this->syslog_handle = fopen($syslog_filefullpath, 'a');
    }

    public function write($message)
    {
        fwrite($this->error_handle, \Carbon\Carbon::now()->toIso8601String() . ' - ' . $message . "\n");
    }

    public function error($message)
    {
        $this->write($message);
    }

    public function log($type, $message)
    {
        fwrite($this->syslog_handle, $type . ' - ' . \Carbon\Carbon::now()->toIso8601String() . ' - ' . $message . "\n");
    }

    public function inventory_change($message)
    {
        $this->log('inventory', $message);
    }

    // TODO fix it
    function cronlogs($start_index, $end_index)
    {
        $count = $end_index - $start_index;
        return $this->db->query("select * from sl_synclist_logs WHERE type='cronstate' LIMIT $start_index,$count")->rows;
    }

    public function __destruct()
    {
        fclose($this->error_handle);
        fclose($this->syslog_handle);
    }

}