<?php

class SyncListApiLocalInventory
{

    /** @var DBMySQLi Description */
    public $db;

    /** @var SyncListApi the local inventory module */
    private $api;

    function __construct(SyncListApi $api)
    {
        $this->db = $api->db;
        $this->api = $api;
    }

    /**
     * generate unique id for a local inventory item
     * @param type $title
     * @return type
     */
    function id($title)
    {
        return substr(md5($title), 0, 16);
    }

    function title($inventory_itemid)
    {
        $table = $this->api->table_name('local_inventory');
        $item = $this->db->query("select title from $table where inventory_itemid='$inventory_itemid'")->row;
        if (empty($item)) {
            return '';
        } else {
            return $item['title'];
        }
    }

    function listing_item_title($listing_item_uniqid)
    {
        $table = $this->api->table_name('listings');
        return $this->db->query("select title from $table where uniqid='$listing_item_uniqid'")->row['title'];
    }

    /*     * DEPRECATED
     * get all local inventory items with linked items info
     * @return Array of inventory item info array database entry
     * with extra key 'linked_items' which is array of linked items info array database entry
     * [
     * 'inventory_itemid'=>'',
     * 'title'=>'',
     * 'quantity'=>'',
     * 'linked_items'=>[ ['item_uniqid'=>'', 'multiply'=>''], ... ]
     * ]
     */

    public function inventory_items()
    {
        $inv_table = $this->api->table_name('local_inventory');
        $items = array_map(function (Array $row) { // each inventory item entry
            $row['linked_items'] = $this->linked_items($row['inventory_itemid']);
            return $row;
        }, $this->db->query("select * from $inv_table")->rows);
        return $items;
    }

    /**
     * inventory items id as plain array
     * @return array
     */
    public function items_id()
    {
        $ids = array_map(function ($row) {
            return $row['inventory_itemid'];
        }, $this->db->query("SELECT inventory_itemid FROM sl_local_inventory")->rows);
        return $ids;
    }

    /** DEPRECATED
     * use this method, you can easily see here and know what key=>value we are providing
     *
     */
    public function items($itemids = [])
    {
        $inv_sold = function (Array $linked_items) { //considering linked items
            $sold = 0;
            foreach ($linked_items as $l) {
                $sold += $l['sold'] * ($l['multiply'] ? $l['multiply'] : 1);
            }
            return $sold;
        };
        $linked_items = function ($invid) { //fetch linked items
            $linked_items = [];
            foreach ($this->db->query("select ln.*,l.* from sl_local_inventory_linked_item ln left JOIN sl_listings l on ln.item_uniqid=l.uniqid where ln.inventory_itemid='$invid'")->rows as $l) {
                $l['id'] = $l['uniqid'];
                $linked_items[] = $l;
            }

            return $linked_items;
        };

        $invitems = [];
        foreach ($this->db->query("SELECT * FROM sl_local_inventory")->rows as $invrow) {
            if (!empty($itemids) && !in_array($invrow['inventory_itemid'], $itemids)) {
                continue;
            }
            $invitem = $invrow;
            $invitem['id'] = $invrow['inventory_itemid'];
            $invitem['linked_items'] = $linked_items($invrow['inventory_itemid']);

            $invitem['sold'] = $inv_sold($invitem['linked_items']);
            $invitem['balance'] = $invitem['quantity'] - $invitem['sold'];
            $invitem['soldout'] = $invitem['sold'] ? false : true;
            $invitem['linked_items_sync_fail'] = [];
            $invitem['linked_items_synced'] = call_user_func(function () use (&$invitem) {
                foreach ($invitem['linked_items'] as $linked_item) {
                    if ($linked_item['synced'] != 1) {
                        $invitem['linked_items_sync_fail'][] = "#{$linked_item['uniqid']} {$linked_item['title']}";
                        return false;
                    }
                }
                return true;
            });
            $invitems[$invrow['inventory_itemid']] = $invitem;
        }
        return $invitems;
    }

    public function item($inv_itemid)
    {
        $items = $this->items([$inv_itemid]);
        return reset($items);
    }

    function log_inventory_calc_operation($inventory_item_title, Array $history = [])
    {
        if (empty($history)) {
            return false;
        }
        $this->api->load_api('Logger');
        foreach ($history as $entry) {
            $cause = implode(',', array_keys($entry));
            $quantity = implode(',', array_values($entry));
            $this->api->Logger->log_inventory_change("inventory item $inventory_item_title "
                . "quantity change: cause: $cause, quantity:$quantity");
        }
    }

}
