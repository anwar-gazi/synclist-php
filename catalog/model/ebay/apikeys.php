<?php

/**
 * Class Modelebayapikeys
 * @property DBMySQLi $db
 */
class Modelebayapikeys extends Model
{
    function save(\resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel $apiKeysModel)
    {
        $account_name = $this->db->escape($apiKeysModel->account_name);
        $appID = $this->db->escape($apiKeysModel->appID);
        $certID = $this->db->escape($apiKeysModel->certID);
        $compatLevel = $this->db->escape($apiKeysModel->compatLevel);
        $devID = $this->db->escape($apiKeysModel->devID);
        $requestToken = $this->db->escape($apiKeysModel->requestToken);
        $serverUrl = $this->db->escape($apiKeysModel->serverUrl);
        $siteID = $this->db->escape($apiKeysModel->siteID);
        $this->db->query("insert into sl_ebay_api_keys set compatLevel=$compatLevel,siteID=$siteID, account_name='$account_name', appID='$appID', certID='$certID', devID='$devID', requestToken='$requestToken', serverUrl='$serverUrl'");
        return $this->db->getLastId();
    }

    function account_name_exists($account_name)
    {
        return $this->db->query("select account_name from sl_ebay_api_keys where account_name='$account_name'")->num_rows > 0;
    }

    /**
     * @return \resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel[]
     */
    function all()
    {
        return $this->db->get_object("SELECT * FROM sl_ebay_api_keys", \resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel::class)->rows;
    }

    /**
     * get production api keys of a user/account name
     * @param $account_name
     * @return \resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel
     */
    function get($account_name)
    {
        return $this->db->get_object("select * from sl_ebay_api_keys where account_name='$account_name'", \resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel::class)->row;
    }
}