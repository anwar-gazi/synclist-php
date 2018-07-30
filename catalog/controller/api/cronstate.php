<?php

class ControllerApiCronstate extends Controller {
    public function index() {
        
    }
    public function get() {
        $cron = $this->request->request['cron'];
        $this->load->model('cron/progress');
        $state = $this->model_cron_progress->get($cron);
        $this->response->setOutput(json_encode($state));
    }
}