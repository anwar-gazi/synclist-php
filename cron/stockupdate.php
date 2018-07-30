<?php

namespace ResGef\SyncList\Cron\StockUpdate;

use resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel;
use resgef\synclist\system\datatypes\listingrow\ListingRow;
use resgef\synclist\system\datatypes\localinventoryitem\LocalInventoryItem;
use resgef\synclist\system\exceptions\apikeynotfoundexception\ApiKeyNotFoundException;
use resgef\synclist\system\exceptions\itemnotforsync\ItemNotForSync;
use resgef\synclist\system\exceptions\notproperlyloaded\NotProperlyLoaded;
use resgef\synclist\system\exceptions\stocksyncnotrequiredexception\StockSyncNotRequiredException;
use resgef\synclist\system\exceptions\unmetdependency\UnmetDependency;
use resgef\synclist\system\interfaces\croninterface\CronInterface;
use Resgef\SyncList\System\Library\EbayApi\EbayApiResponse\EbayApiResponse;
use Resgef\SyncList\System\Library\EbayApi\GetItem\GetItem;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class StockUpdate
 * @package ResGef\SyncList\Cron\EbayStockUpdate
 * @property \Modelebayapikeys $model_ebay_apikeys
 */
class StockUpdate extends \Controller implements CronInterface
{
    function execute()
    {
        $output = new ConsoleOutput();
        $output->writeln('<info>stock sync</info>');
        /** @var LocalInventoryItem[] $items */
        $items = $this->db->get_object("SELECT * FROM sl_local_inventory WHERE sync=1", LocalInventoryItem::class)->rows;
        foreach ($items as $inventoryItem) {
            $inventoryItem->dependency_injection($this->registry);
            try {
                $inventoryItem->sync_linked_items_to_remote(function (LocalInventoryItem $inventoryItem, ListingRow $listingRow) use ($output) {
                    $uniqid = $listingRow->uniqid;
                    $output->writeln("syncing stock for #$uniqid {$listingRow->title}");
                    $output->writeln("local qty {$listingRow->quantity} new proposed balance is {$inventoryItem->balance}, sold {$listingRow->sold}");

                    # now do a check of current remote quantity
                    $getitem = new GetItem($listingRow->api_keys($this->registry));
                    /** @var EbayApiResponse $response */
                    $response = $getitem->fetch_item($listingRow->itemid, $listingRow->sku);
                    $remote_quantity = (string)$response->xml->Item->Quantity;
                    $remote_quantity_sold = (string)$response->xml->Item->SellingStatus->QuantitySold;
                    $remote_quantity_available = $remote_quantity - $remote_quantity_sold;
                    $output->writeln("from remote: quantity: $remote_quantity, sold: $remote_quantity_sold, available: $remote_quantity_available");
                }, function (LocalInventoryItem $inventoryItem, ListingRow $listingRow, $affected_item_count, $message) use ($output) {
                    $uniqid = $listingRow->uniqid;
                    $output->writeln($message);
                    $output->writeln("success:" . ($affected_item_count ? '' : '(skipped)') . " #$uniqid old qty: {$listingRow->quantity} sold: {$listingRow->sold}, new updated balance:{$inventoryItem->balance}");
                }, function (LocalInventoryItem $inventoryItem, ListingRow $listingRow, $error) use ($output) {
                    $output->writeln("<error>Error:</error> $error");
                });
            } catch (ItemNotForSync $exception) {
                $output->writeln("<error>Skipping stock sync:</error> {$exception->getMessage()}");
            }
        }
    }
}