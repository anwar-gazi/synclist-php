<?php

/**
 * fetch orders(and transactions) for ebay seller Hanksminerals
 * we convert ebay's provided time to php's iso8601 format to save at database
 */
class CronEbayHanksmineralsOrders extends SyncListModule
{

    /** @var SyncListApi $api */
    private $api;

    private $timer;
    private $request;
    private $response;
    private $builder;

    function __construct(\SyncListKernel $kernel, \stdClass $config, $module_path)
    {
        parent::__construct($kernel, $config, $module_path);

        $this->api = $this->load->module('app.api');

        /** @var EbayApiRequest request */
        $this->request = $this->load->module('ebayapirequest', 'hanksminerals', $this->api->api_keys('ebay', 'hanksminerals'));
        /** @var EbayApiResponse response */
        $this->response = $this->load->module('ebayapiresponse');
        /** @var EbayApiRequestXmlBuilder builder */
        $this->builder = $this->load->module('ebayapirequestxmlbuilder');
        /** @var Timer timer */
        $this->timer = $this->load->module('timer');
    }

    private function orders_lastfetch_time()
    {
        //pull the latest save_time
        $row = $this->api->db->query("SELECT DISTINCT(save_time) FROM sl_orders WHERE listing_provider='ebay' ORDER BY str_to_date(save_time,'%Y-%m-%dT%T') DESC LIMIT 1")->row;
        $orders_lft = (array_key_exists('save_time', $row) ? $row['save_time'] : '');
        return $orders_lft;
    }

