<?php

class Modellistinglisting extends Model
{
    public function save_item(Array $fields)
    {
        $set_qstr = array_map(function ($val, $key) {
            return "`$key`='{$this->db->escape($val)}'";
        }, $fields, array_keys($fields));
        $entry_exists = $this->db->query("select * from sl_listings where `uniqid`='{$fields['uniqid']}'")->num_rows;
        if ($entry_exists) {
            $sql = "update sl_listings set " . implode("\n,", $set_qstr) . " where `uniqid`='{$fields['uniqid']}'";
        } else {
            $sql = "INSERT INTO sl_listings SET " . implode("\n,", $set_qstr);
        }
        $this->db->query($sql);
        return true;
    }

    function seller_items($seller, $listing_provider)
    {
        return $this->db->query("select * from sl_listings where active=1 and seller='$seller' and listing_provider='$listing_provider'")->rows;
    }
}