<?php

/**
 * for ebay seller Hanksminerals
 * fetch active items listing and transactions
 */
class HanksmineralsEbaySalesUpdateCron extends SyncListModule
{

    /** @var SyncListApi */
    private $api;

    /** @var DBMySQLi */
    public $db;

    /** @var EbayApiRequest */
    private $request;

    /** @var EbayApiResponse */
    private $response;

    /** @var EbayApiRequestXmlBuilder */
    private $builder;

    /** @var Logger */
    public $log;

    function __construct(\SyncListKernel $kernel, $config, $module_path)
    {
        parent::__construct($kernel, $config, $module_path);
        set_error_handler(get_class() . '::pretty_error', E_USER_NOTICE | E_USER_WARNING | E_WARNING | E_NOTICE);

        $this->api = $this->load->module('app.api');
        $this->db = $this->api->db;
        $this->request = $this->load->module('ebayapirequest', 'hanksminerals', $this->api->api_keys('ebay', 'hanksminerals'));
        $this->response = $this->load->module('ebayapiresponse');
        $this->builder = $this->load->module('ebayapirequestxmlbuilder');

        $this->log = $this->load->module('logger', $this->db, $this->api->table_name('synclist_logs'));
    }

    /**
     * execute the purpose of this module:
     * fetch active items info with sales figure and
     * number of transactions
     */
    public function run()
    {
        /** items in database at this moment */
        $existing_listing_items_active = $this->api->db->query("SELECT * FROM sl_listings WHERE active=1 AND listing_provider='ebay'")->rows;

        $now = ServerTime::now()->toISO8601String();
        /**
         * now fetch active items
         */
        $api_keys = $this->api->api_keys('ebay', 'hanksminerals');
        $req_xml_builder = $this->builder->GetMyEbaySelling;
        $total_page = 1;
        $fetched_listing_items = [];
        for ($PageNumber = 1; $PageNumber <= $total_page; $PageNumber++) {
            print("making request GetMyEbaySelling ... \n");

            /** fetch GetMyEbaySelling activeitems pages */
            for ($i = 1; $i <= 3; $i++) {
                $resp = $this->request->request('GetMyeBaySelling', $req_xml_builder->activeitems_only($api_keys->userToken, $PageNumber)->xml);
                if (!$resp && ($i < 3)) {
                    print("empty response, retry ...\n");
                } else {
                    break;
                }
            }

            $this->response->load($resp);
            if ($this->response->error) {
                print($this->response->error . "\n");
                break;
            }

            /** @var $total_page total page to fetch */
            $total_page = $this->response->total_page_activelist;

            /** now parse and save */
            foreach ($this->response->ActiveList->ItemArray->Item as $node) {
                $info['itemid'] = (string)$node->ItemID;
                $info['option'] = '';
                $info['sku'] = (string)$node->SKU;
                $info['uniqid'] = preg_replace('#\s+#', '', 'ebay' . $info['itemid'] . (!empty($node->SKU) ? '-' . $node->SKU : ''));
                $info['has_variations'] = 0;
                $info['title'] = (string)$node->Title;
                $info['pic_url'] = (string)$node->PictureDetails->GalleryURL;
                $info['item_url'] = (string)$node->ListingDetails->ViewItemURL;
                $quantity = (string)$node->Quantity;
                $available = (string)$node->QuantityAvailable;
                $sold = $quantity - $available;
                $info['quantity'] = $quantity;
                $info['sold'] = $sold;
                $info['salesdata_save_time'] = $now;
                $info['listing_provider'] = 'ebay';
                $info['active'] = 1;
                $info['seller'] = 'Hanksminerals';

                if ($node->Variations) { //get variations
                    print(count($node->Variations->Variation) . " variations\n");
                    foreach ($node->Variations->Variation as $var_node) { //each variation
                        $var_item = $info;
                        $var_item['title'] = !empty($var_node->VariationTitle) ? (string)$var_node->VariationTitle : $var_item['title'];
                        $var_item['option'] = call_user_func(function () use (&$var_node) {
                            $option2 = '';
                            foreach ($var_node->VariationSpecifics->NameValueList as $prop) {
                                $option2 .= strtolower($prop->Name . ':' . $prop->Value . ',');
                            }
                            $option = rtrim($option2, ',');
                            return $option;
                        });
                        $var_item['sku'] = (string)$var_node->SKU;
                        $var_item['uniqid'] = preg_replace('#\s+#', '', 'ebay' . $info['itemid'] . '-' . $var_node->SKU);
                        $var_item['quantity'] = (string)$var_node->Quantity;
                        $var_item['sold'] = (string)$var_node->SellingStatus->QuantitySold;
                        $fetched_listing_items[] = $var_item;
                    }
                } else {
                    $fetched_listing_items[] = $info;
                }
            }
        }

        /** no active items found, end execution */
        if (empty($fetched_listing_items)) {
            $fail_report = "listing empty, abort";
            trigger_error($fail_report);
            die();
        }

        /** now save the items */
        foreach ($fetched_listing_items as $set) {
            $this->api->Listing->save_item($set);
        }

        /**
         * now show some good deal of info
         */
        $fetched_listing_items_indexed = [];
        foreach ($fetched_listing_items as $item) {
            $fetched_listing_items_indexed[$item['uniqid']] = $item;
        }

        $existing_listing_items_active_indexed = [];
        foreach ($existing_listing_items_active as $item) {
            $existing_listing_items_active_indexed[$item['uniqid']] = $item;
        }

        /** determine which are to insert, which to update */
        $changelog = ['insert' => [], 'update' => []];
        foreach ($fetched_listing_items_indexed as $f_uniqid => $item) {
            if (!array_key_exists($f_uniqid, $existing_listing_items_active_indexed)) { // new
                $changelog['insert'][] = $item;
            } else {
                $changelog['update'][] = $item;
            }
        }

        /** stale items are those in database, no longer returned as active in the fetch */
        $stale_items = [];
        foreach ($existing_listing_items_active_indexed as $e_uniqid => $item) {
            if (!array_key_exists($e_uniqid, $fetched_listing_items_indexed)) { // stale
                $stale_items[] = $item;
            }
        }

        /** empty the status of the stale items */
        foreach ($stale_items as $item) {
            $this->api->db->query("update " . $this->api->table_name('listings') . " set active=0 where uniqid='{$item['uniqid']}'");
            print("item {$item['uniqid']} made hidden\n");
        }

        $fetch_report = sprintf(
            "%s active items just fetched: %s to update, %s new entries."
            . "\n%s previously active items in database."
            . "\n%s stale items in database, they will be hidden(not active)"
            , count($fetched_listing_items), count($changelog['update']), count($changelog['insert']), count($existing_listing_items_active), count($stale_items)
        );
        print($fetch_report . "\n");

        /** now fetch transactions */
        #determine last fetch time of active items
        $save_times = \array_map(function (Array $item) {
            return $item['salesdata_save_time'];
        }, $existing_listing_items_active);
        \sort($save_times);
        $time_from = \reset($save_times);

        $intervals = [];
        if (!$time_from) {
            $time_from = ServerTime::createFromISO8601String($now)->subDays(30)->toISO8601String();
            $intervals[] = [
                'from' => $time_from,
                'to' => $now
            ];
        } elseif (ServerTime::diffInDaysFromFormat($time_from, $now) <= 30) { //in 30 days
            $intervals[] = [
                'from' => $time_from,
                'to' => $now
            ];
        } else { //exceeds 30 days
            $intervals = ServerTime::build_ebay_intervals($time_from, $now, 30);
        }

        if (!count($intervals)) {
            trigger_error("cannot build time intervals with time_from:$time_from, now:$now", E_USER_ERROR);
            return 0;
        }

        print("fetching transactions from:$time_from to:$now:" . ServerTime::diffForHumansFromFormat($time_from, $now) . ", in " . count($intervals) . " time interval\n");
        $total_trans = 0;
        foreach ($intervals as $intervl) { //each time intervals
            $from = $intervl['from'];
            $to = $intervl['to'];
            print("from $from to $to:" . ServerTime::diffForHumansFromFormat($from, $to) . "\n");
            $trans_req_xml_builder = $this->builder->GetSellerTransactions;
            $trans_total_page = 1;
            for ($PageNumber = 1; $PageNumber <= $trans_total_page; $PageNumber++) {
                print("page $PageNumber/$trans_total_page\n");
                $time_from_ebay = ServerTime::createFromISO8601String($from)->toEbayString();
                $time_to_ebay = ServerTime::createFromISO8601String($to)->toEbayString();
                $resp = $this->request->request('GetSellerTransactions', $trans_req_xml_builder->transactions($api_keys->userToken, $time_from_ebay, $time_to_ebay, $PageNumber)->xml);
                $this->response->load($resp);
                if ($this->response->error) {
                    trigger_error($this->response->error, E_USER_ERROR);
                }
                $trans_total_page = $this->response->total_page;
            }
            print(count($this->response->TransactionArray->Transaction) . " transactions\n");
            $total_trans += count($this->response->TransactionArray->Transaction);
        }

        $cron_report = "$total_trans new transactions in past " . ServerTime::diffForHumansFromFormat($time_from, $now);

        print("$cron_report\n");

        $this->log->log_cronstate($cron_report);

        $this->api->SellingStats->save();

        return 0;
    }

    /**
     * the error handler
     * @param integer $errno
     * @param string $errstr
     * @param string $errfile
     * @param integer $errline
     */
    static function pretty_error($errno, $errstr, $errfile, $errline)
    {
        $const = get_defined_constants();
        $errname = array_search($errno, $const);
        $file = str_replace(realpath(''), '.', $errfile);
        print("$errname: $errstr [in $file:$errline]\n");
    }

    function __destruct()
    {
        restore_error_handler();
    }

}
