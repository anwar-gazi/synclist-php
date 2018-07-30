<?php

namespace resgef\synclist\system\datatypes\apikeysinfo;

use resgef\synclist\system\datatypes\remoteprovider\RemoteProvider;
use resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel;
use resgef\synclist\system\datatypes\etsyapikeysmodel\EtsyApiKeysModel;

class ApiKeysInfo
{
    public $id;# represents api_keys.id

    /** @var RemoteProvider $provider */
    public $provider;

    public $account_name; # represents api_keys.account_name

    private $registry;

    function __construct(RemoteProvider $provider, $id, $account_name)
    {
        $this->provider = $provider;
        $this->id = $id;
        $this->account_name = $account_name;
    }

    function dependency_injection(\Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @return object
     */
    function api_key()
    {
        if ($this->provider == 'ebay') {
            return $this->registry->db->get_object("select * from sl_ebay_api_keys where account_name='{$this->account_name}'", EbayApiKeysModel::class)->row;
        } elseif ($this->provider == 'etsy') {
            return $this->registry->db->get_object("select * from sl_etsy_api_keys where account_name='{$this->account_name}'", EtsyApiKeysModel::class)->row;
        }
    }

    /**
     * @return array
     */
    public function __toArray()
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'account_name' => $this->account_name
        ];
    }
}