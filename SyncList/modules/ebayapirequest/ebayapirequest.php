<?php

class EbayApiRequest extends SyncListModule {

    private $seller;
    private $api_keys;
    
    private $storage;

    function __construct($kernel, $config, $module_path, $seller, stdClass $seller_api_keys) {
        parent::__construct($kernel, $config, $module_path);
        $this->seller = $seller;
        $this->api_keys = $seller_api_keys;
        
        $this->config->path->save_dir = str_replace('%module_root%', $this->config->path->root, $this->config->path->save_dir);
    }

    public function request($api_name, $request_xml) {
        if (!$api_name) {
            print("request fail: api name absent\n");
            return null;
        }
        if (!$request_xml) {
            print("request fail: request xml not present\n");
            return null;
        }
        $this->load->library('ebaysession');
        $session = new eBaySession($this->api_keys->userToken, $this->api_keys->devID, $this->api_keys->appID, $this->api_keys->certID, $this->api_keys->serverUrl, $this->api_keys->compatabilityLevel, $this->api_keys->siteID, $api_name);
        
        $resp = $session->sendHttpRequest($request_xml);
        
        if ($this->config->save_response) {
            $this->save($resp);
        }
        
        return $resp;
    }
    
    function fake_request($api_name) {
        $files = $this->serve_by_api($api_name);
        return file_get_contents($files[0]);
    }
    
    function save($response) {
        if (empty($response)) {
            //trigger_error('cannot save xml: empty string');
            return false;
        }
        $default_root = $this->config->path->save_dir;

        $today = Carbon::now()->format("Y.m.d-H");

        $savedir = $default_root . '/' . $today . '/';

        if (!is_dir($savedir)) {
            mkdir($savedir, 0777, true); // create recursively nested if needed
        }
        
        $api = EbayXmlHelper::fetch_api($response);
        if (!$api) {
            trigger_error("response xml save failed, cannot parse api\n");
            return false;
        }
        
        $file = $api.'_'.time().'.xml';

        $fullpath = $savedir .'/'. $file;

        file_put_contents($fullpath, $response);
    }
    
    function serve_by_api($api_name) {
        $files = DirHelper::search_filename_recursive($this->config->path->save_dir, $api_name, 'xml');
        return $files;
    }

}
