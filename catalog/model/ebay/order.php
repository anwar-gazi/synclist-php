<?php

class ModelEbayOrder extends Model {

    /**
     * pull all orders with short info just for building all orders listing table
     */
    function seller_orders($username) {
        $query = $this->db->query("SELECT * FROM " . EBAY_ORDERS_TABLE . " WHERE seller_userid='" . $this->db->escape($username) . "'");
        if (!$query->num_rows)
            return null;
        return $query->rows;
    }

    /**
     * get all ebay orders for all ebay users
     */
    function orders_listing() {
        $query = $this->db->query("SELECT * FROM " . EBAY_ORDERS_TABLE);
        if (!$query->num_rows)
            return null;
        return $query->rows;
    }

    function order_transactions($orderid) {
        $query = $this->db->query("SELECT * FROM " . EBAY_ORDER_TRANSACTIONS_TABLE . " WHERE orderid='" . $this->db->escape($orderid) . "'");
        return $query->rows;
    }

    function pull_order($orderid) {
        $query = $this->db->query("SELECT * FROM " . EBAY_ORDERS_TABLE . " WHERE orderid='" . $this->db->escape($orderid) . "'");
        if (!$query->num_rows)
            return null;
        return $query->row;
    }

    function get_xml($orderid) {
        return $this->db->query("SELECT orderid,order_xml FROM " . EBAY_ORDERS_TABLE . " WHERE orderid='" . $this->db->escape($orderid) . "'")->row['order_xml'];
    }

    /**
     * 
     * get the seller names from the orders
     * @return array of unique seller ebay usernames
     */
    function field_vals($fieldname) {
        $rows = $this->db->query("select $fieldname from " . EBAY_ORDERS_TABLE)->rows;
        $names = array_map(function(Array $row) use($fieldname) {
            return $row[$fieldname];
        }, $rows);
        return array_unique($names);
    }

}
