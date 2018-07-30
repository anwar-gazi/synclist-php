<?php

class GetSellingManagerSaleRecordRequestXmlBuilder
{
    public function index($orderid, $userToken)
    {
        return '<?xml version="1.0" encoding="utf-8"?>'
        . '<GetSellingManagerSaleRecordRequest xmlns="urn:ebay:apis:eBLBaseComponents">'
        . '<RequesterCredentials>'
        . '<eBayAuthToken>'
        . $userToken
        . '</eBayAuthToken>'
        . '</RequesterCredentials>'
        . '<OrderID>' . $orderid . '</OrderID>'
        . '</GetSellingManagerSaleRecordRequest>';
    }
}