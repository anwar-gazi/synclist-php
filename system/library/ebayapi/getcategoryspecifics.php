<?php

namespace Resgef\SyncList\Lib\EbayApi\GetCategorySpecifics;

use Resgef\SyncList\Exceptions\EbayApiCallSpecificException\EbayApiCallSpecificException;
use Resgef\SyncList\Lib\EbayApi\EbayApiResponse\EbayApiResponse;
use Resgef\SyncList\Lib\EbayApi\EbayTradingApi\EbayTradingApi;
use \Resgef\SyncList\Helpers\EbayHelpers\EbayHelpers;
use Resgef\SyncList\Models\EbayApiKeysModel\EbayApiKeysModel;

class GetCategorySpecifics extends EbayTradingApi
{
    function __construct($api_keys, $requestxml)
    {
        parent::__construct($api_keys, 'GetCategorySpecifics', $requestxml);
    }

    /**
     * TODO: this is incomplete, returned fileAttachment->Data is empty
     * get all available specifics form a specific ebay site
     * @param EbayApiKeysModel $api_keys
     * @throws EbayApiCallSpecificException
     */
    static function get_all(EbayApiKeysModel $api_keys)
    {
        $requestxml =
            '<?xml version="1.0" encoding="utf-8"?>
            <GetCategorySpecificsRequest xmlns="urn:ebay:apis:eBLBaseComponents">
              <CategorySpecificsFileInfo>True</CategorySpecificsFileInfo>
              <RequesterCredentials>
                <eBayAuthToken>' . $api_keys->requestToken . '</eBayAuthToken>
                </RequesterCredentials>
                <ErrorLanguage>en_US</ErrorLanguage>
            </GetCategorySpecificsRequest>';
        $call = new self($api_keys, $requestxml);
        $response_f = $call->execute();

        if ($response_f->error) {
            throw new EbayApiCallSpecificException('GetCategorySpecifics', $response_f->error);
        }

        $requestXml_df = '<?xml version="1.0" encoding="utf-8"?>
                            <downloadFileRequest xmlns="http://www.ebay.com/marketplace/services">
                              <fileReferenceId>' . (string)$response_f->xml->FileReferenceID . '</fileReferenceId>
                              <taskReferenceId>' . (string)$response_f->xml->TaskReferenceID . '</taskReferenceId>
                            </downloadFileRequest>';

        /** @var EbayApiResponse $response */
        $response = EbayHelpers::api_request('downloadFile', $requestXml_df, $api_keys);

        $xml_filename = 'downloadfile_' . time() . '.xml';
        $file = tempnam('tmp', 'zip');
        $zip = new \ZipArchive();
        $zip->open($file, \ZipArchive::OVERWRITE);
        $zip->addFromString($xml_filename, base64_decode((string)$response->xml->fileAttachment->Data));
        $zip->extractTo('/tmp/');
        $zip->close();
        $GetCatResp_file = '/tmp/' . $xml_filename;
    }
}