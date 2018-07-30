<?php

/**
 * fetch current active listing,
 * fetch transactions under each listing, then calculate item sold by adding transaction->quantity
 * etsy doesnt provide number sold for listing items
 */
use Guzzle\Http\Client;
use Guzzle\Plugin\Oauth\OauthPlugin;

/**
 * fetch etsy listing and stock and sales
 */
class CronEtsyListing extends SyncListModule {

    private $api;

    function __construct(\SyncListKernel $kernel, \stdClass $config, $module_path) {
        parent::__construct($kernel, $config, $module_path);
        $this->api = $this->load->module('app.api');
    }

    /**
     * currently we are not accounting for errors, paginations ... etc
     * uniqid pattern: etsy<listing_id>
     * uniqid pattern for variation: etsy<listing_id>opt<property_id><value_id>
     */
    function run() {
        $this->load->library('http');
        $this->api->load_api('EtsyListing');
        $keys = $this->api->api_keys('etsy', 'hanksminerals');
        print("fetching hanksminerals etsy store active listing items\n");
        $resp_json = Http::request("https://openapi.etsy.com/v2/shops/hanksminerals/listings/active?api_key=" . $keys->keystring . "&includes=MainImage");
        
        $response = json_decode($resp_json);
        print("{$response->count} active items\n");
        $items = [];
        /** list the items and variations */
        foreach ($response->results as $item) {
            $uniqid = 'etsy' . $item->listing_id;
            $sold = $this->fetch_item_sold($item->listing_id);
            $data = [
                'uniqid' => $uniqid, //this is how uniqid is created
                'itemid' => $item->listing_id,
                'option' => '',
                'active' => 1,
                'seller' => 'hanksminerals',
                'title' => $item->title,
                'pic_url' => $item->MainImage->url_fullxfull,
                'item_url' => $item->url,
                'sold' => $sold->{$item->listing_id},
                'listing_provider' => 'etsy'
            ];

            if ($item->quantity) {
                $data['quantity'] = $item->quantity;
            }

            if ($item->has_variations) {
                print("getting variations\n");
                $options = $this->get_variations($item->listing_id);
                print("item {$item->listing_id} has " . count($options) . " variations\n");
                foreach ($options as $opt) { //each variation item
                    $varitem = $data;
                    $option2 = "";
                    foreach ($opt as $key => $val) { //each property of the variation
                        $option2 .= strtolower($key) . ":" . strtolower($val) . ",";
                    }
                    $option = rtrim($option2, ",");
                    $varitem['sold'] = $sold->$option;
                    //print("option is $option, find inside \n");print_r($sold);
                    $varitem['option'] = $option;
                    $varitem['uniqid'] = $data['uniqid'] . '-' . substr(md5($option), 0, 8);
                    $items[] = $varitem;
                }
            } else {
                $items[] = $data;
            }
        }
        /** now save */
        print("total " . count($items) . " items\n");
        foreach ($items as $data) {
//            /print_r($data);
            /** now save */
            $table = $this->api->table_name('listings');
            $set = [];
            foreach ($data as $key => $val) {
                $set[] = "`$key`='{$this->api->db->escape($val)}'";
            }
            $item_exist = $this->api->db->query("select * from `$table` where `uniqid`='{$data['uniqid']}'")->num_rows;
            if ($item_exist) {
                $sql = "update `$table` set " . implode(',', $set) . " where uniqid='{$data['uniqid']}'";
            } else {
                $sql = "insert into `$table` set " . implode(',', $set);
            }
            $this->api->db->query($sql);
        }
    }

    private function fetch_item_sold($listing_id) {
        print("fetching item $listing_id transactions to calculate sold\n");
        $client = new Client('https://openapi.etsy.com/v2/');
        $keys = $this->api->api_keys('etsy', 'hanksminerals');
        $oauth = new OauthPlugin(array(
            'consumer_key' => $keys->keystring,
            'consumer_secret' => $keys->shared_secret,
            'token' => $keys->oauth_token,
            'token_secret' => $keys->oauth_token_secret
        ));
        $client->addSubscriber($oauth);

        $sold = new stdClass();
        $sold->$listing_id = 0;

        $total = 100; //initial, actual value coming later
        for ($offset = 0; ($offset <= $total); $offset = $offset + 100) { //fetch all pages
            $trans_json = $client->get("listings/$listing_id/transactions?limit=100&offset=$offset")->send()->getBody();
            $item_trans = json_decode($trans_json);
            $total = $item_trans->count;
            //print("item $listing_id transactions fetched: ".count($item_trans->results)."\n");
            foreach ($item_trans->results as $trans) {
                if ($trans->variations) {
                    $opt_arr = [];
                    /* make key=>value air of attributes*/
                    foreach ($trans->variations as $var) { //calculate for the variations
                        $opt_arr[$var->formatted_name] = $var->formatted_value;
                    }
                    ksort($opt_arr);
                    $option2 = '';
                    foreach ($opt_arr as $key => $val) {
                        $option2 .= strtolower($key . ':' . $val . ',');
                    }
                    $option = rtrim($option2, ',');
                    if (!property_exists($sold, $option)) {
                        $sold->$option = 0;
                    }
                    if ($trans->quantity) {
                        $sold->$option += $trans->quantity;
                    }
                } else {
                    if ($trans->quantity) {
                        $sold->$listing_id += $trans->quantity;
                    }
                }
            }
        }
        return $sold;
    }

    private function get_variations($listing_id) {
        $keys = $this->api->api_keys('etsy', 'hanksminerals');
        $client = new Client('https://openapi.etsy.com/v2');
        $oauth = new OauthPlugin(array(
            'consumer_key' => $keys->keystring,
            'consumer_secret' => $keys->shared_secret,
            'token' => $keys->oauth_token,
            'token_secret' => $keys->oauth_token_secret
        ));
        $client->addSubscriber($oauth);
        $r = $client->get("listings/$listing_id/variations")->send();
        $vars = json_decode($r->getBody());
        $opt = [];
        foreach ($vars->results as $vi => $vars_by_cat) { //each category
            $name = strtolower($vars_by_cat->formatted_name);
            $opt2 = [];
            foreach ($vars_by_cat->options as $var) { //each value
                $value = trim(preg_replace('#\[.+\]?#', '', strtolower($var->formatted_value)));
                if ($vi == 0) {
                    $opt2[] = [$name => $value];
                } else {
                    foreach ($opt as $o) {
                        $o[$name] = $value;
                        ksort($o);
                        $opt2[] = $o;
                    }
                }
            }
            $opt = $opt2;
        }
        return $opt;
    }

}
