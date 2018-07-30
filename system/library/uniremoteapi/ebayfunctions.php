<?php

namespace resgef\synclist\system\library\uniremoteapi\ebayfunctions;

use resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel;
use resgef\synclist\system\datatypes\listingrow\ListingRow;
use Resgef\SyncList\System\Library\EbayApi\EbayApiResponse\EbayApiResponse;
use resgef\synclist\system\library\ebayapi\reviseinventorystatus\ReviseInventoryStatus;
use resgef\synclist\system\library\uniremoteapi\remotefunctionsinterface\RemoteFunctionsInterface;
use resgef\synclist\system\library\uniremoteapi\remoteresponse\RemoteResponse;

class ebayFunctions implements RemoteFunctionsInterface
{
    /** @var EbayApiKeysModel $api_keys */
    private $api_keys;
    /** @var \Registry $registry */
    private $registry;

    function __construct(EbayApiKeysModel $ebayApiKeys, \Registry $registry)
    {
        $this->api_keys = $ebayApiKeys;
        $this->registry = $registry;
    }

    /**
     * @param ListingRow $listingRow
     * @param integer $new_quantity
     * @return RemoteResponse
     */
    function update_quantity(ListingRow $listingRow, $new_quantity)
    {
        $api_keys = $this->api_keys;
        $api = new ReviseInventoryStatus($api_keys);
        $requestxml = '<?xml version="1.0" encoding="utf-8"?>
                                    <ReviseInventoryStatusRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                                    <RequesterCredentials>
                                    <eBayAuthToken>' . $api_keys->requestToken . '</eBayAuthToken>
                                    </RequesterCredentials>
                                    <InventoryStatus>
                                        <ItemID>' . $listingRow->itemid . '</ItemID>
                                        <SKU>' . $listingRow->sku . '</SKU>
                                        <Quantity>' . $listingRow->quantity . '</Quantity>
                                    </InventoryStatus>
                                    <MessageID>1</MessageID>
                                    <WarningLevel>High</WarningLevel>
                                    <Version>' . $api_keys->compatLevel . '</Version>
                                    </ReviseInventoryStatusRequest>​';
        /** @var EbayApiResponse $response */
        $response = $api->execute($requestxml);
        $resp = new RemoteResponse($response->http_status_code, $response->error, empty($response->error), $response->xml);
        return $resp;
    }
}