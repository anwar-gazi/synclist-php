<?php
/** 
 * @property string $xml
 */
class GetOrdersRequestXmlBuilder {

    private $PageNumber;
    private $ebayAuthToken;
    private $CreateTimeFrom;
    private $CreateTimeTo;
    private $use = [];

    /**
     * get details os specific orders
     * @param array $OrderID_collection
     * @param string $usertoken the specific ebay seller's
     * @return string
     */
    public function specific_orders(Array $OrderID_collection, $usertoken, $PageNumber) {
        $OrderID_xml = '';
        foreach ($OrderID_collection as $OrderID) {
            $OrderID_xml .= "<OrderID>$OrderID</OrderID>";
        }
        $requestXmlBody = "" .
                "<?xml version='1.0' encoding='utf-8'?>"
                . "<GetOrdersRequest xmlns='urn:ebay:apis:eBLBaseComponents'>"
                . "<OrderIDArray>"
                . call_user_func(function() {
                $OrderID_xml = '';
                foreach ($OrderID_collection as $OrderID) {
                    $OrderID_xml .= "<OrderID>$OrderID</OrderID>";
                }
            })
                . "</OrderIDArray>"
                . "<OrderRole>Seller</OrderRole>"
                . "<RequesterCredentials>"
                . "<eBayAuthToken>"
                . $usertoken
                . "</eBayAuthToken>"
                . "</RequesterCredentials>"
                . "<Pagination>"
                . "<EntriesPerPage>200</EntriesPerPage>"
                . "<PageNumber>"
                . $PageNumber
                . "</PageNumber>"
                . "</Pagination>"
                . "<DetailLevel>ReturnAll</DetailLevel>"
                . "</GetOrdersRequest>";
        return $requestXmlBody;
    }

    public function all_orders($time_from, $time_to, $usertoken, $pagenum) {
        $this->CreateTimeFrom = $time_from;
        $this->CreateTimeTo = $time_to;
        $this->eBayAuthToken = $usertoken;
        $this->PageNumber = $pagenum;
        $this->use = ['CreateTimeFrom', 'CreateTimeTo', 'eBayAuthToken', 'PageNumber'];
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

    public function compile() {
        $data = [];
        foreach ($this->use as $var) {
            $data[$var] = $this->$var;
        }
        extract($data);

        $requestXmlBody = "" .
                "<?xml version='1.0' encoding='utf-8'?>"
                . "<GetOrdersRequest xmlns='urn:ebay:apis:eBLBaseComponents'>"
                . "<CreateTimeFrom>"
                . $CreateTimeFrom
                . "</CreateTimeFrom>"
                . "<CreateTimeTo>"
                . $CreateTimeTo
                . "</CreateTimeTo>"
                . "<IncludeFinalValueFee>1</IncludeFinalValueFee>"
                . "<OrderRole>Seller</OrderRole>"
                . "<OrderStatus>Completed</OrderStatus>"
                . "<RequesterCredentials>"
                . "<eBayAuthToken>"
                . $eBayAuthToken
                . "</eBayAuthToken>"
                . "</RequesterCredentials>"
                . "<Pagination>"
                . "<EntriesPerPage>200</EntriesPerPage>"
                . "<PageNumber>"
                . $PageNumber
                . "</PageNumber>"
                . "</Pagination>"
                . "<DetailLevel>ReturnAll</DetailLevel>"
                . "</GetOrdersRequest>";
        /* specific days
          <NumberOfDays>28</NumberOfDays>
         */
        return $requestXmlBody;
    }

    public function get_tracking_code($orderid, $userToken, $pagenumber) {
        $requestXmlBody = "" .
            "<?xml version='1.0' encoding='utf-8'?>"
            . "<GetOrdersRequest xmlns='urn:ebay:apis:eBLBaseComponents'>"
            . "<OrderIDArray>"
            . "<OrderID>$orderid</OrderID>"
            . "</OrderIDArray>"
            . "<OrderRole>Seller</OrderRole>"
            . "<RequesterCredentials>"
            . "<eBayAuthToken>"
            . $userToken
            . "</eBayAuthToken>"
            . "</RequesterCredentials>"
            . "<Pagination>"
            . "<EntriesPerPage>200</EntriesPerPage>"
            . "<PageNumber>"
            . $pagenumber
            . "</PageNumber>"
            . "</Pagination>"
            . "<DetailLevel>ReturnAll</DetailLevel>"
            . "</GetOrdersRequest>";
        return $requestXmlBody;
    }

}
