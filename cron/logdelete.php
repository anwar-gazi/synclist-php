<?php

namespace ResGef\SyncList\Cron\logdelete;

use Carbon\Carbon;
use resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel;
use resgef\synclist\system\datatypes\listingrow\ListingRow;
use resgef\synclist\system\datatypes\localinventoryitem\LocalInventoryItem;
use resgef\synclist\system\interfaces\croninterface\CronInterface;
use Resgef\SyncList\System\Library\EbayApi\GetItem\GetItem;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class StockUpdate
 * @package ResGef\SyncList\Cron\EbayStockUpdate
 * @property \Modelebayapikeys $model_ebay_apikeys
 */
class LogDelete extends \Controller implements CronInterface
{
    function execute()
    {
        $output = new ConsoleOutput();
        $output->writeln('<info>log delete</info>');
        $onemonthago = Carbon::now()->subMonth()->format('Y-m-d');
        $sevendaysago = \Carbon\Carbon::now()->subDays(7)->format('Y-m-d');

        $this->db->query("delete from sl_sales_log where DATE(savetime)<'$sevendaysago'");

        $this->db->query("delete from sl_synclist_logs where DATE(sl_synclist_logs.time)<'$onemonthago'");

        $this->db->query("delete from sl_sellingstats where DATE(create_date)<'$sevendaysago'");
    }
}