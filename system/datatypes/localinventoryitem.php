<?php

namespace resgef\synclist\system\datatypes\localinventoryitem;

use resgef\synclist\system\datatypes\listingrow\ListingRow;
use resgef\synclist\system\exceptions\apikeynotfoundexception\ApiKeyNotFoundException;
use resgef\synclist\system\exceptions\itemnotforsync\ItemNotForSync;
use resgef\synclist\system\exceptions\notproperlyloaded\NotProperlyLoaded;
use resgef\synclist\system\exceptions\stocksyncnotrequiredexception\StockSyncNotRequiredException;
use resgef\synclist\system\exceptions\unmetdependency\UnmetDependency;
use resgef\synclist\system\library\uniremoteapi\remoteresponse\RemoteResponse;

/**
 * Class LocalInventoryItem
 * @package resgef\synclist\system\datatypes\localinventoryitem
 * @property int sold
 * @property int balance inventory quantity available
 * @property string id
 * @property bool soldout
 * @property bool synced
 */
class LocalInventoryItem extends \resgef\synclist\system\datatypes\model\Model
{
    public $inventory_itemid;
    public $title;
    public $quantity;
    public $sync;

    public $avg_sold_per_day;

    public $etd_days_in_stock;

    /** @var \Registry $registry */
    private $registry;

    function dependency_injection(\Registry $registry)
    {
        $this->registry = $registry;
    }

    # reload with the current inventory id
    //TODO reload _sold too
    function reload()
    {
        /** @var self $row */
        $row = $this->registry->db->get_object("select * from sl_local_inventory where inventory_itemid='{$this->inventory_itemid}'", self::class)->row;
        $this->title = $row->title;
        $this->quantity = $row->quantity;
        $this->sync = $row->sync;
    }

    function gen_id()
    {
        return substr(md5($this->title), 0, 16);
    }

    function save(\Registry $registry)
    {
        $statement = $registry->pdo->prepare("INSERT INTO sl_local_inventory(inventory_itemid, title, quantity, sync) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE title=values(title),quantity=values(quantity), sync=values(sync)");
        $statement->bindParam(1, $this->inventory_itemid, \PDO::PARAM_STR);
        $statement->bindParam(2, $this->title, \PDO::PARAM_STR);
        $statement->bindParam(3, $this->quantity, \PDO::PARAM_STR);
        $statement->bindParam(4, $this->sync, \PDO::PARAM_BOOL);
        $statement->execute();
    }

    # unlink all items
    function unlink_items(\Registry $registry)
    {
        $statement = $registry->pdo->prepare("DELETE FROM sl_local_inventory_linked_item WHERE inventory_itemid=?");
        $statement->bindParam(1, $this->inventory_itemid);
        $statement->execute();
    }

    function link_item($listing_uniqid, $multiply, \Registry $registry)
    {
        $statement = $registry->pdo->prepare("INSERT INTO sl_local_inventory_linked_item(inventory_itemid, item_uniqid, multiply) VALUES(?,?,?)");
        $statement->bindParam(1, $this->inventory_itemid);
        $statement->bindParam(2, $listing_uniqid);
        $statement->bindParam(3, $multiply);
        $statement->execute();
    }

    /**
     * @return ListingRow[]
     */
    function linked_items()
    {
        return $this->registry->db->get_object("select * from sl_local_inventory_linked_item ln left JOIN sl_listings ls on ln.item_uniqid=ls.uniqid where ln.inventory_itemid='{$this->inventory_itemid}'", ListingRow::class)->rows;
    }

    /**
     * @param callable $before_sync
     * @param callable $listing_sync_success
     * @param callable $listing_sync_fail note: this function will be called also if the proposed quantity value is identical to remote value
     * @throws ItemNotForSync
     * @throws NotProperlyLoaded
     * @throws UnmetDependency
     * @throws ApiKeyNotFoundException
     */
    function sync_linked_items_to_remote(callable $before_sync, callable $listing_sync_success, callable $listing_sync_fail)
    {
        if (!isset($this->quantity)) {
            throw new NotProperlyLoaded("inventory item object not properly loaded. If you created new instance with the constructor, then you must perform the ->reload() method after setting inventory id");
        }

        if (!$this->sync) {
            throw new ItemNotForSync("Sync Failed: local inventory item#{$this->inventory_itemid} sync setting is false");
        }

        if (!$this->registry) {
            throw new UnmetDependency('unmet dependency. inject Regsitry object first');
        }

        $this->registry->load->model('log/log');
        $linked_items = $this->linked_items();

        /** @var ListingRow $listingRow */
        foreach ($linked_items as $listingRow) {
            $old_quantity = $listingRow->quantity;

            $before_sync($this, $listingRow);

            $listingRow->dependency_injection($this->registry);

            try {
                /** @var RemoteResponse $remote_response */
                $remote_response = $listingRow->update_quantity_to_remote($this);
            } catch (StockSyncNotRequiredException $exception) {
                $listing_sync_success($this, $listingRow, 0, "listing stock sync not required: {$exception->getMessage()}");
                $listingRow->synced = true;
                $listingRow->save();
                continue;
            }

            if ($remote_response->success) {
                $listing_sync_success($this, $listingRow, 1, null);
                $listingRow->synced = true;
                $listingRow->save($this->registry);
            } else {
                $listing_sync_fail($this, $listingRow, $remote_response->error_msg);
                $listingRow->synced = false;
                $listingRow->quantity = $old_quantity;
                $listingRow->save($this->registry);
            }
        }
    }

    private function _calculate_sold()
    {
        $sold = 0;
        /** @var ListingRow $listingRow */
        foreach ($this->linked_items() as $listingRow) {
            $listingRow->dependency_injection($this->registry);
            $sold += $listingRow->sold * ($listingRow->multiply ? $listingRow->multiply : 1);
        }
        return $sold;
    }

    # these functions are for templating
    function _sold()
    {
        return $this->_calculate_sold();
    }

    function _balance()
    {
        return $this->quantity - $this->sold;
    }

    function _soldout()
    {
        return ($this->sold ? false : true);
    }

    function _synced()
    {
        if ($this->sync == 1) {
            foreach ($this->linked_items() as $listingRow) {
                if ($listingRow->synced != 1) {
                    return false;
                }
            }
            return true;
        } else {
            return true;
        }
    }

    function __get($name)
    {
        switch ($name) {
            case 'sold':
                return $this->_calculate_sold();
                break;
            case 'balance':
                return $this->_balance();
                break;
//            case 'id':
//                return $this->inventory_itemid;
//                break;
            case 'soldout':
                return $this->_soldout();
                break;
            case 'synced':
                return $this->_synced();
                break;
        }
    }
}