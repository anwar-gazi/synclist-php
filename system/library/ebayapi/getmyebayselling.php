<?php

namespace Resgef\SyncList\System\Library\EbayApi\GetMyEbaySelling;

use resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel;
use Resgef\SyncList\System\Library\EbayApi\EbayTradingApi\EbayTradingApi;

class GetMyEbaySelling extends EbayTradingApi
{
    function __construct(EbayApiKeysModel $api_keys)
    {
        parent::__construct($api_keys, 'GetMyeBaySelling');
    }
}