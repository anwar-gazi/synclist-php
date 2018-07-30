<?php

namespace resgef\synclist\system\datatypes\ebayapikeysmodel;


use resgef\synclist\system\datatypes\model\Model;
use resgef\synclist\system\exceptions\apikeynotfoundexception\ApiKeyNotFoundException;
use resgef\synclist\system\exceptions\notproperlyloaded\NotProperlyLoaded;
use resgef\synclist\system\library\ebayapi\completesale\CompleteSale;
use Resgef\SyncList\System\Library\EbayApi\EbayApiResponse\EbayApiResponse;
use resgef\synclist\system\library\ebayapi\getapiaccessrules\GetApiAccessRules;
use Resgef\SyncList\System\Library\EbayApi\GetTokenStatus\GetTokenStatus;

class EbayApiKeysModel extends Model
{
    public $id;

    public $account_name;
    public $appID;
    public $certID;
    public $compatLevel;
    public $devID;
    public $requestToken;
    public $serverUrl;
    public $siteID;

    /** @var \Registry $registry */
    private $registry;

    /**
     * load with api key account name
     * @param string $account_name the account_name field in api key table
     * @throws ApiKeyNotFoundException
     * @throws NotProperlyLoaded
     */
    function load($account_name)
    {
        if (!$this->registry) {
            throw new NotProperlyLoaded("inject registry");
        }
        $account_name = $this->registry->db->escape($account_name);
        /** @var EbayApiKeysModel $api_keys */
        $api_keys = $this->registry->db->get_object("select * from sl_ebay_api_keys where account_name='{$account_name}'", self::class)->row;
        if ($api_keys) {
            $this->id = $api_keys->id;
            $this->account_name = $api_keys->account_name;
            $this->appID = $api_keys->appID;
            $this->certID = $api_keys->certID;
            $this->compatLevel = $api_keys->compatLevel;
            $this->devID = $api_keys->devID;
            $this->requestToken = $api_keys->requestToken;
            $this->serverUrl = $api_keys->serverUrl;
            $this->siteID = $api_keys->siteID;
        } else {
            throw new ApiKeyNotFoundException("no ebay api key found for account name {$account_name}");
        }
    }

    /**
     * @return EbayApiResponse
     * @throws NotProperlyLoaded
     */
    public function GetApiAccessRules()
    {
        if (!$this->registry) {
            throw new NotProperlyLoaded("inject registry");
        }

        $api = new GetApiAccessRules($this);
        $request_xml = '<?xml version="1.0" encoding="utf-8"?>
                        <GetApiAccessRulesRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                        <RequesterCredentials>
                        <eBayAuthToken>' . $this->requestToken . '</eBayAuthToken>
                        </RequesterCredentials>
                        <ErrorLanguage>en_US</ErrorLanguage>
                        </GetApiAccessRulesRequest>';
        /** @var EbayApiResponse $response */
        $response = $api->execute($request_xml);
        return $response;
    }

    /**
     * validate from server
     * @return EbayApiResponse
     */
    function validate()
    {
        $call = new GetTokenStatus($this);
        $requestxml = '<?xml version="1.0" encoding="utf-8"?><GetTokenStatusRequest xmlns="urn:ebay:apis:eBLBaseComponents"><RequesterCredentials>
<eBayAuthToken>' . $this->requestToken . '</eBayAuthToken>
</RequesterCredentials><WarningLevel>High</WarningLevel><ErrorLanguage>en_US</ErrorLanguage></GetTokenStatusRequest>';
        /** @var EbayApiResponse $response */
        $response = $call->execute($requestxml);
        return $response;
    }

    function is_sandbox()
    {
        if (strpos($this->serverUrl, 'api.sandbox.ebay.com') !== false) { //found
            return true;
        } else {
            return false;
        }
    }

    function dependency_injection(\Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param $sales_rec
     * @param $tracking_code
     * @param $shipping_servc
     * @return array
     */
    function set_tracking_code($sales_rec, $tracking_code, $shipping_servc)
    {
        $db = $this->registry->db;
        $row = $db->query("select OrderID,ShippedTime from sl_orders where SalesRecordNumber='$sales_rec'")->row;
        $OrderID = @$row['OrderID'];
        if (!$OrderID) {
            $ret['success'] = false;
            $ret['error'] = "SalesRecordNumber $sales_rec didnt resolve an OrderID from local db";
            return $ret;
        }

        $completesale = new CompleteSale($this);

        /** @var EbayApiResponse $response */
        $response = $completesale->set_shipment_tracking($OrderID, $tracking_code, $shipping_servc);

        $success = $response->error ? false : true;

        $ret['success'] = $success;
        $ret['error'] = $response->error;
        $ret['data'] = $response->error ? "\nresponse\t" . htmlentities($response->xml->asXML()) : '';
        return $ret;
    }
}