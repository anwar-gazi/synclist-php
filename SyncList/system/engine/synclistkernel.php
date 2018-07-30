<?php

/**
 * @property SyncListLoader $load Description
 * @property stdClass $config Description
 */
Class SyncListKernel {
    
    private $service;

    function __construct(Array $services = []) {
        foreach ($services as $var=>$val) {
            $this->service[$var] = $val;
        }
    }

    public function inject($name, $thing) {
        $this->service[$name] = $thing;
    }

    function __get($name) {
        return $this->service[$name];
    }

}
