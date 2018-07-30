<?php

class GetMyEbaySellingRequestXmlBuilder {

    private $PageNumber;
    private $ebayAuthToken;
    private $use = [];

    public function activeitems_only($userToken, $PageNumber) {
        $this->ebayAuthToken = $userToken;
        $this->PageNumber = $PageNumber;
        $this->use = ['ebayAuthToken', 'PageNumber'];
        return $this;
    }

    function __get($prop) {
        switch ($prop) {
            case 'xml':
                $val = $this->compile();
                break;
        }
        return $val;
    }

    private function compile() {
        $data = [];
        foreach ($this->use as $var) {
            $data[$var] = $this->$var;
        }
        extract($data);

        $xml = '<?xml version="1.0" encoding="utf-8"?>'
                . '<GetMyeBaySellingRequest xmlns="urn:ebay:apis:eBLBaseComponents">' .
                '<RequesterCredentials><eBayAuthToken>'
                . $ebayAuthToken
                . '</eBayAuthToken></RequesterCredentials>' .
                '<ActiveList>' .
                '<Include>true</Include>' .
                '<IncludeNotes>false</IncludeNotes>' .
                '<Pagination>' .
                '<EntriesPerPage>200</EntriesPerPage>' .
                '<PageNumber>'
                . $PageNumber
                . '</PageNumber>' .
                '</Pagination>' .
                '</ActiveList>' .
                '<UnsoldList><Include>false</Include></UnsoldList>' .
                '<BidList><Include>false</Include></BidList>' .
                '<DeletedFromSoldList><Include>false</Include></DeletedFromSoldList>' .
                '<DeletedFromUnSoldList><Include>false</Include></DeletedFromUnSoldList>' .
                '<ScheduledList><Include>false</Include></ScheduledList>' .
                '<SoldList><Include>false</Include></SoldList>' .
                '<HideVariations>false</HideVariations>' .
                '<DetailLevel>ReturnAll</DetailLevel>' .
                '<OutputSelector>ItemID</OutputSelector>' .
                '<OutputSelector>CurrentPrice</OutputSelector>' .
                '<OutputSelector>ListingStatus</OutputSelector>' .
                '<OutputSelector>Title</OutputSelector>' .
                '<OutputSelector>Quantity</OutputSelector>' .
                '<OutputSelector>GalleryURL</OutputSelector>' .
                '<OutputSelector>QuantityAvailable</OutputSelector>' .
                '<OutputSelector>SellingStatus</OutputSelector>' .
                '<OutputSelector>QuantitySold</OutputSelector>' .
                '<OutputSelector>SKU</OutputSelector>' .
                '<OutputSelector>ViewItemURL</OutputSelector>' .
                '<OutputSelector>UserID</OutputSelector>' .
                '<OutputSelector>TotalNumberOfPages</OutputSelector>' .
                '<OutputSelector>TotalNumberOfEntries</OutputSelector>' .
                '<OutputSelector>Variations</OutputSelector>' .
                '<OutputSelector>Variation</OutputSelector>' .
                '</GetMyeBaySellingRequest>';

        return $xml;
    }

}
