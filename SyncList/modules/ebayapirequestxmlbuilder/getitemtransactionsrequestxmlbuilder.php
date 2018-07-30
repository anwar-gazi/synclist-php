<?php

class GetItemTransactionsRequestXmlBuilder
{
    public function item_transactions($itemid, $userToken, $pagenum)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>'
            . '<GetItemTransactionsRequest xmlns="urn:ebay:apis:eBLBaseComponents">'
            . '<RequesterCredentials>'
            . '<eBayAuthToken>'
            . $userToken
            . '</eBayAuthToken>'
            . '</RequesterCredentials>'
            . '<IncludeContainingOrder>true</IncludeContainingOrder>'
            . '<IncludeFinalValueFee>true</IncludeFinalValueFee>'
            . '<IncludeVariations>true</IncludeVariations>'
            . '<ItemID>' . $itemid . '</ItemID>'
            . '<NumberOfDays>20</NumberOfDays>'
            . '<Pagination>'
            . '<EntriesPerPage>200</EntriesPerPage>'
            . '<PageNumber>' . $pagenum . '</PageNumber>'
            . '</Pagination>'
            . '<DetailLevel>ReturnAll</DetailLevel>'
            . '</GetItemTransactionsRequest>';
        return $xml;
    }
}