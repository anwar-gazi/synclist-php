<?php

namespace resgef\synclist\system\library\ebayapi\getstore;

use Carbon\Carbon;
use resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel;
use resgef\synclist\system\exceptions\ebayresponseerrorexception\EbayResponseErrorException;
use Resgef\SyncList\System\Library\EbayApi\EbayTradingApi\EbayTradingApi;

class GetStore extends EbayTradingApi
{
    function __construct(EbayApiKeysModel $api_keys, $callname = 'GetStore')
    {
        parent::__construct($api_keys, $callname);
    }

    /**
     * @return string LastOpenedTime in ISO8601 format
     * @throws EbayResponseErrorException
     */
    function LastOpenedTime()
    {
        $request_xml = '<?xml version="1.0" encoding="utf-8"?>'
            . '<GetStoreRequest xmlns="urn:ebay:apis:eBLBaseComponents">'
            . '<RequesterCredentials><eBayAuthToken>'
            . $this->api_keys->requestToken
            . '</eBayAuthToken></RequesterCredentials>'
            . '<ErrorLanguage>en_US</ErrorLanguage>
            </GetStoreRequest>';
        $response = $this->execute($request_xml);
        if ($response->error) {
            throw new EbayResponseErrorException($response->xml->asXML());
        }

        return Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', (string)$response->xml->Store->LastOpenedTime)->toIso8601String();
    }
}