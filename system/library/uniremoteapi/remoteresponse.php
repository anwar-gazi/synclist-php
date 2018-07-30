<?php

namespace resgef\synclist\system\library\uniremoteapi\remoteresponse;

class RemoteResponse
{
    /** @var  integer $http_status_code */
    public $http_status_code;
    /** @var string $error_msg */
    public $error_msg;
    /** @var boolean $success */
    public $success;
    /** @var mixed $data */
    public $data;

    /**
     * RemoteResponse constructor.
     * @param int $http_status_code
     * @param string $error_message
     * @param boolean $success
     * @param mixed $data
     */
    function __construct($http_status_code, $error_message, $success, $data)
    {
        $this->http_status_code = $http_status_code;
        $this->error_msg = $error_message;
        $this->success = $success;
        $this->data = $data;
    }
}