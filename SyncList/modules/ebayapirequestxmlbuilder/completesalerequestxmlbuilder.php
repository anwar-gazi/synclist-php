<?php

/**
 * Created by PhpStorm.
 * User: droid
 * Date: 11/12/16
 * Time: 11:43 AM
 */
class CompleteSaleRequestXmlBuilder
{
    function set_shipment_tracking($userToken, $OrderID, $tracking_num, $carrier, $ShippedTIme)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>'
            . '<CompleteSaleRequest xmlns="urn:ebay:apis:eBLBaseComponents">'
            . '<RequesterCredentials>'
            . '<eBayAuthToken>'
            . $userToken
            . '</eBayAuthToken>'
            . '</RequesterCredentials>'
            . '<OrderID>' . $OrderID . '</OrderID>'
            . '<Shipment>'
            . '<ShipmentTrackingDetails>'
            . '<ShipmentTrackingNumber>' . $tracking_num . '</ShipmentTrackingNumber>'
            . '<ShippingCarrierUsed>' . $carrier . '</ShippingCarrierUsed>'
            . '</ShipmentTrackingDetails>'
            //. '<ShippedTime>' . $ShippedTIme . '</ShippedTime>'
            . '</Shipment>'
            . '</CompleteSaleRequest>';
        return $xml;
    }

    private function compile()
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>'
            . '<CompleteSaleRequest xmlns="urn:ebay:apis:eBLBaseComponents">'
            . '<FeedbackInfo> FeedbackInfoType'
            . '<CommentText> string </CommentText>'
            . '<CommentType> string </CommentType>'
            . '<TargetUser> string </TargetUser>'
            . '</FeedbackInfo>'
            . '<ItemID> string </ItemID>'
            . '<ListingType> string </ListingType>'
            . '<OrderID> string </OrderID>'
            . '<OrderLineItemID> string </OrderLineItemID>'
            . '<Paid> boolean </Paid>'
            . '<Shipment> ShipmentType'
            . '<Notes> string </Notes>'
            . '<ShipmentTrackingDetails> ShipmentTrackingDetailsType'
            . '<ShipmentLineItem> ShipmentLineItemType'
            . '<LineItem> LineItemType'
            . '<CountryOfOrigin> string </CountryOfOrigin>'
            . '<Description> string </Description>'
            . '<ItemID> string </ItemID>'
            . '<Quantity> int </Quantity>'
            . '<TransactionID> string </TransactionID>'
            . '</LineItem>'
            . '</ShipmentLineItem>'
            . '<ShipmentTrackingNumber> string </ShipmentTrackingNumber>'
            . '<ShippingCarrierUsed> string </ShippingCarrierUsed>'
            . '</ShipmentTrackingDetails>'
            . '<ShippedTime> dateTime </ShippedTime>'
            . '</Shipment>'
            . '<Shipped> boolean </Shipped>'
            . '<TransactionID> string </TransactionID>'
            . '<ErrorHandling> string </ErrorHandling>'
            . '<ErrorLanguage> string </ErrorLanguage>'
            . '<MessageID> string </MessageID>'
            . '<Version> string </Version>'
            . '<WarningLevel> string </WarningLevel>'
            . '</CompleteSaleRequest>';
        return $xml;
    }
}