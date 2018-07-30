<?php

namespace resgef\synclist\system\library\etsy\etsyapi;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Plugin\Oauth\OauthPlugin;
use resgef\synclist\system\datatypes\etsyapikeysmodel\EtsyApiKeysModel;

class EtsyApi
{
    protected $endpoint = 'https://openapi.etsy.com/v2/';

    /** @var EtsyApiKeysModel $api_keys */
    protected $api_keys;

    /** @var  $seller string */
    protected $shop_id;

    function __construct(EtsyApiKeysModel $apiKeysModel)
    {
        $this->api_keys = $apiKeysModel;
    }

    /**
     * @param array $parameters keys are from etsy api this method doc
     * @return \Guzzle\Http\Message\Response
     * @throws ClientErrorResponseException
     */
    public function findAllShopReceipts(Array $parameters)
    {

        $shop_id = $parameters['shop_id'];

        $parameters['shop_id'] = '';
        $parameters = array_filter($parameters);

        $params = $this->params_to_uri($parameters) ?
            $this->params_to_uri($parameters) . '&' : '?';

        $uri = "shops/$shop_id/receipts"
            . $params
            . 'includes=Listings,Transactions,Country';

        return $this->request($uri);
    }

    public function getShop()
    {

    }

    public function submitTracking(Array $parameters)
    {
        $receipt_id = $parameters['receipt_id'];

        $parameters['receipt_id'] = '';
        $parameters = array_filter($parameters);

        $uri = "shops/{$this->shop_id}/receipts/$receipt_id/tracking" . $this->params_to_uri($parameters);
        return $this->request($uri, 'post');
    }

    public function getShop_Receipt2(Array $parameters)
    {
        $receipt_id = $parameters['receipt_id'];

        $uri = "receipts/$receipt_id";

        return $this->request($uri);
    }

    /**
     * @param array $parameters check parameters from https://www.etsy.com/developers/documentation/reference/listinginventory#method_updateinventory
     * @return \Guzzle\Http\Message\Response
     */
    public function updateInventory(Array $parameters)
    {
        $uri = "listings/{$parameters['listing_id']}/inventory/";

        $keys = $this->api_keys;

        $client = new Client('https://openapi.etsy.com/v2/');

        $oauth = new OauthPlugin(array(
            'consumer_key' => $keys->key_string,
            'consumer_secret' => $keys->shared_secret,
            'token' => $keys->oauth_token,
            'token_secret' => $keys->oauth_token_secret
        ));
        $client->addSubscriber($oauth);
        /** @var \Guzzle\Http\Message\Response $response */
        $response = $client->put($uri, null, $parameters)->send();
        return $response;
    }

    /**
     * get permission scopes
     * @return \Guzzle\Http\Message\Response
     * @throws ClientErrorResponseException
     */
    public function get_scopes()
    {
        return $this->request('oauth/scopes');
    }

    private function params_to_uri(Array $parameters)
    {
        $params = [];
        foreach ($parameters as $key => $val) {
            $params[] = "$key=$val";
        }
        $uri = (!empty($params) ? '?' . implode('&', $params) : '');
        return $uri;
    }

    /**
     * @param $uri
     * @param string $http_method
     * @param array $parameters
     * @return \Guzzle\Http\Message\Response
     * @throws ClientErrorResponseException
     */
    private function request($uri, $http_method = 'get', $parameters = [])
    {
        $keys = $this->api_keys;

        $client = new Client('https://openapi.etsy.com/v2/');

        $oauth = new OauthPlugin(array(
            'consumer_key' => $keys->key_string,
            'consumer_secret' => $keys->shared_secret,
            'token' => $keys->oauth_token,
            'token_secret' => $keys->oauth_token_secret
        ));
        $client->addSubscriber($oauth);
        /** @var \Guzzle\Http\Message\Response $response */
        $response = $client->$http_method($uri)->send();
        return $response;
    }
}