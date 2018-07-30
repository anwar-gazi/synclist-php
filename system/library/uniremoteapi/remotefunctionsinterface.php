<?php

namespace resgef\synclist\system\library\uniremoteapi\remotefunctionsinterface;

use resgef\synclist\system\datatypes\listingrow\ListingRow;
use resgef\synclist\system\library\uniremoteapi\remoteresponse\RemoteResponse;

Interface RemoteFunctionsInterface
{
    /**
     * @param ListingRow $listingRow
     * @return RemoteResponse
     */
    function update_quantity(ListingRow $listingRow, $new_quantity);
}