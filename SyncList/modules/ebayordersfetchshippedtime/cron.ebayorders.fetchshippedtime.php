<?php

/**
 * for the orders with no shipped time in current local database, fetch their shipped time
 */
class EbayordersFetchshippedtime extends SyncListModule {

    /** @var SyncListApi */
    private $api;

    /** */
    private $request;

    /** */
    private $response;

    /** */
    private $builder;

    /** */
    private $timer;

    function __construct(\SyncListKernel $kernel, \stdClass $config, $module_path) {
        parent::__construct($kernel, $config, $module_path);
        $this->api = $this->load->module('app.api');
        $this->request = $this->load->module('ebayapirequest', 'hanksminerals', $this->api->api_keys('ebay', 'hanksminerals'));
        $this->response = $this->load->module('ebayapiresponse');
        $this->builder = $this->load->module('ebayapirequestxmlbuilder');
        $this->timer = $this->load->module('timer');
    }

    function run() {
        $not_shipped_OrderID = array_map(function(Array $order) {
            return $order['OrderID'];
        }, $this->api->db->query("select * from {$this->api->table_name('orders')} where `listing_provider`='ebay' and `ShippedTime`=''")->rows);

        $api_keys = $this->api->api_keys('ebay', 'hanksminerals');
        $req_xml_builder = $this->builder->GetOrders;

        print("need to get shipped time for " . count($not_shipped_OrderID) . " ebay orders\n");
        if (empty($not_shipped_OrderID)) {
            return;
        }
        /* now fetch pages, parse, save */
        $saved = 0;
        $total_page = 1;
        for ($PageNumber = 1; $PageNumber <= $total_page; $PageNumber++) {
            print("page:$PageNumber/$total_page\n");
            for ($i = 1; $i <= 3; $i++) { //retry if request fail
                $resp = $this->request->request('GetOrders', $req_xml_builder->specific_orders($not_shipped_OrderID, $api_keys->userToken, $PageNumber));
                if (!$resp && ($i < 3)) {
                    print("empty response, retry ...\n");
                } else {
                    break;
                }
            }
            $this->response->load($resp);
            if ($this->response->error) {
                print($this->response->error . "\n");
                return;
            }
            $total_page = $this->response->total_page;
            foreach ($this->response->OrderArray->Order as $Ordernode) {
                $ShippedTime = (string) $Ordernode->ShippedTime;
                $shipped = $ShippedTime?1:0;
                $OrderID = (string) $Ordernode->OrderID;
                if ($ShippedTime) {
                    $ShippedTime = ServerTime::createFromEbayTime($ShippedTime)->toISO8601String();
                    $local_status = $this->api->Orders->determine_local_status(true, false, true);
                    $this->api->db->query("update {$this->api->table_name('orders')} set ShippedTime='$ShippedTime',shipped='{$shipped}',local_status='$local_status' where OrderID='$OrderID'");
                    $saved++;
                }
            }
        }
        print("$saved orders shipped time updated\n");

        print("cron finished! duration: {$this->timer->report}\n");

        return 0;
    }

}
