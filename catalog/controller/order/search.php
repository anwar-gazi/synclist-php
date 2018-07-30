<?php

class ControllerOrderSearch extends Controller {

    function index() {
        $data = $this->load->_controller('common/layout')->context();
        $this->response->setOutput($this->load->view('order/search.twig', $data));
    }
    function transactions() {
        //$data = $this->load->_controller('common/layout')->context();
        $this->response->setOutput($this->load->view('order/transactions_search.twig', []));
    }

}
