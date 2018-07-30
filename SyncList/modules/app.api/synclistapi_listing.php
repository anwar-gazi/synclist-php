<?php

/**
 * all stores combined
 */
class SyncListAPiListing
{

    /** @var DBMySQLi Description */
    private $db;

    /** @var SyncListApi */
    private $api;

    function __construct(SyncListApi $api)
    {
        $this->db = $api->db;
        $this->api = $api;
    }

    function seller_info()
    {
        return $this->db->query("SELECT DISTINCT seller,listing_provider FROM sl_listings")->rows;
    }

    function seller_items($seller, $listing_provider)
    {
        return $this->db->query("select * from sl_listings where active=1 and seller='$seller' and listing_provider='$listing_provider'")->rows;
    }

    function active_items()
    {
        return $this->db->query("SELECT * FROM sl_listings WHERE `active`=1 ORDER BY `listing_provider`,`title`")->rows;
    }

    function items()
    {
        return $this->db->query("SELECT * FROM sl_listings ORDER BY `listing_provider`,`title`")->rows;
    }

    public function save_item(Array $fields)
    {
        $set_qstr = array_map(function ($val, $key) {
            return "`$key`='{$this->api->db->escape($val)}'";
        }, $fields, array_keys($fields));
        $entry_exists = $this->api->db->query("select * from sl_listings where `uniqid`='{$fields['uniqid']}'")->num_rows;
        if ($entry_exists) {
            $sql = "update sl_listings set " . implode("\n,", $set_qstr) . " where `uniqid`='{$fields['uniqid']}'";
        } else {
            $sql = "INSERT INTO sl_listings SET " . implode("\n,", $set_qstr);
        }
        $this->api->db->query($sql);
        return true;
    }

    /**
     * @return array
     */
    function active_items_grouped()
    {
        $grped = [];
        foreach ($this->active_items() as $item) {
            $remote = $item['listing_provider'];
            if (!array_key_exists($remote, $grped)) {
                $grped[$remote] = [];
            }
            $grped[$remote][] = $item;
        }
        return $grped;
    }

    function itemid_title_map()
    {
        $table_name = $this->api->table_name('listings');
        $map = [];
        foreach ($this->db->query("select itemid, title from $table_name")->rows as $info) {
            $map[$info['itemid']] = $info['title'];
        }
        return $map;
    }

    function item_pic_url($ItemID)
    {
        $table_name = $this->api->table_name('listings');
        return @$this->db->query("select * from $table_name where `itemid`='$ItemID'")->row['pic_url'];
    }

}
