<?php

namespace Resgef\SyncList\System\Helper\EbayHelpers;

use resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel;
use Resgef\SyncList\System\Library\EbayApi\EbayApiResponse\EbayApiResponse;
use Resgef\SyncList\System\Library\EbayApi\Ebaysession\eBaySession;

class EbayHelpers
{
    /**
     * @param $callname
     * @param $reqeustxml
     * @param EbayApiKeysModel $api_keys
     * @param callable|null $advanced_validator a closure with parameter of the xml as simplexmlelement object
     * and returns any advanced errors as you check
     * by default we do only the first phase of response validation: whether it is empty or not
     * @return EbayApiResponse
     */
    static function api_request($callname, $reqeustxml, EbayApiKeysModel $api_keys, callable $advanced_validator = null)
    {
        $ebay = new eBaySession();
        $ebay->set_appID($api_keys->appID);
        $ebay->set_certID($api_keys->certID);
        $ebay->set_compatLevel($api_keys->compatLevel);
        $ebay->set_devID($api_keys->devID);
        $ebay->set_requestToken($api_keys->requestToken);
        $ebay->set_serverUrl($api_keys->serverUrl);
        $ebay->set_siteID($api_keys->siteID);
        $ebay->set_verb($callname);

        $resp = $ebay->sendHttpRequest($reqeustxml);

        $http_status_code = $resp['transfer_info']['http_code'];

        $xml = $resp['response'];

        // very basic validation
        if (stristr($xml, 'HTTP 404')) {
            $xmlobj = null;
            $error = 'empty response';
        } elseif (!$xml) {
            $xmlobj = null;
            $error = 'empty response';
        } else {
            $xmlobj = simplexml_load_string($xml);
            $error = $advanced_validator ? $advanced_validator($xmlobj) : '';
        }

        $response = new EbayApiResponse($xmlobj, $error, $http_status_code);
        return $response;
    }
}