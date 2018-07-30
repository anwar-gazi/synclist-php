<?php
namespace ResGef\SyncList\RoboTasks;

use Robo\Common\TaskIO;

class OrderTasks
{
    use \ReflectionFacility;
    use TaskIO;
    
    private $api;
    
    function __construct(\SyncListApi $api)
    {
        $this->api = $api;
    }
    
    public function command__apply_filter() {
        
    }
}