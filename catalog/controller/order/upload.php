<?php

class ControllerOrderUpload extends Controller {

    function index() {
        $data = array();
        $data['content'] = $this->page_content();
        $data['head_includes'] = $this->load->controller('common/head_includes');
        $data['head_includes_extra'] = '';
        $data['nav'] = $this->load->controller('common/nav');

        $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/common/page_layout.tpl', $data));
    }

    function page_content() {
        $data = [];
        $data['message'] = $this->session->flash();
        $data['upload_url'] = $this->url->link('order/upload/upload');
        return $this->load->view($this->config->get('config_template') . '/template/order/upload.tpl', $data);
    }

    function upload() {
        $this->synclist_api->load_api('EtsyOrders');
        $transactions = Csv::load_file($_FILES["csv"]["tmp_name"], true);

        $save_count = 0;
        foreach ($transactions as $tr) {
            $res = $this->synclist_api->EtsyOrders->save_imported_order($tr[0], $tr[1], $tr[2], $tr[3], $tr[4], $tr[5]);
            $save_count += $res;
        }
        $this->session->flash(count($transactions) . " orders processed, $save_count new entry!");
        header('Location: ' . $this->url->link('order/upload'));
    }

}
