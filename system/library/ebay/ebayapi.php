<?php
namespace Ebay;
class EbayAPI
{
    private $api_keys;

    function __construct($api_keys)
    {
        $this->api_keys = $api_keys;
    }

    public function AddItems(Array $items)
    {
        $items_xml = '';
        foreach ($items as $item) {
            $items_xml .= $this->render_xml($item);
        }
        $xml = '<?xml version="1.0" encoding="utf-8"?><AddItemsRequest xmlns="urn:ebay:apis:eBLBaseComponents"><AddItemRequestContainer>' .
            $items_xml .
            '</AddItemRequestContainer></AddItemsRequest>';
        return $xml;
    }

    private function render_xml(Array $options)
    {
        $normalize = function ($options) use (&$normalize) {
            $xml = '';
            if (is_array($options)) {
                foreach ($options as $tagname => $val) {
                    $xml .= sprintf('<%s>%s</%s>', $tagname, $normalize($val), $tagname);
                }
            } else {
                return $options;
            }
            return $xml;
        };
        $xml = $normalize($options);
        return $xml;
    }

    public function get_orders_by_time($timefrom, $timeto, $page)
    {
        $requestXmlBody = "" .
            "<?xml version='1.0' encoding='utf-8'?>"
            . "<GetOrdersRequest xmlns='urn:ebay:apis:eBLBaseComponents'>"
            . "<CreateTimeFrom>"
            . $timefrom
            . "</CreateTimeFrom>"
            . "<CreateTimeTo>"
            . $timeto
            . "</CreateTimeTo>"
            . "<IncludeFinalValueFee>1</IncludeFinalValueFee>"
            . "<OrderRole>Seller</OrderRole>"
            . "<OrderStatus>Completed</OrderStatus>"
            . "<RequesterCredentials>"
            . "<eBayAuthToken>"
            . $this->api_keys['userToken']
            . "</eBayAuthToken>"
            . "</RequesterCredentials>"
            . "<Pagination>"
            . "<EntriesPerPage>200</EntriesPerPage>"
            . "<PageNumber>"
            . $page
            . "</PageNumber>"
            . "</Pagination>"
            . "<DetailLevel>ReturnAll</DetailLevel>"
            . "</GetOrdersRequest>";

        return $this->request('GetOrders', $requestXmlBody);
    }

    public function request($api_name, $request_xml)
    {
        $api_keys = $this->api_keys;
        $session = new eBaySession($api_keys['userToken'], $api_keys['DevID'], $api_keys['AppID'], $api_keys['CertID'], $api_keys['serverUrl'], $api_keys['compatabilityLevel'], $api_keys['siteID'], $api_name);

        $resp = $session->sendHttpRequest($request_xml);

        return $resp;
    }
}