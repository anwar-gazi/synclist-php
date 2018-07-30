<?php

class EbayTransactionHelper {
    /*
     * get itemid from the transaction node
     * @return string itemid when OrderLineItemID is found(always supposed to be)
     */
    static function itemid(simplexmlelement $transaction_node) {
        $matches = array();
        $line_itemid = (string) $transaction_node->OrderLineItemID;
        return self::itemid_from_lid($line_itemid);
    }
    static function itemid_from_lid($OrderLineItemID) {
        $res = preg_match("/^(\d+)-\d+/",$OrderLineItemID, $matches);
        return empty($matches[1])?false:$matches[1];
    }
    /*
     * if the transaction has only one item then OrderLineItemID is the orderid,
     * then, ExtendedOrderID get absent in the transaction node
     */
    static function orderid(simplexmlelement $transaction_node) {
        $line_itemid = (string) $transaction_node->OrderLineItemID;
        $extended_orderid = (string) $transaction_node->ExtendedOrderID;
        
        if (!$extended_orderid) return self::orderid_from_lid($line_itemid);
        
        return self::orderid_from_x_orderid($extended_orderid);
    }
    static function orderid_from_lid($OrderLineItemID) {
        return $OrderLineItemID;
    }
    static function orderid_from_x_orderid($ExtendedOrderID) {
        $matches = array();
        $res = preg_match("/^.+!(\d+)/",$ExtendedOrderID, $matches);
        return empty($matches[1])?false:$matches[1];
    }
    
    /*
     * take an array of Transaction objects, group them(makes an indented array) by the transaction items id
     * 
     * @param Array $transactions, an array of Transaction objects(EbayTransaction class)
     * 
     * @return array, with keys as itemid, values are array of transaction objects having that item(itemid)
     * 
     */
    static function group_by_itemid(Array $transactions) {
        $transactions = array_filter($transactions, function(EbayItemTransaction $Trans) {
            return $Trans;
        });
        $grouped = array();
        foreach($transactions as $Tr) {
            $Item = $Tr->Item;
            $itemid = $Item->itemid;
            
            $grouped[$itemid] = [$Tr];
        }
        return $grouped;
    }
}