<?php

namespace Resgef\SyncList\Lib\EbayApi\AddFixedPriceItem;

use Resgef\SyncList\Lib\EbayApi\EbayApiResponse\EbayApiResponse;
use Resgef\SyncList\Lib\EbayApi\EbayTradingApi\EbayTradingApi;

class AddFixedPriceItem extends EbayTradingApi
{
    function __construct($api_keys, $requestxml)
    {
        parent::__construct($api_keys, 'AddFixedPriceItem', $requestxml);
    }
}