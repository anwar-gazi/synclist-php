<?php

class EbayTransactionsIterator implements IteratorAggregate, countable {
    /*
     * an array of transactions object(EbayTransaction class), inside each transaction, its item is represented by EbayItem objct
     */
    private $collection = array();
    
    /*
     * @param array of EbayItemTransactions objects
     */
    function __construct(Array $Transactions_object_array=array()) {
        $this->collection = $Transactions_object_array;
    }
    
    public function getIterator() {
        return new ArrayIterator($this->collection);
    }
    function count() {
        return count($this->collection);
    }
    
    /*
     * add a transaction to the collection
     */
    public function add(EbayTransaction $Transaction) {
        $this->collection[] = $Transaction;
        return $this;
    }
    
    /*
     * merge to this collection another array of EbayTransaction objects, and sets $this collection with the merge result
     *
     * @param array $EbayTransaction_objects, an array containing EbayTransaction objects,
     * 
     * @return void
     * 
     */
    public function mergeArray(Array $EbayTransaction_objects) {
        if (empty($EbayTransaction_objects)) return null;
        $this->collection = array_merge($this->collection, $EbayTransaction_objects);
        return $this;
    }
    
    /*
     * remove current collection and sets the provided array
     * @param array of EbayTransaction objects
     */
    public function reaplceWithArray(Array $EbayTransaction_objects) {
        $this->collection = $EbayTransaction_objects;
    }
    
    /*
     * return the Item object of all transactions
     * @param void
     * @return array of EbayItem objects from inside the transactions collection, 
     */
    function TransactionItems() {
        $ItemsArray = array_map(function($Tr) {
            return $Tr->Item;
        }, $this->collection);
        return $ItemsArray;
    }
    
    /*
     * build a human readable report string of how many transactions, how many for each item
     * @param void
     * @return string
     * TODO: next version, show which items are updated too
     */
    function report_for_human() {
        $new_tr_count = count($this->collection);
        $report = "$new_tr_count transactions";
        /*
        $grouped = EbayTransactionHelper::group_by_itemid($this->collection);
        print_r($grouped);
        foreach($grouped as $itemid=>$TransactionsArray) {
            $item_title = $TransactionsArray[0]->Item->title;
            $item_trs_count = count($TransactionsArray);
            $report .= ", $item_title: $item_trs_count";
        }*/
        return $report;
    }
    
    function __toArray() {
        return $this->collection;
    }
}
?>