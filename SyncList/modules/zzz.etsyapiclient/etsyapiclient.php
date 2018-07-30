<?php

require_once __DIR__ . '/etsy_api_methods.php';
require_once __DIR__ . '/etsyapiresponse.php';

class EtsyApiClient extends SyncListModule
{
    use EtsyApiMethods;

    /** @var  $api SyncListApi */
    private $api;

    function __construct(SyncListKernel $kernel, stdClass $config, $module_path, SyncListApi $api, $seller, $api_keys)
    {
        parent::__construct($kernel, $config, $module_path);
        $this->api = $api;
        $this->api_keys = $api_keys;
        $this->shop_id = $seller;
    }

}