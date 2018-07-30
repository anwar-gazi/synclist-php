<?php
// listing 
class ControllerEbayOrders extends Controller {
    function index() {
        if (!$this->portal_user->isLogged()) {
            $this->load->controller('utils/login_utils/set_referrer', 'common/home');
            //$this->session->flashdata('You have to login first');
            $this->response->redirect($this->url->link('user/login_page', '', 'SSL'));
        }
        
        $data = array();
        
        $data['head_includes'] = $this->load->controller('common/head_includes');
        $data['nav'] = $this->load->controller('common/nav');
        $data['orders_datatable'] = $this->orders_table();
        $data['foot_includes'] = $this->load->controller('common/foot/foot_includes');
        
        $data['breadcrumbs'] = array(
            array( 'title'=>'', 'href'=>$this->url->link('common/home'),'iconclass'=>'glyphicon glyphicon-home' ),
            array( 'title'=>' orders listing', 'href'=>$this->url->link('ebay/orders'),'iconclass'=>'' )
            );
        
        $this->response->setOutput( $this->load->view( $this->config->get('config_template').'/template/ebay/ebay_orders_page.tpl', $data ) );
    }
    
    function orders_table() {
        $data = array();
        $data['orders'] = $this->orders_shortinfo();
        $data['order_url'] = $this->url->link("ebay/vieworder");
        return $this->load->view($this->config->get('config_template').'/template/ebay/orders_data_table.tpl', $data);
    }
    
    function orders_shortinfo() {
        $this->load->model('ebay/order');
        $orders = $this->model_ebay_order->orders_listing();
        $_orders = array();
        foreach($orders as $order) {
            $orderxmlDOM = EbayXmlHelpers\xmlstr_to_xmldom($order['order_xml']);
            $quantity_purchased = 0;
            foreach($orderxmlDOM->TransactionArray->Transaction as $Transaction) {
                $quantity_purchased += (string) $Transaction->QuantityPurchased;
            }
            $order = array(
                'orderid'=> (string) $orderxmlDOM->OrderID,
                'sale_time'=> (string) $orderxmlDOM->CreatedTime,
                'quantity_total'=> $quantity_purchased,
                'price_total'=> (string) $orderxmlDOM->Total.(string) $orderxmlDOM->Total['CurrencyID'],
                'order_status'=> (string) $orderxmlDOM->OrderStatus,
                'checkout_status'=> (string) $orderxmlDOM->CheckoutStatus->Status,
                'payment_time'=> (string) $orderxmlDOM->PaidTime,
                'shipment_time'=> (string) $orderxmlDOM->ShippedTime,
                'shipping_service'=> (string) $orderxmlDOM->ShippingServiceSelected->ShippingService,
            );
            $_orders[] = $order;
        }
        return $_orders;
    }
}