<?php

namespace resgef\synclist\system\datatypes\etsyapikeysmodel;

use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Message\Response;
use resgef\synclist\system\datatypes\model\Model;
use resgef\synclist\system\library\etsy\etsyapi\EtsyApi;
use resgef\synclist\system\library\etsy\etsyapiresponsehttpstatuscode\EtsyApiResponseHttpStatusCode;

class EtsyApiKeysModel extends Model
{
    public $id;

    public $account_name;
    public $app_name;
    public $key_string;
    public $shared_secret;
    public $oauth_token;
    public $oauth_token_secret;

    /** @var \Registry $registry */
    private $registry;

    /**
     * @return array
     */
    function validate()
    {
        $result = [
            'invalid_keys' => true,
            'error' => ''
        ];
        # now validate the keys and check permission scopes
        $etsy = new EtsyApi($this);
        try {
            /** @var \Guzzle\Http\Message\Response $response */
            $response = $etsy->get_scopes();
            $responseCode = new EtsyApiResponseHttpStatusCode($response->getStatusCode());
            if ($responseCode->isOk()) { //api keys ok
                $result['invalid_keys'] = false;
            } elseif ($responseCode->isUnauthorized()) {
                $result['invalid_keys'] = true;
            } else {
                $result['invalid_keys'] = true;
                $result['error'] = $responseCode->getMeaning();
            }
        } catch (ClientErrorResponseException $exception) {
            $result['invalid_keys'] = true;
            $result['error'] = $exception->getMessage();
        }
        return $result;
    }

    /**
     * @return array or null
     */
    function scopes()
    {
        $etsy = new EtsyApi($this);
        try {
            /** @var \Guzzle\Http\Message\Response $response */
            $response = $etsy->get_scopes();
            $responseCode = new EtsyApiResponseHttpStatusCode($response->getStatusCode());
            if ($responseCode->isOk()) {
                $json = $response->json();
                return $json['results'];
            } else {
                return null;
            }
        } catch (ClientErrorResponseException $exception) {
            return null;
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
        $receipt_id = $sales_rec;

        $api = new EtsyApi($this);

        try {
            /** @var Response $response */
            $response = $api->submitTracking([
                'receipt_id' => $receipt_id,
                'tracking_code' => $tracking_code,
                'carrier_name' => $shipping_servc
            ]);

            $resp_json = $response->getBody();
            $resp_data = json_decode($resp_json);

            $ret['success'] = true;
            $ret['msg'] = $ret['success'] ? sprintf("update success: receipt {$resp_data->receipt_id} tracking_code {$resp_data->shipments->tracking_code} tracking_url {$resp_data->shipments->tracking_url}") : '';
            $ret['error'] = '';
            $ret['data'] = $resp_data;
            return $ret;
        } catch (ClientErrorResponseException $E) {
            $error = $E->getMessage();
            return [
                'success' => false,
                'error' => $error,
                'data' => null
            ];
        }
    }

    function get_shop_id()
    {

    }
}