<?php

class ControllerPrintShippinglabel extends Controller {
    
    function index() {
        $orderid = $this->request->get['orderid'];
        $template_id = $this->request->get['template_id'];
        $this->load->model('ebay/order');
        
        $data = array();
        $data['head_includes'] = $this->load->controller('common/head_includes');
        $data['assets_url'] = sprintf('%scatalog/view/theme/%s/assets/', HTTP_SERVER, $this->config->get('config_template'));
        $data['orderid'] = $orderid;
        $data['content'] = $this->compile_label($orderid, $template_id);
        $this->response->setOutput($this->load->view( $this->config->get('config_template').'/template/print/order_shipping_label.tpl', $data ));
    }
    private function compile_label($orderid, $template_id) {
        $this->load->model('template/template');
        $this->load->model('ebay/order');

        $template_html = $this->model_template_template->get_html($template_id);
        $template_html = html_entity_decode($template_html);
        
        // now fetch the items table
        $ret = preg_match_all('#<tr>.+?</tr>#s', $template_html, $matches, PREG_SET_ORDER);
        if ($ret !== FALSE) {
            $matchstr = '';
            foreach($matches as $match) {
                $matchstr = $match[0];
                if (strpos($matchstr, 'item.title') !== FALSE) {
                    $replace_str = '{% for item in order_transactions %}'.$matchstr.'{% endfor %}';
                    $template_html = str_replace($matchstr, $replace_str, $template_html);
                }
            }
        }
        $orderxml = $this->model_ebay_order->get_xml($orderid);
        $orderDOM = EbayXmlHelpers\xmlstr_to_xmldom($orderxml);
        
        $data = array();
        $data['sales_record_no'] = (string) $orderDOM->SellingManagerSalesRecordNumber;
        $data['buyer_full_name'] = (string) $orderDOM->ShippingAddress->Name;
        $data['shipping_service'] = (string) $orderDOM->ShippingServiceSelected->ShippingService;
        
        $shipping_address = array(
                                'street1'=> (string) $orderDOM->ShippingAddress->Street1,
                                'street2'=> (string) $orderDOM->ShippingAddress->Street2,
                                'cityname'=> (string) $orderDOM->ShippingAddress->CityName,
                                'state_or_province'=> (string) $orderDOM->ShippingAddress->StateOrProvince,
                                'postalcode'=> (string) $orderDOM->ShippingAddress->PostalCode,
                                'countryname'=> (string) $orderDOM->ShippingAddress->CountryName
                                );
        $_shipping_address = array_filter($shipping_address);
        $data['shipping_address'] = implode(',', $_shipping_address);
        
        $order_transactions = array();
        foreach($orderDOM->TransactionArray->Transaction as $Transaction) {
            $trans = array(
                'itemid'=> (string) $Transaction->Item->ItemID,
                'title'=> (string) $Transaction->Item->Title,
                'quantity_purchased'=> (string) $Transaction->QuantityPurchased,
                'sku'=> (string) $Transaction->Item->SKU,
                'variation_sku'=> '',
                'sales_record_num'=> (string) $Transaction->ShippingDetails->SellingManagerSalesRecordNumber
            );
            $order_transactions[] = $trans;
        }
        $data['order_transactions'] = $order_transactions;
        
        require_once ROOT_DIR.'vendor/autoload.php';
        $env = new Twig_Environment(new Twig_Loader_String());
        return $env->render($template_html, $data);
    }
}