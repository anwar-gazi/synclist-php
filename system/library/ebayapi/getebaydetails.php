<?php

namespace Resgef\SyncList\Lib\EbayApi\GeteBayDetails;

use Resgef\SyncList\Lib\EbayApi\EbayTradingApi\EbayTradingApi;
use Resgef\SyncList\Models\EbayShippingLocationDetailsModel\EbayShippingLocationDetailsModel;
use Resgef\SyncList\Models\EbayShippingServiceModel\EbayShippingServiceModel;

class GeteBayDetails extends EbayTradingApi
{
    function __construct($api_keys, $requestxml = '')
    {
        if (!$requestxml) {
            $requestxml = '<?xml version="1.0" encoding="utf-8"?>
                            <GeteBayDetailsRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                            <RequesterCredentials>
                            <eBayAuthToken>' . $api_keys->requestToken . '</eBayAuthToken>
                            </RequesterCredentials>
                            <ErrorLanguage>en_US</ErrorLanguage>
                            <WarningLevel>High</WarningLevel>
                            </GeteBayDetailsRequest>';
        }
        parent::__construct($api_keys, 'GeteBayDetails', $requestxml);
    }

    /**
     * get list of shipping locations.
     * the shipping location is a Short name or abbreviation for a region (e.g., Asia) or location (e.g. Japan)
     * @return array|null
     * @throws \Exception
     */
    function get_ShippingLocations()
    {
        $response = $this->execute();
        if ($response->error) {
            throw new \Exception('Error: ' . $response->error . ', xml:' . $response->xml);
        }

        $locations = [];
        foreach ($response->xml->ShippingLocationDetails as $shippingLocationDetail) {
            $locations[] = (string)$shippingLocationDetail->ShippingLocation;
        }
        return $locations;
    }

    /**
     * Lists the shipping carriers supported by the specified site
     * @return EbayShippingServiceModel[]
     * @throws \Exception
     */
    function get_shipping_service_details()
    {
        //using default requestxml to fetch everything
        $api = new self($this->api_keys);
        $response = $api->execute();
        if ($response->error) {
            throw new \Exception('Error: ' . $response->error . ', xml:' . $response->xml);
        }

        $EbayShippingServiceModels = [];

        foreach ($response->xml->ShippingServiceDetails as $ShippingServiceDetailsNode) {
            $service = new EbayShippingServiceModel();
            $service->SiteID = $this->api_keys->siteID;
            $service->ShippingCarrier = (string)$ShippingServiceDetailsNode->ShippingCarrier;
            $service->ShippingServiceID = (string)$ShippingServiceDetailsNode->ShippingServiceID;
            $service->ShippingService = (string)$ShippingServiceDetailsNode->ShippingService;
            $service->ServiceType = (string)$ShippingServiceDetailsNode->ServiceType;
            $service->ValidForSellingFlow = (int)((string)$ShippingServiceDetailsNode->ValidForSellingFlow == 'true');
            $service->Description = (string)$ShippingServiceDetailsNode->Description;
            $service->InternationalService = (int)((string)$ShippingServiceDetailsNode->InternationalService == 'true');
            $service->SurchargeApplicable = (int)((string)$ShippingServiceDetailsNode->SurchargeApplicable == 'true');
            $service->WeightRequired = (int)((string)$ShippingServiceDetailsNode->WeightRequired == 'true');
            $service->DimensionsRequired = (int)((string)$ShippingServiceDetailsNode->DimensionsRequired == 'true');
            $service->isDeprecated = (int)!empty($ShippingServiceDetailsNode->DeprecationDetails);
            $service->MappedToShippingServiceID = (string)$ShippingServiceDetailsNode->MappedToShippingServiceID;

            $EbayShippingServiceModels[] = $service;
        }
        return $EbayShippingServiceModels;
    }

    /**
     * @return EbayShippingLocationDetailsModel[]
     * @throws \Exception
     */
    public function get_shipping_location_details()
    {
        $api = new self($this->api_keys);
        $response = $api->execute();
        if ($response->error) {
            throw new \Exception("Error: {$response->error} | xml: {$response->xml}");
        }

        $ebayShippingLocationModels = [];

        foreach ($response->xml->ShippingLocationDetails as $ShippingLocationDetailsNode) {
            $location = new EbayShippingLocationDetailsModel();
            $location->Description = (string)$ShippingLocationDetailsNode->Description;
            $location->ShippingLocation = (string)$ShippingLocationDetailsNode->ShippingLocation;
            $ebayShippingLocationModels[] = $location;
        }
        return $ebayShippingLocationModels;
    }
}