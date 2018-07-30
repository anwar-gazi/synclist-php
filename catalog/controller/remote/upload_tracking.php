<?php

use resgef\synclist\system\datatypes\remoteprovider\RemoteProvider;

/**
 * Class ControllerRemoteUploadTracking
 * @property Modelebayapikeys $model_ebay_apikeys
 * @property Modeletsyapikeys $model_etsy_apikeys
 */
class ControllerRemoteUploadTracking extends Controller
{
    function index()
    {
        $this->load->model('ebay/apikeys');
        $this->load->model('etsy/apikeys');
        $all_ebay_api_keys = $this->model_ebay_apikeys->all();
        $all_etsy_api_keys = $this->model_etsy_apikeys->all();

        /** @var \resgef\synclist\system\datatypes\apikeysinfo\ApiKeysInfo[] $api_keys_info */
        $api_keys_info = call_user_func(function () use ($all_ebay_api_keys, $all_etsy_api_keys) {
            $info = [];
            foreach ($all_ebay_api_keys as $ebay_api_key) {
                $keyinfo = new \resgef\synclist\system\datatypes\apikeysinfo\ApiKeysInfo(RemoteProvider::ebay(), $ebay_api_key->id, $ebay_api_key->account_name);
                $info[] = $keyinfo->__toArray();
            }
            foreach ($all_etsy_api_keys as $etsy_api_key) {
                $keyinfo = new \resgef\synclist\system\datatypes\apikeysinfo\ApiKeysInfo(RemoteProvider::etsy(), $etsy_api_key->id, $etsy_api_key->account_name);
                $info[] = $keyinfo->__toArray();
            }
            return $info;
        });

        $data = [
            'api_keys_info' => $api_keys_info
        ];
        $this->response->setOutput($this->load->twig('remote/upload_tracking.twig', $data));
    }
}