<?php
/**
 * fetch receipts(etsy doesnt provide orders api)
 * from the receipts list orders, transactions
 */

namespace resgef\synclist\cron\etsyorders;

use Carbon\Carbon;
use Guzzle\Http\Client;
use Guzzle\Plugin\Oauth\OauthPlugin;
use ResGef\SyncList\System\Helper\EtsyItemHelper\EtsyItemHelper;
use resgef\synclist\system\interfaces\croninterface\CronInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class EtsyOrders
 * @package resgef\synclist\cron\etsyorders
 * @property \Modeletsyapikeys $model_etsy_apikeys
 */
class EtsyOrders extends \Controller implements CronInterface
{
    function execute()
    {
        $output = new ConsoleOutput();
        $output->writeln("<info>etsy orders fetch</info>");
        $this->load->model('etsy/apikeys');
        foreach ($this->model_etsy_apikeys->all() as $keys) {
            $client = new Client('https://openapi.etsy.com/v2/shops/hanksminerals/');
            $oauth = new OauthPlugin(array(
                'consumer_key' => $keys->key_string,
                'consumer_secret' => $keys->shared_secret,
                'token' => $keys->oauth_token,
                'token_secret' => $keys->oauth_token_secret
            ));
            $client->addSubscriber($oauth);

            /** fetch receipts */
            $total = 100;
            for ($offset = 0; ($offset <= $total); $offset = $offset + 100) {
                //sort as newest orders first
                $resp_json = $client->get("receipts?limit=100&offset=$offset&includes=Listings,Transactions,Country")->send()->getBody();
                $resp = json_decode($resp_json);
                $total = $resp->count;
                $output->writeln("fetched $offset-" . ($offset + 100) . ":" . count($resp->results) . " receipts " . " of $total, ");
                $now = Carbon::now()->toISO8601String();
                $orders = [];
                $transactions = [];
                foreach ($resp->results as $r) { //each order receipt
                    $order = [
                        'account_name' => $keys->account_name,
                        'save_time' => $now,
                        'listing_provider' => 'etsy',
                        'OrderID' => $r->receipt_id,
                        'order_hash' => $r->receipt_id,
                        'ExtendedOrderID' => '',
                        'BuyerUserID' => $r->buyer_user_id,
                        'SellerUserID' => $r->seller_user_id,
                        'EIASToken' => '',
                        'Total' => $r->grandtotal,
                        'AmountPaid' => '',
                        'currencyID' => $r->currency_code,
                        'PaymentMethods' => '',
                        'OrderStatus' => '',
                        'eBayPaymentStatus' => '',
                        'CheckoutLastModifiedTime' => Carbon::createFromTimestampUTC($r->last_modified_tsz)->toISO8601String(),
                        'PaymentMethod' => $r->payment_method,
                        'CheckoutStatus' => '',
                        'ShippingName' => $r->name,
                        'ShippingStreet1' => $r->first_line,
                        'ShippingStreet2' => $r->second_line,
                        'ShippingCityName' => $r->city,
                        'ShippingStateOrProvince' => $r->state,
                        'ShippingCountry' => $r->Country->iso_country_code, //country numeric code
                        'ShippingCountryName' => $r->Country->name,
                        'ShippingPhone' => '',
                        'ShippingPostalCode' => $r->zip,
                        'ShippingService' => $r->shipping_details->shipping_method,
                        'ShippingServiceCost' => $r->total_shipping_cost,
                        'SalesRecordNumber' => '',
                        'CreatedTime' => Carbon::createFromTimestampUTC($r->creation_tsz)->toISO8601String(),
                        'PaidTime' => '',
                        'ShippedTime' => '',
                        'paid' => $r->was_paid,
                        'shipped' => $r->was_shipped,
                        'note' => $r->message_from_buyer,
                        'local_status' => 'Ready'
                    ];
                    $orders[] = $order;

                    foreach ($r->Transactions as $trans) {
                        $transaction = [
                            'TransactionID' => $trans->transaction_id,
                            'OrderLineItemID' => '',
                            'ExtendedOrderID' => '',
                            'OrderID' => $order['OrderID'],
                            'item_uniqid' => 'etsy' . $trans->listing_id,
                            'ItemID' => $trans->listing_id,
                            'Title' => $trans->title,
                            'option' => '',
                            'BuyerEIASToken' => '',
                            'BuyerEmail' => $r->buyer_email,
                            'QuantityPurchased' => $trans->quantity,
                            'TransactionPrice' => $trans->price,
                            'EstimatedDeliveryTimeMin' => '',
                            'EstimatedDeliveryTimeMax' => '',
                            'listing_provider' => 'etsy'
                        ];

                        if (!empty($trans->variations)) { //variation purchased in the transaction
                            $transaction['item_uniqid'] = EtsyItemHelper::option_uniqid($transaction['item_uniqid'], $trans->variations);
                            $transaction['option'] = EtsyItemHelper::option_string($trans->variations);
                        }

                        $transactions[] = $transaction;
                    }
                } //list complete
                $output->writeln(count($orders) . " orders, " . count($transactions) . " transactions fetched,");

                // now save
                $saved_orders = 0;
                foreach ($orders as $data) {
                    $set = [];
                    foreach ($data as $key => $val) {
                        $set[] = "`$key`='{$this->db->escape($val)}'";
                    }
                    $entry_exist = $this->db->query("select * from sl_orders where OrderID='{$data['OrderID']}'")->num_rows;
                    if (!$entry_exist) {
                        $saved_orders++;
                        $this->db->query("INSERT INTO sl_orders SET " . implode(',', $set));
                    } else {
                        if ($this->db->query("select * from sl_orders where OrderID='{$data['OrderID']}' and shipped<>'{$data['shipped']}'")->num_rows) {
                            $this->db->query("update sl_orders set " . implode(',', $set) . " where OrderID='{$data['OrderID']}'");
                            $saved_orders++;
                        }
                    }
                }

                /**
                 * as etsy returns receipts in descending order(latest dated are first)
                 * we assume, if no new orders in this iteration, then probably no more newer in rest iterations
                 * so break the fetcher loop
                 *
                 * if (!$saved_orders) {
                 * print("$saved_orders orders to save, no more newer orders probably\n");
                 * break;
                 * }*/

                $saved_trans = 0;
                foreach ($transactions as $data) {
                    $set = [];
                    foreach ($data as $key => $val) {
                        $set[] = "`$key`='{$this->db->escape($val)}'";
                    }
                    if (!$this->db->query("select * from sl_listings where uniqid='{$data['item_uniqid']}'")->num_rows) {
                        $output->writeln("<error>item(uniqid) {$data['item_uniqid']} doesnt exist in listing table</error>");
                    }
                    $entry_exist = $this->db->query("select * from sl_transactions where TransactionID='{$data['TransactionID']}'")->num_rows;
                    if (!$entry_exist) {
                        $saved_trans++;
                        $this->db->query("INSERT INTO sl_transactions SET " . implode(',', $set));
                    } else {
                        $this->db->query("update sl_transactions set " . implode(',', $set) . " where TransactionID='{$data['TransactionID']}'");
                    }
                }
                $output->writeln(" $saved_orders new orders, $saved_trans new transactions");
            }
        }
    }

    function findAllCountry()
    {
        $countries = [];
        $client = new Client('https://openapi.etsy.com/v2/');
        $total = 100;
        for ($offset = 0; ($offset <= $total); $offset = $offset + 100) {
            $resp_json = $client->get("countries?limit=100&offset=$offset")->send()->getBody();
            $resp = json_decode($resp_json);
            $total = $resp->count;
            foreach ($resp->results as $entry) {
                $countries[$entry->country_id] = [
                    'code' => $entry->iso_country_code,
                    'name' => $entry->name
                ];
            }
        }
        return $countries;
    }
}