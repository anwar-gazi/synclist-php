<?php

class EbayOrderTransaction {

    /**
     *
     * @var mixed SimpleXmlElement object Transaction node or transaction info db array
     */
    private $info;

    /**
     *
     * @var associative array, dbfield=>ebayfield map
     */
    public $fieldmap;

    /**
     *
     * @param mixed: SimpleXMLElement/array $info, you can instantiate from a transaction xml node
     * or a transaction info array pulled from database
     */
    function __construct($info, Array $dbfield_to_ebayfield_map = []) {
        $this->fieldmap = $dbfield_to_ebayfield_map;
        if (EbayXmlHelper::is_simplexml($info) || is_array($info)) {
            $this->info = $info;
        } else {
            return null;
        }

        return $this;
    }

    /**
     *
     * @param string $property, dbfield or dot seperated ebay trasnaction xml fieldname
     * @return mixed Object||Array||string see EbayOrder::get()
     *
     * similar to EbayOrder::get()
     */
    public function get($property) {
        switch ($property) {
            case "ItemID";
                return $this->extract_itemid();
                break;
            case "OrderID";
                return $this->extract_orderid();
                break;
        }
        /** if $property is a dbfield */
        if (array_key_exists($property, $this->fieldmap)) {
            $property = $this->fieldmap[$property];
        }
        /** if this->info is db fields array but property accessed with ebay field */
        if (is_array($this->info) && array_search($property, $this->fieldmap)) {
            $property = array_search($property, $this->fieldmap);
        }

        $props = explode('.', $property);
        $info = $this->info;
        /** now */
        foreach ($props as $prop) {
            if (EbayXmlHelper::is_simplexml($info)) {
                $info = $info->$prop;
                if (empty($info)) { // $prop value doesn't exist
                    $info = '';
                    break;
                } elseif (!\EbayXmlHelper::has_childnode($info)) { //already in the bottom
                    $info = (string) $info;
                }
            } elseif (is_array($info)) {
                $info = $info[$prop];
            } else {
                $info = '';
                break;
            }
        }

        return $info;
    }

    /**
     *
     * get the properties of the transaction
     * @param string $key
     * @return string
     */
    function __get($key) {
        $val = $this->get($key);
        return $val;
    }

    /**
     * get itemid from OrderLineItemID(which is just Itemid-OrderID)
     * @return string
     */
    private function extract_itemid() {
        $ItemID = $ItemID = $this->{'Item.ItemID'};
        if (!$ItemID) { // parse from OrderLineItemID
            $matches = [];
            if (preg_match("/^(\d+)-\d+/", $this->OrderLineItemID, $matches)) {
                $ItemID = $matches[1];
            }
        }
        return $ItemID;
    }

    /**
     * if the transaction has only one item then OrderLineItemID is the orderid,
     * then, ExtendedOrderID gets absent in the transaction node
     */
    private function extract_orderid() {
        if ($this->ExtendedOrderID) { // parse from ExtendedOrderID
            $matches = [];
            preg_match("/^.+!(\d+)/", $this->ExtendedOrderID, $matches);
            return empty($matches[1]) ? false : $matches[1];
        } else { // parse from OrderLineItemID
            return $this->OrderLineItemID;
        }
    }

}
