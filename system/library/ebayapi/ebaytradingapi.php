<?php

namespace Resgef\SyncList\System\Library\EbayApi\EbayTradingApi;

use resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel;
use Resgef\SyncList\System\Helper\EbayHelpers\EbayHelpers;
use Resgef\SyncList\System\Library\EbayApi\EbayApiResponse\EbayApiResponse;

class EbayTradingApi
{
    /** @var EbayApiKeysModel $api_keys */
    protected $api_keys;

    protected $callname;

    function __construct(EbayApiKeysModel $api_keys, $callname)
    {
        $this->api_keys = $api_keys;
        $this->callname = $callname;
    }

    /**
     * execute a curl request
     * @param string $requestxml
     * @return EbayApiResponse
     */
    function execute($requestxml)
    {
        /** @var EbayApiResponse $response */
        $response = EbayHelpers::api_request($this->callname, $requestxml, $this->api_keys, function (\SimpleXMLElement $xml) {
            return $this->xml_has_error($xml);
        });
        return $response;
    }

    protected function xml_has_error(\SimpleXMLElement $xml)
    {
        $error = '';
        if (!empty($xml->Errors)) {
            $error = $xml->Errors->LongMessage.' | '.$xml->Errors->asXML();
        }
        return $error;
    }
}