    /**
     * run the cron
     * if last fetched orders save_time is empty, it asserts current time past 120 days
     * if the last fetched orders save_time to now is more than 120 days,
     * then the date range is sliced into multiple intervals
     */
    function run()
    {
        $time_from = $this->orders_lastfetch_time();
        $now = ServerTime::now()->toISO8601String();
        $intervals = [];
        if (!$time_from) {
            print("asserting time_from as 120 days past now\n");
            $time_from = ServerTime::now()->subDays(120)->toISO8601String();
            $intervals[] = [
                'from' => $time_from,
                'to' => $now
            ];
        } elseif (ServerTime::createFromISO8601String($now)->diffInDays(ServerTime::createFromISO8601String($time_from)) <= 120) {
            $intervals[] = [
                'from' => $time_from,
                'to' => $now
            ];
        } else {
            $intervals = ServerTime::build_ebay_intervals($time_from, $now, 120);
        }

        $api_keys = $this->api->api_keys('ebay', 'hanksminerals');
        /** @var GetOrdersRequestXmlBuilder $req_xml_builder */
        $req_xml_builder = $this->builder->GetOrders;

        print("fetching orders from $time_from to $now:" . ServerTime::diffForHumansFromFormat($now, $time_from) . " in " . count($intervals) . " interval" . (count($intervals) > 1 ? 's' : '') . "\n");

        $orders_count = 0;
        foreach ($intervals as $int_ind => $interval) {
            $from = $interval['from'];
            $to = $interval['to'];
            $total_page = 1;
            print('interval ' . ($int_ind + 1) . '/' . count($intervals) . "# $from to $to:" . ServerTime::diffForHumansFromFormat($to, $from) . "\n");
            for ($PageNumber = 1; $PageNumber <= $total_page; $PageNumber++) {
                print("page:$PageNumber/$total_page ");
                /** fetch GetMyEbaySelling activeitems pages */
                for ($i = 1; $i <= 3; $i++) {
                    $time_from_ebay = ServerTime::createFromISO8601String($from)->toEbayString();
                    $now_ebay = ServerTime::createFromISO8601String($to)->toEbayString();
                    $resp = $this->request->request('GetOrders', $req_xml_builder->all_orders($time_from_ebay, $now_ebay, $api_keys->userToken, $PageNumber)->xml);
                    if (!$resp && ($i < 3)) {
                        print("empty response, retry ...\n");
                    } else {
                        $this->response->load($resp);
                        break;
                    }
                }

                if ($this->response->error) {
                    print($this->response->error . "\n");
                    break;
                }

                /** @var integer $total_page total page to fetch */
                $total_page = $this->response->total_page;

                /** now parse and save */
                $num_trans_fetched = call_user_func(function () {
                    $c = 0;
                    foreach ($this->response->OrderArray->Order as $o) {
                        $c += count($o->TransactionArray->Transaction);
                    }
                    return $c;
                });
                print(count($this->response->OrderArray->Order) . " orders, $num_trans_fetched transactions fetched\n");
                $orders_count += count($this->response->OrderArray->Order);
                $shipped_count = 0;
                foreach ($this->response->OrderArray->Order as $node) { //each order
                    $order_info = [
                        'save_time' => $now,
                        'listing_provider' => 'ebay',
                        'OrderID' => (string)$node->OrderID,
                        'order_hash' => (string)$node->ShippingDetails->SellingManagerSalesRecordNumber,
                        'BuyerUserID' => (string)$node->BuyerUserID,
                        'SellerUserID' => (string)$node->SellerUserID,
                        'EIASToken' => (string)$node->EIASToken,
                        'Total' => (string)$node->Total,
                        'AmountPaid' => (string)$node->AmountPaid,
                        'currencyID' => 'USD',
                        'PaymentMethods' => '',
                        'OrderStatus' => (string)$node->OrderStatus,
                        'eBayPaymentStatus' => (string)$node->CheckoutStatus->eBayPaymentStatus,
                        'CheckoutLastModifiedTime' => empty($node->CheckoutStatus->LastModifiedTime) ? '' : ServerTime::createFromEbayTime((string)$node->CheckoutStatus->LastModifiedTime)->toISO8601String(),
                        'PaymentMethod' => (string)$node->CheckoutStatus->PaymentMethod,
                        'CheckoutStatus' => (string)$node->CheckoutStatus->Status,
                        'ShippingName' => (string)$node->ShippingAddress->Name,
                        'ShippingStreet1' => (string)$node->ShippingAddress->Street1,
                        'ShippingStreet2' => (string)$node->ShippingAddress->Street2,
                        'ShippingCityName' => (string)$node->ShippingAddress->CityName,
                        'ShippingStateOrProvince' => (string)$node->ShippingAddress->StateOrProvince,
                        'ShippingCountry' => (string)$node->ShippingAddress->Country,
                        'ShippingCountryName' => (string)$node->ShippingAddress->CountryName,
                        'ShippingPhone' => (string)$node->ShippingAddress->Phone,
                        'ShippingPostalCode' => (string)$node->ShippingAddress->PostalCode,
                        'ShippingService' => (string)$node->ShippingServiceSelected->ShippingService,
                        'ShippingServiceCost' => (string)$node->ShippingServiceSelected->ShippingServiceCost,
                        'SalesRecordNumber' => (string)$node->ShippingDetails->SellingManagerSalesRecordNumber,
                        'CreatedTime' => empty($node->CreatedTime) ? '' : ServerTime::createFromEbayTime((string)$node->CreatedTime)->toISO8601String(),
                        'PaidTime' => empty($node->PaidTime) ? '' : ServerTime::createFromEbayTime((string)$node->PaidTime)->toISO8601String(),
                        'ShippedTime' => empty($node->ShippedTime) ? '' : ServerTime::createFromEbayTime((string)$node->ShippedTime)->toISO8601String(),
                        'paid' => ((string)$node->PaidTime) ? 1 : '',
                        'shipped' => empty($node->ShippedTime) ? '' : 1,
                        'note' => empty($node->BuyerCheckoutMessage) ? '' : (string)$node->BuyerCheckoutMessage,
                    ];

                    $order_info['local_status'] = $this->api->Orders->determine_local_status(!EbayOrder::is_cancelled($order_info['OrderStatus']), EbayOrder::is_waiting_on_customer_response($order_info['OrderStatus']), $order_info['shipped'] == 1);

                    if ($order_info['shipped']) {
                        $shipped_count++;
                    }

                    /* save the order */
                    $this->api->Orders->save_order($order_info);

                    foreach ($node->TransactionArray->Transaction as $tnode) { //each transaction node
                        $transaction = [
                            'TransactionID' => (string)$tnode->TransactionID,
                            'OrderLineItemID' => (string)$tnode->OrderLineItemID,
                            'OrderID' => $order_info['OrderID'],
                            'ItemID' => $this->parse_ItemID($tnode),
                            'Title' => (string)$tnode->Item->Title,
                            'option' => (string)$tnode->Item->ConditionDisplayName,
                            'sku' => (string)$tnode->Item->SKU,
                            'SalesRecordNumber' => (string)$tnode->ShippingDetails->SellingManagerSalesRecordNumber,
                            'BuyerEIASToken' => (string)$tnode->Buyer->EIASToken,
                            'BuyerEmail' => (string)$tnode->Buyer->Email,
                            'QuantityPurchased' => (string)$tnode->QuantityPurchased,
                            'TransactionPrice' => (string)$tnode->TransactionPrice,
                            'EstimatedDeliveryTimeMin' => '',
                            'EstimatedDeliveryTimeMax' => '',
                            'listing_provider' => 'ebay'
                        ];
                        $transaction['item_uniqid'] = preg_replace('#\s+#', '', 'ebay' . $transaction['ItemID']);

                        /** save transaction */
                        $this->api->Orders->save_transaction($transaction);
                    }
                }
                print("$shipped_count shipped\n");
            }
        }

        $cron_report = "duration: {$this->timer->report}. $orders_count orders in past " . ServerTime::createFromISO8601String($now)->diffForHumans2(ServerTime::createFromISO8601String($time_from));

        print("\n$cron_report\n");

        return 0;
    }

    /**
     * @param SimpleXMLElement $Transaction_node
     * @return mixed|string
     */
    private function parse_ItemID(SimpleXMLElement $Transaction_node)
    {
        $ItemID = '';
        if (!empty($Transaction_node->Item->ItemID)) {
            $ItemID = (string)$Transaction_node->Item->ItemID;
        } elseif (!empty($Transaction_node->OrderLineItemID)) {
            $orderlineitemid = (string)$Transaction_node->OrderLineItemID;
            $matches = [];
            preg_match('#^([\d]+)-[\d]+$#', $orderlineitemid, $matches);
            $ItemID = $matches[1];
        } elseif (!empty($Transaction_node->ExtendedOrderID)) {
            preg_match('#^([\d]+)-[\d]+![\d]+$#', (string)$Transaction_node->ExtendedOrderID, $matches);
            $ItemID = $matches[1];
        }
        return $ItemID;
    }

}
