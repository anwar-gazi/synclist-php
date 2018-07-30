<?php

/** @property SetShipmentTrackingInfoRequestXmlBuilder SetShipmentTrackingInfo */
/** @property CompleteSaleRequestXmlBuilder CompleteSale */
class EbayApiRequestXmlBuilder extends SyncListModule {

    function __get($api_name) {
        $class = $api_name . 'RequestXmlBuilder';
        require_once __DIR__ . '/' . strtolower($class) . '.php';
        if (class_exists($class)) {
            return new $class();
        }
    }

}
