<?php

namespace resgef\synclist\system\library\ebayapi\completesale;

use resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel;
use Resgef\SyncList\System\Library\EbayApi\EbayTradingApi\EbayTradingApi;

class CompleteSale extends EbayTradingApi
{
    function __construct(EbayApiKeysModel $api_keys, $callname = 'CompleteSale')
    {
        parent::__construct($api_keys, $callname);
    }

    function set_shipment_tracking($OrderID, $tracking_num, $carrier)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>'
            . '<CompleteSaleRequest xmlns="urn:ebay:apis:eBLBaseComponents">'
            . '<RequesterCredentials>'
            . '<eBayAuthToken>'
            . $this->api_keys->requestToken
            . '</eBayAuthToken>'
            . '</RequesterCredentials>'
            . '<OrderID>' . $OrderID . '</OrderID>'
            . '<Shipment>'
            . '<ShipmentTrackingDetails>'
            . '<ShipmentTrackingNumber>' . $tracking_num . '</ShipmentTrackingNumber>'
            . '<ShippingCarrierUsed>' . $carrier . '</ShippingCarrierUsed>'
            . '</ShipmentTrackingDetails>'
            . '</Shipment>'
            . '</CompleteSaleRequest>';
        return $this->execute($xml);
    }
}