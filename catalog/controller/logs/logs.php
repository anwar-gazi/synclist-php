<?php

class ControllerLogsLogs extends Controller
{
    function logs()
    {
        $data = [];
        $this->response->setOutput($this->load->twig('logs/logs.twig', $data));
    }
}