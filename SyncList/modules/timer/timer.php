<?php


class Timer extends SyncListModule {

    /**
     * the start time, currently this is expected to be an Carbon\Carbon object
     */
    private $time_start;
    
    /**
     * the end time, currently this is expected to be an Carbon\Carbon object
     */
    private $time_end;
    
    function __construct($kernel, $config, $module_path, Array $more_dependencies = []) {
        parent::__construct($kernel, $config, $module_path, $more_dependencies);
        $this->start();
    }
    
    /**
     * save the time as cron start time
     */
    function start() {
        $this->time_start = Carbon::now();
    }
    
    /**
     * save the time as cron end time
     */
    function stop() {
        $this->time_end = Carbon::now();
    }
    
    /**
     * the difference between start time and end time in minutes
     */
    function minutes() {
        $time_end = $this->time_end?$this->time_end:Carbon::now();
        return $time_end->diffInMinutes($this->time_start);
    }
    function __get($key) {
        switch($key) {
            case "report":
                $time_start = $this->time_start;
                $time_end = Carbon::now();
                $total_seconds = $time_end->diffInSeconds($time_start);
                $minutes = floor($total_seconds/60);
                $seconds = ($total_seconds%60);
                $value = "$minutes minutes, $seconds seconds";
                break;
        }
        return $value;
    }
    
}

?>