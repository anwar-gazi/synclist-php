<?php

class ModelLogSyncList extends Model {
    function recent_cron_logs() {
        $logs = $this->synclist->load->module('cronapp_hanksmineralsebaysalesupdate')->log->cronstate_logs();
        return array_slice($logs, 0, 5);
    }
    function recent_event_logs() {
        $logs = $this->synclist->load->module('cronapp_hanksmineralsebaysalesupdate')->log->event_logs();
        return array_slice($logs, 0, 5);
    }
}