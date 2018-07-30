<?php

class SetShipmentTrackingInfoRequestXmlBuilder
{

    public function set_tracking($OrderID, $tracking_number, $shipping_carrier)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>' .
            '<SetShipmentTrackingInfoRequest xmlns="urn:ebay:apis:eBLBaseComponents">' .
            '<OrderID>' . $OrderID . '</OrderID>' .
            '<Shipment>' .
            '<ShipmentTrackingDetails>' .
            '<ShipmentTrackingNumber>' . $tracking_number
            . '</ShipmentTrackingNumber>' .
            '<ShippingCarrierUsed>' . $shipping_carrier . '</ShippingCarrierUsed>' .
            '</ShipmentTrackingDetails>' .
            '</Shipment>' .
            '</SetShipmentTrackingInfoRequest>';

        return $xml;
    }

}
