<?php

/**
 * Created by PhpStorm.
 * User: droid
 * Date: 8/10/17
 * Time: 9:25 PM
 */
class Modeletsyapikeys extends Model
{
    function save(\resgef\synclist\system\datatypes\etsyapikeysmodel\EtsyApiKeysModel $apiKeysModel)
    {
        $account_name = $this->db->escape($apiKeysModel->account_name);
        $app_name = $this->db->escape($apiKeysModel->app_name);
        $key_string = $this->db->escape($apiKeysModel->key_string);
        $shared_secret = $this->db->escape($apiKeysModel->shared_secret);
        $oauth_token = $this->db->escape($apiKeysModel->oauth_token);
        $oauth_token_secret = $this->db->escape($apiKeysModel->oauth_token_secret);
        $this->db->query("insert into sl_etsy_api_keys set account_name='$account_name', app_name='$app_name', key_string='$key_string', shared_secret='$shared_secret',oauth_token='$oauth_token', oauth_token_secret='$oauth_token_secret'");
        return $this->db->getLastId();
    }

    function account_name_exists($account_name)
    {
        return $this->db->query("select * from sl_etsy_api_keys WHERE account_name='$account_name'")->num_rows > 0;
    }

    /**
     * @return \resgef\synclist\system\datatypes\etsyapikeysmodel\EtsyApiKeysModel[]
     */
    function all()
    {
        return $this->db->get_object("SELECT * FROM sl_etsy_api_keys", \resgef\synclist\system\datatypes\etsyapikeysmodel\EtsyApiKeysModel::class)->rows;
    }
}