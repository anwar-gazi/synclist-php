<?php

class Modellobavapikeys extends Model
{
    function save(\resgef\synclist\system\datatypes\lobavapikeysmodel\LobAVApiKeysModel $lobAVApiKeysModel)
    {
        $account_name = $this->db->escape($lobAVApiKeysModel->account_name);
        $live = $this->db->escape($lobAVApiKeysModel->live);
        $test = $this->db->escape($lobAVApiKeysModel->test);
        $this->db->query("insert into sl_lob_api_keys set account_name='$account_name', test='$test', live='$live'");
        return $this->db->getLastId();
    }

    function account_name_exists($account_name)
    {
        return $this->db->query("select * from sl_lob_api_keys WHERE account_name='$account_name'")->num_rows > 0;
    }

    /**
     * @return \resgef\synclist\system\datatypes\lobavapikeysmodel\LobAVApiKeysModel or null
     */
    function get()
    {
        /** @var \resgef\synclist\system\datatypes\lobavapikeysmodel\LobAVApiKeysModel $keys */
        $keys = $this->db->get_object("SELECT * FROM sl_lob_api_keys", \resgef\synclist\system\datatypes\lobavapikeysmodel\LobAVApiKeysModel::class)->row;
        return $keys;
    }

    /**
     * @return \resgef\synclist\system\datatypes\lobavapikeysmodel\LobAVApiKeysModel[]
     */
    function get_all()
    {
        return $this->db->get_object("SELECT * FROM sl_lob_api_keys", \resgef\synclist\system\datatypes\lobavapikeysmodel\LobAVApiKeysModel::class)->rows;
    }
}