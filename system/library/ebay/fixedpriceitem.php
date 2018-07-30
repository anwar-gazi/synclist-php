<?php

namespace Resgef\SyncList\Lib\FixedPriceItem;

class FixedPriceItem {
    public $local_id;
    public $ebay_ItemID;
    public $local_quantity;
    public $local_price;
    public $local_supplier_id;
    public $ean;

    public $sync_required = true;

    public $customer;
}