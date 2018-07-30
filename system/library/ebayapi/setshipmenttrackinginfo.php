<?php

namespace resgef\synclist\system\library\ebayapi\setshipmenttrackinginfo;

use resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel;
use resgef\synclist\system\library\ebayapi\ebaymerchantdataapi\EbayMerchantDataApi;

class SetShipmentTrackingInfo extends EbayMerchantDataApi
{
    function __construct(EbayApiKeysModel $ebayApiKeysModel, $callname = 'SetShipmentTrackingInfo')
    {
        parent::__construct($ebayApiKeysModel, $callname);
    }
}