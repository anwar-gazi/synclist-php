<?php

namespace Resgef\SyncList\Lib\EbayApi\EbayBusinessPoliciesManagementApi;

use Resgef\SyncList\Lib\EbayApi\EbayApiMethod\EbayApiMethod;
use Resgef\SyncList\Lib\EbayApi\EbayApiResponse\EbayApiResponse;
use Resgef\SyncList\Models\EbayApiKeysModel\EbayApiKeysModel;

class EbayBusinessPoliciesManagementApi extends EbayApiMethod
{
    /** @var EbayApiKeysModel $api_keys */
    protected $api_keys;

    protected $requestxml;

    protected $callname;

    /**
     * execute a curl request
     * @return EbayApiResponse
     */
    function execute()
    {
        $response = parent::execute();
        if (!$response->error) {
            $response->error = $this->xml_has_error($response->xml);
        }
        return $response;
    }

    protected function xml_has_error(\SimpleXMLElement $xml)
    {
        $error = '';
        if (!empty($xml->errorMessage)) {
            $error = call_user_func(function () use ($xml) {
                $errors = [];
                foreach ($xml->errorMessage->error as $e) {
                    $errors[] = (string)$e->message;
                }
                return implode(';', $errors);
            });
        }
        return $error;
    }
}