<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '/var/www/html/sl/SyncList/init.php';

$url = 'http://localhost/sl/SyncList/modules/etsyapiclient/etsy_get_creds.php';

$get_access_url = "$url?page=get_access";
$get_verifier_url = "$url?page=get_verifier";

$flashdata_file = __DIR__ . '/' . 'flashdata.tmp';
if (!file_exists($flashdata_file) || !fopen($flashdata_file, 'a')) {
    print("create $flashdata_file with write permissions for web browser\n");
    return;
}

# determine the page
$get_verifier_page = false;
$get_access_page = false;
if (@$_REQUEST['page'] == 'get_verifier') {
    $get_verifier_page = true;
    $get_access_page = false;
} else {
    $get_access_page = true;
    $get_verifier_page = false;
}

$scopes = [
    'email_r',
    'transactions_r',
    'transactions_w',
    'listings_r',
    'listings_w',
];

class Session
{
    private $data = [];

    function __construct(Array $session)
    {
        $this->data = $session;
    }

    public function flash($val = null)
    {
        global $flashdata_file;

        if ($val) {
            file_put_contents($flashdata_file, $val);
        }

        if ($val === null) {
            return file_get_contents($flashdata_file);
        }
    }
}

class Request
{
    private $request = [];

    function __construct(Array $request)
    {
        $this->request = $request;
    }

    public function get($key)
    {
        return $this->request[$key];
    }
}

/**
 * to get oauth access tokens
 * just call the get_access route
 */
class ControllerEtsyOauth
{

    private $synclist_api;
    private $request;
    private $session;

    function __construct(SyncListApi $api, Request $request, Session $session)
    {
        $this->synclist_api = $api;
        $this->request = $request;
        $this->session = $session;
    }

    /**
     * this requests for temporary token, saves the returned token secret and redirects to login url
     * for the user to approve access for the app
     * upon approval, url to $this->get_verifier() is called
     */
    function get_access()
    {
        global $get_verifier_url, $scopes;

        /** @var SyncListApi $api */
        $api = $this->synclist_api;
        $keys = $api->api_keys('etsy', 'hanksminerals');
        $oauth = new OAuth($keys->keystring, $keys->shared_secret);

        //$oauth->setCAPath('/home/droid/cacert.pem');
        $oauth->disableSSLChecks();

        $req_token = $oauth->getRequestToken("https://openapi.etsy.com/v2/oauth/request_token?scope=" . implode('%20', $scopes), $get_verifier_url);

        $this->session->flash($req_token['oauth_token_secret']);

        if ($req_token['login_url']) {
            header("Location: {$req_token['login_url']}");
        } else {
            trigger_error("failed");
        }
    }

    /**
     * this url is called after step one, upon approval of app access
     */
    function get_verifier()
    {

        $oauth_verifier = $this->request->get('oauth_verifier');
        $request_token = $this->request->get('oauth_token');

        /** @var SyncListApi $api */
        $api = $this->synclist_api;
        $keys = $api->api_keys('etsy', 'hanksminerals');
        $oauth = new OAuth($keys->keystring, $keys->shared_secret);
        $oauth->setToken($request_token, $this->session->flash());

        $oauth_token = '';
        $oauth_token_secret = '';
        try {
            //$oauth->setCAPath('/home/droid/cacert.pem');
            $oauth->disableSSLChecks();

            // set the verifier and request Etsy's token credentials url
            $acc_token = $oauth->getAccessToken("https://openapi.etsy.com/v2/oauth/access_token", null, $oauth_verifier);
            $oauth_token = $acc_token['oauth_token'];
            $oauth_token_secret = $acc_token['oauth_token_secret'];
        } catch (OAuthException $e) {
            print($e->getMessage() . "\n");
        }

        $new_keys = [
            'keystring' => $keys->keystring,
            'shared_secret' => $keys->shared_secret,
            'oauth_token' => $oauth_token,
            'oauth_token_secret' => $oauth_token_secret
        ];

        $keys_desc = [];
        foreach ($new_keys as $key => $val) {
            $keys_desc[] = "$key=$val";
        }

        print("keystring and shared secret remain unchanged. oauth_token and oauth_token_secret are new\n");
        print("\n" . implode("\n", $keys_desc) . "\n");

    }

}

/** @var SyncListApi $api */
$api = new_synclist_kernel()->load->module('app.api');
$request = new Request($_REQUEST);
$session = new Session($_SESSION);

$Auth = new ControllerEtsyOauth($api, $request, $session);

if ($get_access_page) {
    $Auth->get_access();
}
if ($get_verifier_page) {
    $Auth->get_verifier();
}