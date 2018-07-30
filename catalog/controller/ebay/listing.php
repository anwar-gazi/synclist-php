<?php
// listing 
class ControllerEbayListing extends Controller {
    function index() {
        $ebay_user = $this->request->get['user'];
        
        $data = array();
        
        $data['head_includes'] = $this->load->controller('common/head_includes');
        $data['nav'] = $this->load->controller('common/nav');
        $data['foot_includes'] = $this->load->controller('common/foot/foot_includes');
        $data['ebay_user'] = $ebay_user;
        $data['data_table'] = $this->load->controller('ebay/listing/data_table', $ebay_user);
        
        $data['breadcrumbs'] = array(
            array( 'title'=>'', 'href'=>$this->url->link('common/home'),'iconclass'=>'glyphicon glyphicon-home' ),
            array( 'title'=>'items listing', 'href'=>$this->url->link('ebay/listing','user='.$ebay_user),'iconclass'=>'' )
            );
        
        $this->response->setOutput( $this->load->view( $this->config->get('config_template').'/template/ebay/ebay_listing_page.tpl', $data ) );
    }
    
    function data_table($ebay_user) {
        $this->load->model('ebay/listing');
        
        $this->synclist_api->load_api('EbayListing');
        $all_items = $this->synclist_api->EbayListing->all_items();
        $this_seller_items = [];
        foreach ($all_items as $item) {
            if (strtolower($ebay_user)===strtolower($item['SellerUserID'])) {
                $this_seller_items[] = $item;
            }
        }
        
        $data = array();
        $data['ebay_user'] = $ebay_user;
        $data['items'] = $this_seller_items;
        return $this->load->view( $this->config->get('config_template').'/template/ebay/listing_data_table.tpl', $data );
    }
}
