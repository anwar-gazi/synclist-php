<?php

class GetOrderTransactionsRequestXmlBuilder
{
    public function order_transactions($orderid, $userToken)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>'
            . '<GetOrderTransactionsRequest xmlns="urn:ebay:apis:eBLBaseComponents">'
            . '<RequesterCredentials>'
            . '<eBayAuthToken>'
            . $userToken
            . '</eBayAuthToken>'
            . '</RequesterCredentials>'
            . '<OrderIDArray>'
            . '<OrderID>' . $orderid . '</OrderID>'
            . '</OrderIDArray>'
            . '<DetailLevel>ReturnAll</DetailLevel>'
            . '</GetOrderTransactionsRequest>';
        return $xml;
    }
}