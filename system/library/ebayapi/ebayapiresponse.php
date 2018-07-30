<?php

namespace Resgef\SyncList\System\Library\EbayApi\EbayApiResponse;

class EbayApiResponse
{
    /** @var integer $http_status_code */
    public $http_status_code;

    /** @var string $error */
    public $error;

    /** @var \SimpleXMLElement $xml or null(when response xml is empty) */
    public $xml;

    function __construct($xml, $error, $response_code)
    {
        $this->http_status_code = $response_code;
        $this->xml = $xml;
        $this->error = $error;
    }

    function is_empty()
    {
        return empty($this->xml);
    }
}
