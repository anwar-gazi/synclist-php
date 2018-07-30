<?php

class Modellogcronstate extends Model
{
    function log($text)
    {
        $this->db->query("insert into sl_synclist_logs set log='{$text}', time='" . \Carbon\Carbon::now()->toISO8601String() . "', type='cronstate'");
    }
}