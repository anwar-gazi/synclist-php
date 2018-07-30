<?php

class SellingStatsTasks
{
    use \ReflectionFacility;
    use \Robo\Common\TaskIO;
    
    private $api;
    
    function __construct(SyncListApi $api)
    {
        $this->api = $api;
    }
    
    public function command__build_stats()
    {
        $stats = array_map(function ($item) {
            return sprintf("#%s average daily selling: %s, stock remain estd: %s days", $item['inv_id'], $item['daily_avg_sold'], $item['estd_remaining_days']);
        }, $this->api->SellingStats->build());
        print("\n" . implode("\n", $stats) . "\n");
    }
}