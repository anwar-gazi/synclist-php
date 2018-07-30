<?php

namespace Resgef\SyncList\Lib\EbayApi\EbayApiMethod;

use Resgef\SyncList\Lib\EbayApi\EbayApiResponse\EbayApiResponse;
use Resgef\SyncList\Helpers\EbayHelpers\EbayHelpers;
use Resgef\SyncList\Models\EbayApiKeysModel\EbayApiKeysModel;

class EbayApiMethod
{
    /** @var EbayApiKeysModel $api_keys */
    protected $api_keys;

    protected $requestxml;

    protected $callname;

    /**
     * EbayTradingApi constructor.
     * @param EbayApiKeysModel $api_keys
     * @param string $callname
     * @param string $requestxml
     */
    function __construct(EbayApiKeysModel $api_keys, $callname, $requestxml)
    {
        $this->api_keys = $api_keys;
        $this->callname = $callname;
        $this->requestxml = $requestxml;
    }

    /**
     * execute a curl request
     * @return EbayApiResponse
     */
    function execute()
    {
        $response = EbayHelpers::api_request($this->callname, $this->requestxml, $this->api_keys, function (\SimpleXMLElement $xml) {
            return $this->is_error_response($xml);
        });
        return $response;
    }

    /**
     * this is a second phase validation to regardless of Errors node defined in api specification to be returned or not
     * If you provide a undefined call, the response will contain the Errors node,
     * @param \SimpleXMLElement $xml
     * @return string
     */
    protected function is_error_response(\SimpleXMLElement $xml)
    {
        $error = '';
        if ($xml->getName() == 'eBay') { //request error
            $error = $xml->Errors->asXML();
            $error .= PHP_EOL . "requestxml: " . $this->requestxml;
        }
        return $error;
    }
}