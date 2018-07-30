<?php

class SyncListApiCronState
{

    private $db;
    private $table_name;

    function __construct(SyncListApi $api)
    {
        $this->db = $api->db;
        $this->table_name = 'sl_cronstate'; //create this table before, schema in app.api module manifest
    }

    public function percentage_completed($cron_name)
    {
        $info = $this->get($cron_name);
        $result = [
            'percent' => @$info['percent_completed'],
            'note' => @$info['note'],
            'name' => @$info['name']
        ];
        return $result;
    }

    function set_value($cron_name, $value)
    {
        $this->db->query("UPDATE sl_cronstate SET percent_completed='$value', time='" . Carbon::now()->format('Y-m-d G:i:s') . "' WHERE name='$cron_name'");
        return $this->db->countAffected();
    }

    function set($cron_name, $value, $note = '')
    {
        $this->db->query("UPDATE sl_cronstate SET percent_completed='$value',note='{$note}', time='" . Carbon::now()->format('Y-m-d G:i:s') . "' WHERE name='$cron_name'");
        return $this->db->countAffected();
    }

    private function get($cron_name)
    {
        $row = $this->db->query("SELECT * FROM sl_cronstate WHERE name='$cron_name'")->row;
        $row['time'] = $row['time'] ? \Carbon\Carbon::createFromFormat('Y-m-d G:i:s', $row['time'])->toIso8601String() : '';
        return $row;
    }

    public function reset($cron_name)
    {
        if ($this->db->query("select * from sl_cronstate where name='$cron_name'")->num_rows) {
            $this->db->query("delete from sl_cronstate where name='$cron_name'");
        }
        $this->db->query("insert into sl_cronstate set name='$cron_name', percent_completed=0");

        return $this->db->countAffected();
    }

    public function get_last_time($cron_name)
    {
        $time = $this->db->query("SELECT `time` FROM sl_cronstate WHERE name='$cron_name'")->row['time'];
        return \Carbon\Carbon::createFromFormat('Y-m-d G:i:s', $time)->toIso8601String();
    }

    public function set_time($cron_name, $time_iso_8601_utc)
    {
        $time = \Carbon\Carbon::createFromFormat(\Carbon\Carbon::ISO8601, $time_iso_8601_utc)->format('Y-m-d G:i:s');
        if ($this->db->query("select * from sl_cronstate where name='$cron_name'")->num_rows) {
            $this->db->query("update sl_cronstate set time=$time where name='$cron_name'");
        } else {
            $this->db->query("insert into sl_cronstate set time=$time, name=$cron_name");
        }
    }

}
