<?php

namespace Resgef\SyncList\Lib\EbayApi\GetCategories;

use Resgef\SyncList\Lib\EbayApi\EbayTradingApi\EbayTradingApi;

class GetCategories extends EbayTradingApi
{
    function __construct($api_keys, $requestxml = '')
    {
        if (!$requestxml) {
            $requestxml =
                '<?xml version="1.0" encoding="utf-8"?>
            <GetCategoriesRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                <RequesterCredentials>
                <eBayAuthToken>' . $api_keys->requestToken . '</eBayAuthToken>
                </RequesterCredentials>
                <ViewAllNodes>true</ViewAllNodes>
                <DetailLevel>ReturnAll</DetailLevel>
                <ErrorLanguage>en_US</ErrorLanguage>
            </GetCategoriesRequest>';
        }
        parent::__construct($api_keys, 'GetCategories', $requestxml);
    }

    /**
     * @param \SimpleXMLElement $GetCategoriesxml
     * @return array
     */
    function parse_list(\SimpleXMLElement $GetCategoriesxml)
    {
        $categories = [];
        /** @var \SimpleXMLElement $Category */
        foreach ($GetCategoriesxml->CategoryArray->Category as $Category) {
            $categories[] = [
                'CategoryID' => (string)$Category->CategoryID,//max len 10
                'CategoryLevel' => (string)$Category->CategoryLevel,
                'CategoryName' => (string)$Category->CategoryName,
                'CategoryParentID' => (string)$Category->CategoryParentID,//max len 10 // immediate parent
                'LeafCategory' => (integer)((string)$Category->LeafCategory == 'true'),
                'CategoryVersion' => (string)$GetCategoriesxml->CategoryVersion
            ];
        }
        return $categories;
    }
}
