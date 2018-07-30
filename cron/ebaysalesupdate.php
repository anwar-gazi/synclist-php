<?php

namespace Resgef\SyncList\Cron\EbaySalesUpdate;

use Carbon\Carbon;
use resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel;
use resgef\synclist\system\datatypes\ebaylistingrow\EbayListingRow;
use resgef\synclist\system\exceptions\cannotbuildtimeintervalsexception\CannotBuildTimeIntervalsException;
use resgef\synclist\system\exceptions\ebaynoactiveitems\EbayNoActiveItemsException;
use resgef\synclist\system\exceptions\ebayresponseerrorexception\EbayResponseErrorException;
use Resgef\Synclist\System\Helper\ServerTime\ServerTime;
use resgef\synclist\system\interfaces\croninterface\CronInterface;
use Resgef\SyncList\System\Library\EbayApi\EbayApiResponse\EbayApiResponse;
use Resgef\SyncList\System\Library\EbayApi\GetMyEbaySelling\GetMyEbaySelling;
use Resgef\SyncList\System\Library\EbayApi\GetSellerTransactions\GetSellerTransactions;
use resgef\synclist\system\library\ebayapi\getstore\GetStore;
use \Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class EbaySalesUpdate
 * @package Resgef\SyncList\Cron\EbaySalesUpdate
 * @property \Modelebayapikeys $model_ebay_apikeys
 * @property \Modellistinglisting $model_listing_listing
 * @property \Modellogcronstate $model_log_cronstate
 */
class EbaySalesUpdate extends \Controller implements CronInterface
{
    /** @var ConsoleOutput */
    private $output;

    /**
     * @throws CannotBuildTimeIntervalsException
     * @throws EbayNoActiveItemsException
     * @throws EbayResponseErrorException
     */
    function execute()
    {
        $this->output = new ConsoleOutput();

        $this->output->writeln("<info>Ebay Sales Update</info>");

        $this->load->model('ebay/apikeys');
        $this->load->model('listing/listing');
        $this->load->model('log/cronstate');

        /** @var EbayApiKeysModel[] $all_ebay_api_keys */
        $all_ebay_api_keys = $this->model_ebay_apikeys->all();

        $this->output->writeln(count($all_ebay_api_keys) . " remote stores");

        $now = Carbon::now();

        foreach ($all_ebay_api_keys as $api_keys) {
            $this->for_a_ebay_seller($api_keys);
        }

        $this->output->writeln("time consumed: " . ServerTime::diffHumanReadable($now, Carbon::now()));
    }

