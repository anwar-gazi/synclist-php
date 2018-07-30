<?php

use Guzzle\Http\Client;
use Guzzle\Plugin\Oauth\OauthPlugin;

trait EtsyApiMethods
{
    protected $endpoint = 'https://openapi.etsy.com/v2/';

    /** @var  $api_keys stdClass */
    protected $api_keys;

    /** @var  $seller string */
    protected $shop_id;

    /**
     * @param array $parameters keys are from etsy api this method doc
     * @return EtsyApiResponse
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
     * get permission scopes
     * @return EtsyApiResponse
     */
    public function scopes()
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

    private function request($uri, $http_method = 'get')
    {
        $keys = $this->api_keys;

        $client = new Client('https://openapi.etsy.com/v2/');

        $oauth = new OauthPlugin(array(
            'consumer_key' => $keys->keystring,
            'consumer_secret' => $keys->shared_secret,
            'token' => $keys->oauth_token,
            'token_secret' => $keys->oauth_token_secret
        ));
        $client->addSubscriber($oauth);

        $GLOBALS['resp'] = '';
        try {

            $GLOBALS['resp'] = $client->$http_method($uri)->send();

        } catch (Guzzle\Http\Exception\ClientErrorResponseException $E) {

            $error = $E->getMessage();
            $response = new EtsyApiResponse($error, '');
            return $response;

        }

        $resp_json = $GLOBALS['resp']->getBody();
        $response = new EtsyApiResponse('', $resp_json);
        return $response;
    }
}