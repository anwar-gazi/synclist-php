<?php

class ControllerEbayVieworder extends Controller {
    // view an order
    function index() {
        $orderid = $this->request->get['orderid'];
        $ebay_user = @$this->request->get['user'];
        
        $data = array();
        $data['head_includes'] = $this->load->controller('common/head_includes');
        $data['nav'] = $this->load->controller('common/nav');
        $data['foot_includes'] = $this->load->controller('common/foot/foot_includes');
        $data['orderinfo_html'] = $this->orderinfo_html($orderid);
        $data['orderid'] = $orderid;
        $data['breadcrumbs'] = array(
            array( 'title'=>'', 'href'=>$this->url->link('common/home'),'iconclass'=>'glyphicon glyphicon-home' ),
            array( 'title'=>$ebay_user.' orders listing', 'href'=>$this->url->link('ebay/orders','user='.$ebay_user),'iconclass'=>'' ),
            array( 'title'=>'order#'.$orderid, 'href'=>$this->url->link('ebay/vieworder','orderid='.$orderid.'&user='.$ebay_user),'iconclass'=>'' )
            );
        $data['shippinglabel_preview_url'] = $this->url->link('print/shippinglabel', 'orderid='.$orderid);
        $data['select_template_modal_html'] = $this->template_list_modal();
        $this->response->setOutput($this->load->view( $this->config->get('config_template').'/template/ebay/vieworder.tpl', $data ));
    }
    
    private function template_list_modal() {
        $this->load->model("template/template");
        $data = array();
        $data['template_names'] = $this->model_template_template->list_template_names();
        return $this->load->view( $this->config->get('config_template').'/template/ebay/modals/modal_select_template.tpl', $data );
    }
    
    /**
     * build a order info page html
     */
    private function orderinfo_html($orderid) {
        $data = array();
        $this->load->model("ebay/order");
        $this->load->model("template/template");
        $order = $this->model_ebay_order->pull_order($orderid);
        $orderDOM = EbayXmlHelpers\xmlstr_to_xmldom($order['order_xml']);
        $order = array();
        
        $data['template_names'] = $this->model_template_template->list_template_names();
        
        $order['orderid'] = (string) $orderDOM->OrderID;
        $order['selling_manager'] = (string) $orderDOM->SellingManagerSalesRecordNumber;
        
        $order['sale_time'] = (string) $orderDOM->CreatedTime;
        $order['update_time'] = (string) $orderDOM->CheckoutStatus->LastModifiedTime;
        
        $order['order_status_checkout'] = (string) $orderDOM->CheckoutStatus->Status;
        $order['order_status_payment'] = empty($orderDOM->PaidTime)?'':'complete';
        $order['order_status_shipped'] = empty($orderDOM->ShippedTime)?'':'complete';
        
        $order['seller'] = (string) $orderDOM->SellerUserID;
        
        $order['buyer_userid'] = (string) $orderDOM->BuyerUserID;
        $order['buyer_name'] = (string) $orderDOM->ShippingAddress->Name;
        $order['buyer_email'] = (string) $orderDOM->TransactionArray->Transaction[0]->Buyer->Email;
        $order['shipping_address'] =(string) $orderDOM->ShippingAddress->Street1.
                                    (string) $orderDOM->ShippingAddress->Street2.
                                    (string) $orderDOM->ShippingAddress->CityName.
                                    (string) $orderDOM->ShippingAddress->StateOrProvince.
                                    (string) $orderDOM->ShippingAddress->PostalCode.
                                    (string) $orderDOM->ShippingAddress->CountryName;
        $order['currency'] = $orderDOM->AmountPaid['CurrencyID'];
        $order['payment_date'] = (string) $orderDOM->PaidTime;
        $order['payment_method'] = (string) $orderDOM->CheckoutStatus->PaymentMethod;
        $order['ebay_final_fee'] = (string) $orderDOM->BuyerUserID;
        
        $order['ebay_final_fee'] = (string) $orderDOM->BuyerUserID;
        
        $transactions = array();
        $items = array();
        $ebay_final_fee = 0;
        foreach($orderDOM->TransactionArray->Transaction as $Transaction) {
            $ebay_final_fee += (string) $Transaction->FinalValueFee;
            $transactions[] = array(
                'transactionid'=> (string) $Transaction->TransactionID,
                'transaction_price'=> (string) $Transaction->TransactionPrice,
                'amount_currency'=> (string) $Transaction->TransactionPrice['CurrencyID'],
                'create_date'=> (string) $Transaction->CreatedDate,
                'final_value_fee'=> (string) $Transaction->FinalValueFee
            );
            //$item_info = $this->model_ebay_listing->get_item((string) $Transaction->Item->ItemID);
            $items[] = array(
                'itemid'=> (string) $Transaction->Item->ItemID,
                'title'=> (string) $Transaction->Item->Title,
                'quantity_purchased'=> (string) $Transaction->Item->QuantityPurchased,
                'item_price'=> 'ble ble ble',
                'transaction_price'=> (string) $Transaction->TransactionPrice
            );
        }
        $order['ebay_final_fee'] = $ebay_final_fee;
        
        $order['Transactions'] = $transactions;
        $order['transaction_items'] = $items;
        
        $order['shipping_service_name'] = (string) $orderDOM->ShippingServiceSelected->ShippingService;
        $order['shipping_cost'] = (string) $orderDOM->ShippingServiceSelected->ShippingServiceCost;
        $order['shipping_time'] = (string) $orderDOM->ShippedTime;
        
        $order['subtotal'] = (string) $orderDOM->Subtotal;
        $order['shipping_cost'] = (string) $orderDOM->ShippingServiceSelected->ShippingServiceCost;
        $order['grand_total'] = (string) $orderDOM->Total;
        $order['amount_paid'] = (string) $orderDOM->AmountPaid;
        $order['amount_saved'] = (string) $orderDOM->AmountSaved;
        
        $data['order'] = $order;
        
        return $this->load->view( $this->config->get('config_template').'/template/ebay/vieworder_data.tpl', $data );
    }
}