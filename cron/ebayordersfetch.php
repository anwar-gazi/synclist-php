<?php

namespace ResGef\SyncList\Cron\EbayOrdersFetch;

use Carbon\Carbon;
use resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel;
use resgef\synclist\system\exceptions\ebaynoordersfetchedexception\EbayNoOrdersFetchedException;
use resgef\synclist\system\exceptions\ebayresponseerrorexception\EbayResponseErrorException;
use resgef\synclist\system\helper\ebayorder\EbayOrder;
use Resgef\Synclist\System\Helper\ServerTime\ServerTime;
use resgef\synclist\system\interfaces\croninterface\CronInterface;
use Resgef\SyncList\System\Library\EbayApi\EbayApiResponse\EbayApiResponse;
use resgef\synclist\system\library\ebayapi\getorders\GetOrders;
use resgef\synclist\system\library\ebayapi\getstore\GetStore;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class EbayOrdersFetch
 * @package ResGef\SyncList\Cron\EbayOrdersFetch
 * @property \Modelebayapikeys $model_ebay_apikeys
 */
class EbayOrdersFetch extends \Controller implements CronInterface
{
    /**
     * @return bool
     * @throws EbayResponseErrorException
     * @throws EbayNoOrdersFetchedException
     */
    function execute()
    {
        $output = new ConsoleOutput();
        $output->writeln("<info>Ebay Orders Fetch</info>");
        $this->load->model('ebay/apikeys');

        $all_api_keys = $this->model_ebay_apikeys->all();
        $output->writeln(count($all_api_keys) . " ebay account to fetch");
        /** @var EbayApiKeysModel $api_keys */
        foreach ($all_api_keys as $api_keys) {
            $output->writeln("#{$api_keys->account_name}");

            /** @var string $time_from */
            $time_from = @$this->db->query("select * from sl_orders_last_fetch_time where remote_provider='ebay' and account_name='{$api_keys->account_name}'")->row['fetchtime'];
            if ($time_from) { # convert from mysql's formate to the one we like
                $time_from = Carbon::createFromFormat('Y-m-d H:i:s', $time_from)->toIso8601String();
            }
            $now = ServerTime::now()->toISO8601String();
            $intervals = [];
            if (!$time_from) {
                $output->writeln("<info>asserting time_from from store LastOpenedTime</info>");
                $getstore = new GetStore($api_keys);
                $time_from = $getstore->LastOpenedTime();
                $output->writeln("taking LastOpenedTime as time_from");
                $intervals = ServerTime::build_ebay_intervals($time_from, $now, 120);
            } elseif (ServerTime::createFromISO8601String($now)->diffInDays(ServerTime::createFromISO8601String($time_from)) <= 120) {
                $intervals[] = [
                    'from' => $time_from,
                    'to' => $now
                ];
            } else {
                $intervals = ServerTime::build_ebay_intervals($time_from, $now, 120);
            }

            $getorders = new GetOrders($api_keys, 'GetOrders');

            $output->writeln("fetching orders from $time_from to $now:" . ServerTime::diffForHumansFromFormat($now, $time_from) . " in " . count($intervals) . " interval" . (count($intervals) > 1 ? 's' : ''));

            $orders_count = 0;
            foreach ($intervals as $int_ind => $interval) {
                $from = $interval['from'];
                $to = $interval['to'];
                $total_page = 1;
                $output->writeln('interval ' . ($int_ind + 1) . '/' . count($intervals) . "# $from to $to:" . ServerTime::diffForHumansFromFormat($to, $from));
                for ($PageNumber = 1; $PageNumber <= $total_page; $PageNumber++) {
                    $output->write("page:$PageNumber/$total_page ");

                    $time_from_ebay = ServerTime::createFromISO8601String($from)->toEbayString();
                    $now_ebay = ServerTime::createFromISO8601String($to)->toEbayString();

                    $requestxml = "" .
                        "<?xml version='1.0' encoding='utf-8'?>"
                        . "<GetOrdersRequest xmlns='urn:ebay:apis:eBLBaseComponents'>"
                        . "<CreateTimeFrom>"
                        . $time_from_ebay
                        . "</CreateTimeFrom>"
                        . "<CreateTimeTo>"
                        . $now_ebay
                        . "</CreateTimeTo>"
                        . "<IncludeFinalValueFee>1</IncludeFinalValueFee>"
                        . "<OrderRole>Seller</OrderRole>"
                        . "<OrderStatus>Completed</OrderStatus>"
                        . "<RequesterCredentials>"
                        . "<eBayAuthToken>"
                        . $api_keys->requestToken
                        . "</eBayAuthToken>"
                        . "</RequesterCredentials>"
                        . "<Pagination>"
                        . "<EntriesPerPage>200</EntriesPerPage>"
                        . "<PageNumber>"
                        . $PageNumber
                        . "</PageNumber>"
                        . "</Pagination>"
                        . "<DetailLevel>ReturnAll</DetailLevel>"
                        . "</GetOrdersRequest>";

                    /** @var EbayApiResponse $response */
                    $response = $getorders->execute($requestxml);

                    if ($response->error) {
                        $output->writeln("<error>{$response->error}</error>");
                        throw new EbayResponseErrorException($response->error);
                    }

                    $total_page = (string)$response->xml->PaginationResult->TotalNumberOfPages;

                    /** now parse and save */
                    $num_trans_fetched = call_user_func(function () use ($response) {
                        $c = 0;
                        foreach ($response->xml->OrderArray->Order as $o) {
                            $c += count($o->TransactionArray->Transaction);
                        }
                        return $c;
                    });

                    $output->writeln(count($response->xml->OrderArray->Order) . " orders, $num_trans_fetched transactions fetched");
                    $orders_count += count($response->xml->OrderArray->Order);
                    $shipped_count = 0;
                    foreach ($response->xml->OrderArray->Order as $node) { //each order
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
                            'account_name' => $api_keys->account_name
                        ];

                        $order_info['local_status'] = $this->synclist_api->Orders->determine_local_status(!EbayOrder::is_cancelled($order_info['OrderStatus']), EbayOrder::is_waiting_on_customer_response($order_info['OrderStatus']), $order_info['shipped'] == 1);

                        if ($order_info['shipped']) {
                            $shipped_count++;
                        }

                        /* save the order */
                        $this->synclist_api->Orders->save_order($order_info);

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
                            $this->synclist_api->Orders->save_transaction($transaction);
                        }
                    }

                    $output->writeln("$shipped_count shipped");
                }
            }

            $cron_report = "$orders_count orders in past " . ServerTime::diffForHumansFromFormat($now, $time_from);

            $output->writeln("$cron_report");

            if (!$orders_count) {
                $output->writeln("<error>no orders fetched</error>");
            }

            # now save the fetch time
            $statement = $this->pdo->prepare("INSERT INTO sl_orders_last_fetch_time(fetchtime,account_name,remote_provider) VALUES(?,?,?) ON DUPLICATE KEY UPDATE fetchtime=VALUES(fetchtime)");
            $remote_provider = 'ebay';
            $statement->bindParam(1, $now);
            $statement->bindParam(2, $api_keys->account_name);
            $statement->bindParam(3, $remote_provider);
            $statement->execute();
        }

        return true;
    }

    private function parse_ItemID(\SimpleXMLElement $Transaction_node)
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