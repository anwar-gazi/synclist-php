<?php

class ControllerShippingLabel extends Controller
{
    function template_editor()
    {
        $data = $this->load->_controller('common/layout')->context();
        $data['error'] = '';
        $data['form'] = [
            'id' => '',
            'title' => '',
            'html' => ''
        ];
        $id = @$this->request->get['id'] ? filter_var($this->request->get['id'], FILTER_SANITIZE_NUMBER_INT) : '';

        if ($this->request->post) {
            if (@$this->request->post['delete']) {
                $this->synclist_api->ShippingLabel->delete_template($id);
                $this->response->redirect($this->url->link('shipping/label/template_editor'));
                return;
            }
            if (@$this->request->post['save']) {
                $response = call_user_func(function () use ($id) {
                    $response = [
                        'id' => '',
                        'error' => ''
                    ];
                    $title = filter_var($this->request->post['title'], FILTER_SANITIZE_STRING);
                    $html = $this->request->post['html'];
                    if (!$title || !$html) {
                        $response['error'] = 'title or html empty';
                        return $response;
                    }
                    $duplicate_title = call_user_func(function () use ($id, $title) {
                        $row = $this->synclist_api->ShippingLabel->get_by_title($title);
                        if (empty($row)) {
                            return false;
                        } else {
                            if ($row['id'] == $id) {
                                return false;
                            } else {
                                return true;
                            }
                        }
                    });
                    if ($duplicate_title) { //cannot save
                        $response['error'] = 'Error: duplicate title';
                        return $response;
                    }

                    $resp = $this->synclist_api->ShippingLabel->save_template($id, $title, $html);
                    $response['error'] = $resp['error'];
                    $response['id'] = $resp['id'];
                    return $response;
                });

                if (!$response['error'] && !$id) { //insert success
                    $this->response->set('context', $data);
                    $this->response->redirect($this->url->link('shipping/label/template_editor', "&id={$response['id']}"));
                    return;
                } else { //error or update
                    if ($response['error']) { //error in update
                        $data['form'] = [
                            'id' => $id,
                            'title' => $this->request->post['title'],
                            'html' => $this->request->post['html'],
                        ];
                        $data['error'] = $response['error'];
                    } else { //update
                        $data['form'] = $this->synclist_api->ShippingLabel->get_by_id($id);
                    }
                }
            }
        }
        $data['form'] = $id ? $this->synclist_api->ShippingLabel->get_by_id($id) : $data['form'];
        $this->response->set('context', $data);
        $this->response->setOutput($this->load->view('shipping/label_editor.twig', $data));
    }

    function generate_label()
    {
        $tpl_id = $this->request->get['tpl_id'];
        $order_hash = $this->request->get['orderid'];
        $order = $this->synclist_api->Orders->get_order_by_orderid($order_hash);
        $data['order_id'] = $order['id'];
        $tpl = $this->synclist_api->ShippingLabel->get_by_id($tpl_id)['html'];
        $tpl = html_entity_decode(html_entity_decode($tpl));
        $label_html = $this->synclist_api->ShippingLabel->compile($tpl, $order);
        $data['label_html'] = $label_html;
        $this->response->setOutput($this->load->view('shipping/label.twig', $data));
    }

    function delete_template()
    {
        $id = $this->request->post['id'];
        $this->synclist_api->ShippingLabel->delete_template($id);
        $this->response->setOutput($this->load->view('shipping/label_editor.twig', []));
    }
}