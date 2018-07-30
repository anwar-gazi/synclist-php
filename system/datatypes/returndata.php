<?php

namespace resgef\synclist\system\datatypes\returndata;

class ReturnData
{
    public $success;
    public $error;
    public $msg;
    public $data;

    function __construct($success, $error, $msg, $data)
    {
        $this->success = $success;
        $this->error = $error;
        $this->msg = $msg;
        $this->data = $data;
    }

    function append_data($data)
    {
        if (!$this->data) {
            $this->data = [];
        }
        $this->data[] = $data;
        return $this;
    }

    function data_as_str()
    {
        return json_encode($this->data);
    }
}