    /**
     * @param EbayApiKeysModel $api_keys
     * @return bool
     * @throws CannotBuildTimeIntervalsException
     * @throws EbayNoActiveItemsException
     * @throws EbayResponseErrorException
     */
    private function for_a_ebay_seller(EbayApiKeysModel $api_keys)
    {
        $this->output->writeln("#{$api_keys->account_name}");

        $getmyebayselling = new GetMyEbaySelling($api_keys);
        $getsellertransactions = new GetSellerTransactions($api_keys);

        /** @var EbayListingRow[] $existing_listing_items_active */
        $existing_listing_items_active = $this->db->get_object("SELECT * FROM sl_listings WHERE active=1 AND listing_provider='ebay' and account_name='{$api_keys->account_name}'", EbayListingRow::class)->rows;

        #determine last fetch time of active items
        $time_from = call_user_func(function () use ($existing_listing_items_active) {
            $save_times = \array_map(function (EbayListingRow $item) {
                return $item->salesdata_save_time;
            }, $existing_listing_items_active);
            \sort($save_times);
            $time_from = \reset($save_times);
            return $time_from;
        });

        if (!count($existing_listing_items_active)) {
            $this->output->writeln("<info>new store linked: {$api_keys->account_name}</info>");
        }

        $now = Carbon::now('UTC')->toIso8601String();

        $total_page = 1;
        /** @var EbayListingRow[] $fetched_listing_items */
        $fetched_listing_items = [];
        /**
         * Now fetch the active listings
         */
        for ($PageNumber = 1; $PageNumber <= $total_page; $PageNumber++) {

            $this->output->writeln("making request GetMyEbaySelling");

            $requestxml = '<?xml version="1.0" encoding="utf-8"?>'
                . '<GetMyeBaySellingRequest xmlns="urn:ebay:apis:eBLBaseComponents">' .
                '<RequesterCredentials><eBayAuthToken>'
                . $api_keys->requestToken
                . '</eBayAuthToken></RequesterCredentials>' .
                '<ActiveList>' .
                '<Include>true</Include>' .
                '<IncludeNotes>false</IncludeNotes>' .
                '<Pagination>' .
                '<EntriesPerPage>200</EntriesPerPage>' .
                '<PageNumber>'
                . $PageNumber
                . '</PageNumber>' .
                '</Pagination>' .
                '</ActiveList>' .
                '<UnsoldList><Include>false</Include></UnsoldList>' .
                '<BidList><Include>false</Include></BidList>' .
                '<DeletedFromSoldList><Include>false</Include></DeletedFromSoldList>' .
                '<DeletedFromUnSoldList><Include>false</Include></DeletedFromUnSoldList>' .
                '<ScheduledList><Include>false</Include></ScheduledList>' .
                '<SoldList><Include>false</Include></SoldList>' .
                '<HideVariations>false</HideVariations>' .
                '<DetailLevel>ReturnAll</DetailLevel>' .
                '<OutputSelector>ItemID</OutputSelector>' .
                '<OutputSelector>CurrentPrice</OutputSelector>' .
                '<OutputSelector>ListingStatus</OutputSelector>' .
                '<OutputSelector>Title</OutputSelector>' .
                '<OutputSelector>Quantity</OutputSelector>' .
                '<OutputSelector>GalleryURL</OutputSelector>' .
                '<OutputSelector>QuantityAvailable</OutputSelector>' .
                '<OutputSelector>SellingStatus</OutputSelector>' .
                '<OutputSelector>QuantitySold</OutputSelector>' .
                '<OutputSelector>SKU</OutputSelector>' .
                '<OutputSelector>ViewItemURL</OutputSelector>' .
                '<OutputSelector>UserID</OutputSelector>' .
                '<OutputSelector>TotalNumberOfPages</OutputSelector>' .
                '<OutputSelector>TotalNumberOfEntries</OutputSelector>' .
                '<OutputSelector>Variations</OutputSelector>' .
                '<OutputSelector>Variation</OutputSelector>' .
                '</GetMyeBaySellingRequest>';

            /** @var \Resgef\SyncList\System\Library\EbayApi\EbayApiResponse\EbayApiResponse $response */
            $response = $getmyebayselling->execute($requestxml);

            if ($response->error) {
                /**
                 * If there is an error, then there is no point in going forward
                 * throw exception to end this cron here
                 * If you proceed then salesdata save time will be saved which should not be
                 */
                $this->output->writeln("<error>{$response->error}</error>");
                throw new EbayResponseErrorException($response->error);
            }

            $total_page = $response->xml->ActiveList->PaginationResult->TotalNumberOfPages;

            /** now parse and save */
            foreach ($response->xml->ActiveList->ItemArray->Item as $node) {
                $listing = new EbayListingRow();
                $listing->itemid = (string)$node->ItemID;
                $listing->option = '';
                $listing->sku = (string)$node->SKU;
                $listing->uniqid = preg_replace('#\s+#', '', 'ebay' . $listing->itemid . (!empty($node->SKU) ? '-' . $node->SKU : ''));
                $listing->has_variations = 0;
                $listing->title = (string)$node->Title;
                $listing->pic_url = (string)$node->PictureDetails->GalleryURL;
                $listing->item_url = (string)$node->ListingDetails->ViewItemURL;
                $quantity = (string)$node->Quantity;
                $available = (string)$node->QuantityAvailable;
                $sold = $quantity - $available;
                $listing->quantity = $quantity;
                $listing->sold = $sold;
                $listing->salesdata_save_time = $now;
                $listing->active = 1;
                $listing->seller = $api_keys->account_name;
                $listing->account_name = $api_keys->account_name;

                if ($node->Variations) { //get variations
                    foreach ($node->Variations->Variation as $var_node) { //each variation
                        $var_item = clone $listing;
                        $var_item->title = !empty($var_node->VariationTitle) ? (string)$var_node->VariationTitle : $var_item->title;
                        $var_item->option = call_user_func(function () use (&$var_node) {
                            $option2 = '';
                            foreach ($var_node->VariationSpecifics->NameValueList as $prop) {
                                $option2 .= strtolower($prop->Name . ':' . $prop->Value . ',');
                            }
                            $option = rtrim($option2, ',');
                            return $option;
                        });
                        $var_item->sku = (string)$var_node->SKU;
                        $var_item->uniqid = preg_replace('#\s+#', '', 'ebay' . $var_item->itemid . '-' . $var_node->SKU);
                        $var_item->quantity = (string)$var_node->Quantity;
                        $var_item->sold = (string)$var_node->SellingStatus->QuantitySold;
                        $fetched_listing_items[] = $var_item;
                    }
                } else {
                    $fetched_listing_items[] = $listing;
                }
            }
        }

        /** no active items found, end execution */
        if (empty($fetched_listing_items)) {
            /**
             * If fetched listings are empty then there is no point moving forward
             */
            $fail_report = "listing empty, abort";
            $this->output->writeln("<error>$fail_report</error>");
            throw new EbayNoActiveItemsException($fail_report);
        }

        /**
         * now show some good deal of info
         */
        /** @var EbayListingRow[] $fetched_listing_items_indexed */
        $fetched_listing_items_indexed = [];
        foreach ($fetched_listing_items as $item) {
            $fetched_listing_items_indexed[$item->uniqid] = $item;
        }

        /** @var EbayListingRow[] $existing_listing_items_active_indexed */
        $existing_listing_items_active_indexed = [];
        foreach ($existing_listing_items_active as $item) {
            $existing_listing_items_active_indexed[$item->uniqid] = $item;
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
        /** @var EbayListingRow[] $stale_items */
        $stale_items = [];
        foreach ($existing_listing_items_active_indexed as $e_uniqid => $item) {
            if (!array_key_exists($e_uniqid, $fetched_listing_items_indexed)) { // stale
                $stale_items[] = $item;
            }
        }

        $fetch_report = sprintf(
            "%s active items just fetched: %s to update, %s new entries."
            . "\n%s previously active items in database."
            . "\n%s stale items in database, they will be hidden(not active)"
            , count($fetched_listing_items), count($changelog['update']), count($changelog['insert']), count($existing_listing_items_active), count($stale_items)
        );
        $this->output->writeln($fetch_report);

        /** now fetch transactions */

        /**
         * now build the time intervals
         * If couldnt get a $time_from which means probably no listing entry in database, which means probably the store is just linked(api key just added)
         * then make the $time_from as the store LastOpenedTime
         */
        $intervals = [];

        if (!$time_from) {
            $getstore = new GetStore($api_keys);
            $time_from = $getstore->LastOpenedTime();
            $intervals = ServerTime::build_ebay_intervals($time_from, $now, 30);
        } elseif (ServerTime::diffInDaysFromFormat($time_from, $now) <= 30) { //in 30 days
            $intervals[] = [
                'from' => $time_from,
                'to' => $now
            ];
        } else { //exceeds 30 days
            $intervals = ServerTime::build_ebay_intervals($time_from, $now, 30);
        }

        if (!count($intervals)) {
            $this->output->writeln("<error>cannot build time intervals with time_from:$time_from, now:$now</error>");
            throw new CannotBuildTimeIntervalsException("cannot build time intervals with time_from:$time_from, now:$now");
        }

        $this->output->writeln("fetching transactions from:$time_from to:$now:" . ServerTime::diffForHumansFromFormat($time_from, $now) . ", in " . count($intervals) . " time interval");
        $total_trans = 0;
        foreach ($intervals as $intervl) { //each time intervals
            $from = $intervl['from'];
            $to = $intervl['to'];
            $this->output->writeln("from $from to $to:" . ServerTime::diffForHumansFromFormat($from, $to));
            $trans_total_page = 1;
            for ($PageNumber = 1; $PageNumber <= $trans_total_page; $PageNumber++) {
                $this->output->writeln("page $PageNumber/$trans_total_page");
                $time_from_ebay = ServerTime::createFromISO8601String($from)->toEbayString();
                $time_to_ebay = ServerTime::createFromISO8601String($to)->toEbayString();

                $requestxml = '<?xml version="1.0" encoding="utf-8" ?>'
                    . '<GetSellerTransactions xmlns="urn:ebay:apis:eBLBaseComponents">'
                    . '<RequesterCredentials><eBayAuthToken>' .
                    $api_keys->requestToken .
                    '</eBayAuthToken></RequesterCredentials>'
                    . '<Pagination><EntriesPerPage>200</EntriesPerPage>'
                    . '<PageNumber>'
                    . $PageNumber
                    . '</PageNumber>'
                    . '</Pagination>'
                    . "<ModTimeFrom>{$time_from_ebay}</ModTimeFrom>"
                    . "<ModTimeTo>{$time_to_ebay}</ModTimeTo>"
                    . '</GetSellerTransactions>';

                /** @var EbayApiResponse $response */
                $response = $getsellertransactions->execute($requestxml);
                if ($response->error) {
                    $this->output->writeln("<error>{$response->error}</error>");
                    throw new EbayResponseErrorException($response->error);
                }
                $trans_total_page = $response->xml->PaginationResult->TotalNumberOfPages;

                $this->output->writeln(count($response->xml->TransactionArray->Transaction) . " transactions");
                $total_trans += count($response->xml->TransactionArray->Transaction);
            }
        }

        $cron_report = "ebay:{$api_keys->account_name}# $total_trans new transactions in " . ServerTime::diffForHumansFromFormat($time_from, $now);

        $this->output->writeln("$cron_report");

//        return;

        # in database transactions
        $this->pdo->beginTransaction();

        # save
        foreach ($fetched_listing_items as $ebayListingRow) {
            $statement = $this->pdo->prepare("INSERT INTO sl_listings(uniqid, salesdata_save_time, itemid, `option`, sku, seller, title, pic_url, item_url, quantity, sold, listing_provider, active, account_name) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE salesdata_save_time=VALUES(salesdata_save_time),itemid=VALUES(itemid),`option`=VALUES(`option`),sku=VALUES(sku),seller=VALUES(seller),title=VALUES(title),pic_url=VALUES(pic_url),item_url=VALUES(item_url),quantity=VALUES(quantity),sold=VALUES(sold),listing_provider=VALUES(listing_provider),active=VALUES(active),account_name=VALUES(account_name)");
            $statement->bindParam(1, $ebayListingRow->uniqid);
            $statement->bindParam(2, $ebayListingRow->salesdata_save_time);
            $statement->bindParam(3, $ebayListingRow->itemid);
            $statement->bindParam(4, $ebayListingRow->option);
            $statement->bindParam(5, $ebayListingRow->sku);
            $statement->bindParam(6, $ebayListingRow->seller);
            $statement->bindParam(7, $ebayListingRow->title);
            $statement->bindParam(8, $ebayListingRow->pic_url);
            $statement->bindParam(9, $ebayListingRow->item_url);
            $statement->bindParam(10, $ebayListingRow->quantity);
            $statement->bindParam(11, $ebayListingRow->sold);
            $statement->bindParam(12, $ebayListingRow->listing_provider);
            $statement->bindParam(13, $ebayListingRow->active, \PDO::PARAM_INT);
            $statement->bindParam(14, $ebayListingRow->account_name);
            $statement->execute();
        }

        /** empty the status of the stale items */
        foreach ($stale_items as $item) {
            $statement = $this->pdo->prepare("UPDATE sl_listings SET active=0 WHERE uniqid=?");
            $statement->execute([$item->uniqid]);
        }

        # cronstate log
        $statement = $this->pdo->prepare("INSERT INTO sl_synclist_logs(log, time, type) VALUES (?,?,?)");
        $statement->execute([$cron_report, Carbon::now()->toIso8601String(), 'cronstate']);

        $this->pdo->commit();

        return true;
    }
}