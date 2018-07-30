<?php

/**
 * this is both the homepage and dashbaord
 */
class ControllerCommonHome extends Controller
{

    public function index()
    {
        $this->response->redirect($this->url->link('local/inventory', '', 'SSL'));
    }

}
