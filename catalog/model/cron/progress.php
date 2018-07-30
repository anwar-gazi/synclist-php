<?php

class ModelCronProgress extends Model {
    function get($cron) {
        $sql = "select * from ".CRON_STATE_TABLE." where name='$cron'";
        return $this->db->query($sql)->row;
    }
}