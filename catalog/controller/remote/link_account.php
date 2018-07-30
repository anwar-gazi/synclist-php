<?php

/**
 * Created by PhpStorm.
 * User: droid
 * Date: 8/9/17
 * Time: 12:07 PM
 */

/**
 * Class ControllerRemoteLinkaccount
 * @property Modelebayapikeys $model_ebay_apikeys
 * @property Modeletsyapikeys $model_etsy_apikeys
 * @property Modellobavapikeys $model_lobav_apikeys
 */
class ControllerRemoteLinkaccount extends Controller
{
    function ebay()
    {
        $this->load->model('ebay/apikeys');
        $data = [
            'all_ebay_api_keys' => $this->model_ebay_apikeys->all()
        ];
        if (!empty($this->request->post)) {
            $data['form'] = $this->request->post;
            $post = $this->request->post;
            $api_keys = new \resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel();
            $api_keys->account_name = $post['account_name'];
            $api_keys->appID = $post['appID'];
            $api_keys->siteID = $post['siteID'];
            $api_keys->devID = $post['devID'];
            $api_keys->certID = $post['certID'];
            $api_keys->requestToken = $post['requestToken'];
            $api_keys->compatLevel = $post['compatLevel'];
            $api_keys->serverUrl = $post['serverUrl'];
            $validation = $api_keys->validate();
            if ($validation->error) {
                $data['error'] = "Error: " . $validation->error;
            } else {
                if ($this->model_ebay_apikeys->account_name_exists($api_keys->account_name)) {
                    $data['error'] = 'Error: account name exists!';
                } else {
                    $this->model_ebay_apikeys->save($api_keys);
                    $data['msg'] = 'success!';
                }
            }
        }
        $this->response->setOutput($this->load->twig('remote/link_ebay_account.twig', $data));
    }

    function etsy()
    {
        $this->load->model('etsy/apikeys');
        $data = [
            'all_etsy_api_keys' => $this->model_etsy_apikeys->all(),
            'form' => [
                'key_string' => $this->session->get_and_unset('key_string', null),
                'shared_secret' => $this->session->get_and_unset('shared_secret', null),
                'oauth_token' => $this->session->get_and_unset('oauth_token', null),
                'oauth_token_secret' => $this->session->get_and_unset('oauth_token_secret', null),
                'msg' => $this->session->get_and_unset('msg', null)
            ],
            'error' => '',
            'msg' => ''
        ];
        if (!empty($this->request->post)) {
            $data['form'] = $this->request->post;
            $api_keys = new \resgef\synclist\system\datatypes\etsyapikeysmodel\EtsyApiKeysModel();
            $api_keys->account_name = $this->request->post['account_name'];
            $api_keys->app_name = $this->request->post['app_name'];
            $api_keys->key_string = $this->request->post['key_string'];
            $api_keys->shared_secret = $this->request->post['shared_secret'];
            $api_keys->oauth_token = $this->request->post['oauth_token'];
            $api_keys->oauth_token_secret = $this->request->post['oauth_token_secret'];

            # now validation: account_name uniqueness, api keys validity, api keys permission scopes
            if ($this->model_etsy_apikeys->account_name_exists($api_keys->account_name)) {
                $data['error'] = 'Error: duplicate account name';
            } else {
                $validation = $api_keys->validate();
                if (!$validation['invalid_keys']) { //valid keys
                    /** @var array $scopes */
                    $scopes = $api_keys->scopes();
                    $required_scopes = $this->config->get('etsy_api')['permission_scopes'];
                    $scopes_not_met = array_diff($required_scopes, $scopes);
                    if (empty($scopes_not_met)) { //all required scopes met
                        $this->model_etsy_apikeys->save($api_keys);
                        $data['msg'] = 'success';
                    } else {
                        $data['error'] = 'Error: insufficient permission scopes! required permisions are ' . implode(',', $required_scopes) . ' but the api keys have ' . implode(',', $scopes);
                    }
                } else { //invalid keys
                    $data['error'] = $validation['error'];
                }
            }
        }

        $this->response->setOutput($this->load->twig('remote/link_etsy_account.twig', $data));
    }

    function lob_address_verification()
    {
        $this->load->model('lobav/apikeys');
        $data = [
            'error' => '',
            'msg' => '',
            'form' => [],
            'keys' => $this->model_lobav_apikeys->get_all()
        ];
        if (!empty($this->request->post)) {
            $data['form'] = $this->request->post;
            $api_keys = new \resgef\synclist\system\datatypes\lobavapikeysmodel\LobAVApiKeysModel();
            $api_keys->account_name = $this->request->post['account_name'];
            $api_keys->test = $this->request->post['test'];
            $api_keys->live = $this->request->post['live'];
            if ($this->model_lobav_apikeys->account_name_exists($api_keys->account_name)) {
                $data['error'] = 'Error: account name exists';
            } else {
                $this->model_lobav_apikeys->save($api_keys);
                $data['msg'] = 'success';
            }
        }
        $this->response->setOutput($this->load->twig('remote/link_lob_address_verification_account.twig', $data));
    }
}