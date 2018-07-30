<?php

/**
 * Class GetSellerTransactionsRequestXmlBuilder
 * @property string $xml
 */
class GetSellerTransactionsRequestXmlBuilder {

    private $eBayAuthToken;
    private $ModTimeTo;
    private $ModTimeFrom;
    private $PageNumber;
    
    private $use = [];

    private function compile() {
        $data = [];
        foreach ($this->use as $var) {
            $data[$var] = $this->$var;
        }
        extract($data);

        $requestXmlBody = '<?xml version="1.0" encoding="utf-8" ?>'
                . '<GetSellerTransactions xmlns="urn:ebay:apis:eBLBaseComponents">'
                . '<RequesterCredentials><eBayAuthToken>' .
                $eBayAuthToken .
                '</eBayAuthToken></RequesterCredentials>'
                . '<Pagination><EntriesPerPage>200</EntriesPerPage>'
                . '<PageNumber>'
                .$PageNumber
                . '</PageNumber>'
                . '</Pagination>';

        if ($ModTimeFrom && $ModTimeTo) {
            $requestXmlBody .=
                    "<ModTimeFrom>{$ModTimeFrom}</ModTimeFrom>" .
                    "<ModTimeTo>{$ModTimeTo}</ModTimeTo>";
        }

        $requestXmlBody .= '</GetSellerTransactions>';

        return $requestXmlBody;
    }

    public function transactions($usertoken, $time_from, $time_to, $pagenumber) {
        $this->eBayAuthToken = $usertoken;
        $this->ModTimeFrom = $time_from;
        $this->ModTimeTo = $time_to;
        $this->PageNumber = $pagenumber;
        $this->use = ['eBayAuthToken', 'ModTimeFrom', 'ModTimeTo', 'PageNumber'];
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

}
