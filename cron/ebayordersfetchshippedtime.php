<?php

namespace ResGef\SyncList\Cron\EbayOrdersFetchShippedTime;

use resgef\synclist\system\exceptions\ebayresponseerrorexception\EbayResponseErrorException;
use Resgef\Synclist\System\Helper\ServerTime\ServerTime;
use resgef\synclist\system\interfaces\croninterface\CronInterface;
use Resgef\SyncList\System\Library\EbayApi\EbayApiResponse\EbayApiResponse;
use resgef\synclist\system\library\ebayapi\getorders\GetOrders;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class EbayOrdersFetchShippedTime
 * @package ResGef\SyncList\Cron\EbayOrdersFetchShippedTime
 * @property \Modelebayapikeys $model_ebay_apikeys
 */
class EbayOrdersFetchShippedTime extends \Controller implements CronInterface
{
    /**
     * @return bool
     * @throws EbayResponseErrorException
     */
    function execute()
    {
        $output = new ConsoleOutput();
        $output->writeln("<info>Ebay get orders shipped time</info>");

        $this->load->model('ebay/apikeys');

        $all_ebay_api_keys = $this->model_ebay_apikeys->all();

        foreach ($all_ebay_api_keys as $api_keys) {
            $getorders = new GetOrders($api_keys, 'GetOrders');

            $not_shipped_OrderID = array_map(function (Array $order) {
                return $order['OrderID'];
            }, $this->db->query("SELECT * FROM sl_orders WHERE `listing_provider`='ebay' AND account_name='{$api_keys->account_name}' AND `ShippedTime`=''")->rows);

            $output->writeln("need to get shipped time for " . count($not_shipped_OrderID) . " ebay orders");
            if (empty($not_shipped_OrderID)) {
                return false;
            }
            /* now fetch pages, parse, save */
            $saved = 0;
            $total_page = 1;
            for ($PageNumber = 1; $PageNumber <= $total_page; $PageNumber++) {
                $output->writeln("page:$PageNumber/$total_page");
                $requestxml = "<?xml version='1.0' encoding='utf-8'?>"
                    . "<GetOrdersRequest xmlns='urn:ebay:apis:eBLBaseComponents'>"
                    . "<OrderIDArray>"
                    . call_user_func(function () use ($not_shipped_OrderID) {
                        $OrderID_xml = '';
                        foreach ($not_shipped_OrderID as $OrderID) {
                            $OrderID_xml .= "<OrderID>$OrderID</OrderID>";
                        }
                        return $OrderID_xml;
                    })
                    . "</OrderIDArray>"
                    . "<OrderRole>Seller</OrderRole>"
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

                $total_page = $response->xml->PaginationResult->TotalNumberOfPages;

                foreach ($response->xml->OrderArray->Order as $Ordernode) {
                    $ShippedTime = (string)$Ordernode->ShippedTime;
                    $shipped = $ShippedTime ? 1 : 0;
                    $OrderID = (string)$Ordernode->OrderID;
                    if ($ShippedTime) {
                        $ShippedTime = ServerTime::createFromEbayTime($ShippedTime)->toISO8601String();
                        $local_status = $this->synclist_api->Orders->determine_local_status(true, false, true);
                        $this->db->query("update sl_orders set ShippedTime='$ShippedTime',shipped='{$shipped}',local_status='$local_status' where OrderID='$OrderID'");
                        $saved++;
                    }
                }
            }

            $output->writeln("$saved orders shipped time updated");
        }

        return true;
    }
}