<?php

namespace Resgef\SyncList\Lib\EbayApi\GetSellerProfiles;

use Resgef\SyncList\Lib\EbayApi\EbayBusinessPoliciesManagementApi\EbayBusinessPoliciesManagementApi;

//TODO business policies management api differes from trading api, see https://developer.ebay.com/Devzone/business-policies/Concepts/MakingACall.html
class GetSellerProfiles extends EbayBusinessPoliciesManagementApi
{
    function __construct($api_keys, $requestxml = '')
    {
        if (!$requestxml) {
            $requestxml = '<?xml version="1.0" encoding="utf-8"?>
            <getSellerProfilesRequest xmlns="http://www.ebay.com/marketplace/selling">
            <includeDetails>true</includeDetails>
            </getSellerProfilesRequest>';
        }

        parent::__construct($api_keys, 'getSellerProfiles', $requestxml);
    }
}
