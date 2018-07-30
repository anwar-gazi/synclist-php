<?php

/**
 * to get oauth access tokens
 * just call the get_access route
 */
class ControllerEtsyOauth extends Controller
{

    /**
     * @throws Exception
     */
    function index()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $data = [];
        if (!empty($this->request->post)) {
            $keystring = $this->request->post['key_string'];
            $shared_secret = $this->request->post['shared_secret'];

            if (!$keystring || !$shared_secret) {
                $data['error'] = "key string and shared secret both required";
            } else {
                $this->session->key_string = $keystring;
                $this->session->shared_secret = $shared_secret;

                $oauth = new OAuth($keystring, $shared_secret);
                $oauth->disableSSLChecks();
                /** @var array $req_token */
                try {
                    $req_token = $oauth->getRequestToken("https://openapi.etsy.com/v2/oauth/request_token?scope=email_r%20transactions_r%20transactions_w%20listings_r%20listings_w%20billing_r%20profile_r%20feedback_r", $this->url->link('etsy/oauth/get_verifier'));
                } catch (Exception $exception) {
                    print($exception->getMessage());
                    throw $exception;
                }

                $this->session->oauth_token_secret = $req_token['oauth_token_secret'];

                if ($req_token['login_url']) {
                    header("Location: {$req_token['login_url']}");
                } else {
                    $data['error'] = 'failed';
                }
            }
        }

        $this->response->setOutput($this->load->twig('etsy/oauth.twig', $data));
    }

    /**
     * this requests for temporary token, saves the returned token secret and redirects to login url
     * for the user to approve access for the app
     * upon approval, url to $this->get_verifier() is called
     */
//    function get_access()
//    {
//        error_reporting(E_ALL);
//        ini_set('display_errors', 1);
//
//        /** @var SyncListApi $api */
//        $api = $this->synclist_api;
//        $keys = $api->api_keys('etsy', 'hanksminerals');
//        $oauth = new OAuth($keys->keystring, $keys->shared_secret);
//
//        //$oauth->setCAPath('/home/droid/cacert.pem');
//        $oauth->disableSSLChecks();
//
//        $req_token = $oauth->getRequestToken("https://openapi.etsy.com/v2/oauth/request_token?scope=email_r%20transactions_r%20transactions_w%20listings_r%20listings_w", $this->url->link('etsy/oauth/get_verifier'));
//        $this->session->flash($req_token['oauth_token_secret']);
//
//        if ($req_token['login_url']) {
//            header("Location: {$req_token['login_url']}");
//        } else {
//            trigger_error("failed");
//        }
//    }

    /**
     * this url is called after step one, upon approval of app access
     */
    function get_verifier()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $oauth_verifier = $this->request->get['oauth_verifier'];
        $request_token = $this->request->get['oauth_token'];

//        $key_string = $this->session->key_string;
//        $shared_secret = $this->session->shared_secret;

        $oauth = new OAuth($this->session->key_string, $this->session->shared_secret);
        $oauth->setToken($request_token, $this->session->oauth_token_secret);

//        $oauth_token = '';
//        $oauth_token_secret = '';
        try {
            //$oauth->setCAPath('/home/droid/cacert.pem');
            $oauth->disableSSLChecks();
            // set the verifier and request Etsy's token credentials url
            $acc_token = $oauth->getAccessToken("https://openapi.etsy.com/v2/oauth/access_token", null, $oauth_verifier);
            $this->session->oauth_token = $acc_token['oauth_token'];
            $this->session->oauth_token_secret = $acc_token['oauth_token_secret'];
            $this->session->msg = "Please save these api keys";
            $this->response->redirect($this->url->link('remote/link_account/etsy'));
        } catch (OAuthException $e) {
            print($e->getMessage() . "\n");
        }
    }
}
