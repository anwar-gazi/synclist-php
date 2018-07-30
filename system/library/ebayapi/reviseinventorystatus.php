<?php

namespace resgef\synclist\system\library\ebayapi\reviseinventorystatus;

use resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel;
use Resgef\SyncList\System\Library\EbayApi\EbayTradingApi\EbayTradingApi;

class ReviseInventoryStatus extends EbayTradingApi
{
    function __construct(EbayApiKeysModel $api_keys)
    {
        parent::__construct($api_keys, 'ReviseInventoryStatus');
    }
}