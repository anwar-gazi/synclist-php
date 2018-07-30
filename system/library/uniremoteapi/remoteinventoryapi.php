<?php

namespace resgef\synclist\system\library\uniremoteapi\remoteinventoryapi;

use resgef\synclist\system\datatypes\listingrow\ListingRow;
use resgef\synclist\system\datatypes\model\Model;
use resgef\synclist\system\datatypes\remoteprovider\RemoteProvider;
use resgef\synclist\system\library\uniremoteapi\ebayfunctions\ebayFunctions;
use resgef\synclist\system\library\uniremoteapi\etsyfunctions\etsyfunctions;
use resgef\synclist\system\library\uniremoteapi\remotefunctionsinterface\RemoteFunctionsInterface;

class RemoteInventoryApi implements RemoteFunctionsInterface
{
    /** @var RemoteProvider $remote_provider */
    private $remote_provider;
    /** @var $api_keys */
    private $api_keys;
    /** @var \Registry $registry */
    private $registry;

    /** @var RemoteFunctionsInterface $remote */
    private $remote;

    function __construct(RemoteProvider $remote_provider, $api_keys, \Registry $registry)
    {
        $this->remote_provider = $remote_provider;
        $this->api_keys = $api_keys;
        $this->registry = $registry;

        if ($remote_provider->name == 'ebay') {
            $this->remote = new ebayFunctions($api_keys, $registry);
        } elseif ($remote_provider->name == 'etsy') {
            $this->remote = new etsyfunctions($api_keys, $registry);
        }
    }

    /**
     * @param ListingRow $ListingRow
     * @return \resgef\synclist\system\library\uniremoteapi\remoteresponse\RemoteResponse
     */
    function update_quantity(ListingRow $ListingRow)
    {
        return $this->remote->update_quantity($ListingRow);
    }
}