<?php

class EbayItemHelper {
    static function really_updated(EbayItem $Item, EbayItem $updatedItem) {
        if ($Item->itemid != $updatedItem->itemid) {
            trigger_error("cannot compare update: not same item");
            return false;
        }
        if ( ($updatedItem->sold==$Item->sold) && ($updatedItem->quantity==$Item->quantity) ) {
            return false;
        }
        return true;
    }
    /*
     * group items by salesdata last fetch time
     * @param Array of EbayItem object
     */
    static function group_by_lft(Array $Items) {
        $group = array();
        foreach($Items as $Item) {
            $group[$Item->lft] = [$Item];
        }
        return $group;
    }
    
    /*
     * remove duplicates from an array of EbayItem object
     * find the duplicates by matching itemid sku combination
     */
    static function unique(Array $Items) {
        // ensure datatype
        $Items = array_filter($Items, function(EbayItem $Item) {
            return $Item;
        });
        $Items_uniq = array();
        foreach($Items as $Item) {
            $Items_uniq[$Item->itemid."-".$Item->sku] = $Item;
        }
        /*
         * now make this into numeric array
         */
        $Items_uniq = array_map(function(EbayItem $Item) {
            return $Item;
        }, $Items_uniq);
        return $Items_uniq;
    }
}