<?php

class EbayItem implements iteratoraggregate, Countable {

    private $db;
    private $info = [];

    /**
     *
     * @param type $info
     * @param DBMySQLi $db if you dont provide this then
     * database actions, eg. $this->save() will not be available
     * @return \EbayItem
     */
    function __construct($info, DBMySQLi $db = null) {
        $this->db = $db;
        $Table = new EbayItemTable(null);

        if (EbayXmlHelper::is_simplexml($info)) {
            $info_arr = [];
            /** buid array with dbfields as keys */
            foreach ($Table->fieldmap() as $dbfield => $xmlfield) {
                $info_arr[$dbfield] = EbayXmlHelper::val($info, $xmlfield);
            }
            $this->info = $info_arr;
        } elseif (is_array($info)) {
            $this->info = $info;
        } else {
            return null;
        }

        return $this;
    }

    function __get($property) {
        switch ($property) {
            case 'salesdata_last_fetch_time':
                if (array_key_exists($property, $this->info)) {
                    $val = $this->info[$property];
                } else {
                    $val = '';
                }
                break;
            case 'id':
            case 'uniq_itemid':
                if (array_key_exists('uniq_itemid', $this->info) && $this->info['uniq_itemid']) {
                    $val = $this->info['uniq_itemid'];
                } else {
                    $val = $this->generate_uniqid();
                }
                break;
            case 'Quantity':
                if (array_key_exists($property, $this->info)) {
                    $val = $this->info[$property];
                } else {
                    $val = $this->info['QuantitySold'] + $this->info['QuantityAvailable'];
                }
                break;
            case 'QuantityAvailable':
                if (array_key_exists($property, $this->info)) {
                    $val = $this->info[$property];
                } else {
                    $val = $this->info['Quantity'] - $this->info['QuantitySold'];
                }
                break;
            default:
                $val = $this->info[$property];
        }

        return $val;
    }

    private function generate_uniqid() {
        if ($this->SKU) {
            $sku = preg_replace("#[\s-]#", '', $this->SKU);
        } else {
            $sku = '';
        }
        return "ebay{$this->SellerUserID}{$this->ItemID}{$sku}";
    }

    /**
     *
     * @return type
     * so that you can use count like array, this returns number of actual items
     * if there are variations, then count returns number of them
     * if there are no variations, then count returns 1
     * so this always return minimum 1 where iterator_count returns 0 for no variations
     */
    function count() {
        $vars_count = $this->variation_count;
        $true_count = $vars_count + 1;
        return $true_count;
    }

    /**
     *
     * @return \ArrayIterator
     */
    function getIterator() {
        $items = $this->variationsArray;
        return new ArrayIterator($items);
    }

    /**
     * load from db
     * @return new object of this class
     */
    public function load($itemid, $sku) {
        $info = DB_item::row($itemid, $sku);
        $Item = new self($info);
        return $Item;
    }

    public function save() {
        $Table = new EbayItemTable($this->db);
        $Table->save_item($this);
    }

}
