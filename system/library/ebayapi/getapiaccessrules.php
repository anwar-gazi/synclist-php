<?php

namespace resgef\synclist\system\library\ebayapi\getapiaccessrules;

use resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel;
use Resgef\SyncList\System\Library\EbayApi\EbayApiResponse\EbayApiResponse;
use Resgef\SyncList\System\Library\EbayApi\EbayTradingApi\EbayTradingApi;

class GetApiAccessRules extends EbayTradingApi
{
    function __construct(EbayApiKeysModel $api_keys, $callname = 'GetApiAccessRules')
    {
        parent::__construct($api_keys, $callname);
    }
}