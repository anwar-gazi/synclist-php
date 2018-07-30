<?php

namespace Resgef\SyncList\System\Library\EbayApi\GetItem;

use resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel;
use Resgef\SyncList\System\Library\EbayApi\EbayTradingApi\EbayTradingApi;

class GetItem extends EbayTradingApi
{
    function __construct(EbayApiKeysModel $api_keys)
    {
        parent::__construct($api_keys, 'GetItem');
    }

    /**
     * @param $itemid
     * @param $sku
     * @return \Resgef\SyncList\System\Library\EbayApi\EbayApiResponse\EbayApiResponse
     */
    function fetch_item($itemid, $sku)
    {
        $requestxml = sprintf('<?xml version="1.0" encoding="utf-8"?>'
            . '<GetItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">'
            . '<IncludeItemSpecifics>true</IncludeItemSpecifics>'
            . '<DetailLevel>ReturnAll</DetailLevel>'
            . '<RequesterCredentials><eBayAuthToken>%s</eBayAuthToken></RequesterCredentials>'
            . '<IncludeItemSpecifics>True</IncludeItemSpecifics>'
            . '<ItemID>%s</ItemID>'
            . '<VariationSKU>%s</VariationSKU>'
            . '</GetItemRequest>', $this->api_keys->requestToken, $itemid, $sku);
        return $this->execute($requestxml);
    }
}