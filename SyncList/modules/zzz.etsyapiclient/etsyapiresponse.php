<?php

require_once dirname(dirname(__DIR__)) . '/system/interfaces/apiresponseinterface.php';

class EtsyApiResponse implements ApiResponseInterface
{
    public $response;

    private $error = '';

    private $response_json = '';

    function __construct($error, $response_json)
    {
        $this->error = $error;
        $this->response_json = $response_json;

        if ($response_json) {
            $this->response = json_decode($response_json);
        }

    }

    public function has_error()
    {
        return $this->error;
    }

    public function as_json()
    {
        return $this->response_json;
    }
}