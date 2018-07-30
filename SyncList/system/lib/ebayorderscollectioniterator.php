<?php
/**
 * to work with ebay orders in mass
 */
class EbayOrdersCollectionIterator implements IteratorAggregate, Countable {
    protected $_collection = array();
    function __construct(Array $orders) {
        // ensure data type
        $this->_collection = array_filter($orders, function(EbayOrder $Order) {
            return $Order;
        });
    }
    function getIterator() {
        return new ArrayIterator($this->_collection);
    }
    /**
     * 
     */
    function saveOrders() {
        $report = "";
        $Table = new EbayOrdersTable();
        $not_saved = array_filter($this->_collection, function(EbayOrder $Order) use ($Table) {
            $error = '';
            $res = $Table->save_order($Order, $error);
            if (!$res) { // error on save
                return true;
            } else {
                return false;
            }
        });
        
        $not_saved_ids = array_map(function(EbayOrder $Order) {
            return $Order->orderid;
        }, $not_saved);
        
        if (!empty($not_saved_ids)) {
            trigger_error(
                sprintf("%s order not saved, the are %s", count($not_saved_ids), implode(",", $not_saved_ids))
            );
        }
    }
    
    /**
     * save the 
     */
    function saveOrderTransactions() {
        $report = "";
        $Table = new EbayOrderTransactionsTable();
        $not_saved = array_filter($this->_collection, function(EbayOrder $Order) use ($Table) {
            $error = '';
            $res = $Table->save_order($Order, $error);
            if (!$res) { // error on save
                return true;
            } else {
                return false;
            }
        });
        
        $not_saved_ids = array_map(function(EbayOrder $Order) {
            return $Order->orderid;
        }, $not_saved);
        
        if (!empty($not_saved_ids)) {
            trigger_error(
                sprintf("%s order not saved, the are %s", count($not_saved_ids), implode(",", $not_saved_ids))
            );
        }
    }
    
    function count() {
        return count($this->_collection);
    }
}