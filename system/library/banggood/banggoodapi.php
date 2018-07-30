<?php

namespace Banggood;

class BanggoodAPI
{
    private $bg_live_api_url = 'https://api.banggood.com';

    private $appsecret;

    function __construct($appsecret)
    {
        $this->appsecret = $appsecret;
    }

    private function bg_api_root_url()
    {
        return $this->bg_live_api_url;
    }

    public function getCategoryList(Array $options)
    {
        $bg_root_url = $this->bg_api_root_url();
        $client = new \GuzzleHttp\Client();
        $resp = $client->get("{$bg_root_url}/category/getCategoryList", [
            'access_token' => $this->appsecret,
            'page' => $options['page'],
            'lang' => 'en'
        ]);
        return $resp;
    }

    public function getProductList(Array $options)
    {
        $client = new GuzzleHttp\Client();
        $bg_root_url = $this->bg_api_root_url();
        $resp = $client->get("$bg_root_url/product/getProductList", [
            'access_token' => $this->appsecret,
            'cat_id' => $options['cat_id'],
            'add_date_start' => $options['add_date_start'],
            'add_date_end' => $options['add_date_end'],
            'modify_date_start' => $options['modify_date_start'],
            'modify_date_end' => $options['modify_date_end'],
            'page' => $options['page'],
            'lang' => 'en',
        ]);
        return $resp;
    }
}