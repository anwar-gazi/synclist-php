<?php

class SyncListModule {

    /** @var stdClass */
    public $config;

    /** @var SyncListLoader */
    public $load;

    function __construct(SyncListKernel $kernel, stdClass $config, $module_path) {
        $this->config = $config;
        $this->load = $kernel->load;
        if (!property_exists($this->config, 'path')) {
            $this->config->path = new stdClass();
        }
        $this->config->path->root = $module_path;
        $this->config->name = basename($module_path);
    }

}
