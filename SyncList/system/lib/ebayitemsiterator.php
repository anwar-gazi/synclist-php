<?php

class EbayItemsIterator implements IteratorAggregate {
    
    // the collection, contains Item object
    protected $_collection = array();
    
    function __construct(Array $ItemsArray) {
        $this->_collection = $ItemsArray;
    }
    
    public function getIterator() {
        return new ArrayIterator($this->_collection);
    }
    public function collection() {
        return $this->_collection;
    }
    
    // merge with another iterator
    public function merge(ItemListIterator $anotherActiveList=null) {
        if (!$anotherActiveList || empty($anotherActiveList->collection)) return null;
        $items = array_merge($this->_collection, $anotherActiveList->collection);
        $this->_collection = $items;
    }
    public function mergeArray() {
        
    }
    // add to the collections
    public function addItem(EbayItem $Item) {
        $this->_collection[] = $Item;
    }
    /*
     * set and replace the collection 
     */
    public function setArray(Array $Items) {
        $this->_collection = $Items;
    }
    
    /*
     * get an item from the collection by itemid
     */
    public function get($itemid) {
        $Item;
        foreach($this->_collection as $item) {
            if ($item->itemid==$itemid) {
                $Item = $item;
                break;
            }
        }
        return $Item;
    }
    /*
     * returns itemid of all items in the collection
     */
    public function itemidAll() {
        $ids = array();
        foreach($this->_collection as $Item) {
            $ids[] = $Item->itemid;
        }
        return $ids;
    }
    /*
     * return items from collection excluding the $Items specified here
     * @param $Items an array of item object
     * @return array of items
     */
    public function excluding(Array $Items) {
        $this_itemid_all = $this->itemidAll();
        $excluding_itemid = array();
        foreach($Items as $I) {
            $excluding_itemid[] = $I->itemid;
        }
        $needed_itemid = array_diff($this_itemid_all, $excluding_itemid);
        
        $Items = array();
        foreach($needed_itemid as $itemid) {
            $Items[] = $this->get($itemid);
        }
        
        return $Items;
    }
}