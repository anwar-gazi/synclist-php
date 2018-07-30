<?php

namespace resgef\synclist\system\library\uniremoteapi\etsyfunctions;

use resgef\synclist\system\datatypes\etsyapikeysmodel\EtsyApiKeysModel;
use resgef\synclist\system\datatypes\listingrow\ListingRow;
use resgef\synclist\system\library\uniremoteapi\remotefunctionsinterface\RemoteFunctionsInterface;

class etsyfunctions implements RemoteFunctionsInterface
{
    private $api_keys;
    private $registry;

    function __construct(EtsyApiKeysModel $api_keys, \Registry $registry)
    {
        $this->api_keys = $api_keys;
        $this->registry = $registry;
    }

    function update_quantity(ListingRow $listingRow, $new_quantity)
    {
        // TODO: Implement update_quantity() method.
    }
}