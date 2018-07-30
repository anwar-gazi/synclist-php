<?php

namespace Banggood;

class SimulateBanggoodAPI
{
    function __construct()
    {

    }

    public function getCategoryList(Array $options)
    {
        $respone = file_get_contents(__DIR__ . '/demo_response/getcategorylist_success.json');
        $resp = new \GuzzleHttp\Psr7\Response(200, [], $respone);
        return json_decode($resp->getBody());
    }

    public function getProductList(Array $options)
    {
        $respone = file_get_contents(__DIR__ . '/demo_response/getproductlist_success.json');
        $resp = new \GuzzleHttp\Psr7\Response(200, [], $respone);
        return json_decode($resp->getBody());
    }

    public function GetProductInfo(Array $options)
    {
        $respone = file_get_contents(__DIR__ . '/demo_response/getproductinfo_success.json');
        $resp = new \GuzzleHttp\Psr7\Response(200, [], $respone);
        return json_decode($resp->getBody());
    }

    public function GetProductStock(Array $options)
    {
        $respone = file_get_contents(__DIR__ . '/demo_response/getproductstock_success.json');
        $resp = new \GuzzleHttp\Psr7\Response(200, [], $respone);
        return json_decode($resp->getBody());
    }
}