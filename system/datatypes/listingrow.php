<?php

namespace resgef\synclist\system\datatypes\listingrow;

use resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel;
use resgef\synclist\system\datatypes\etsyapikeysmodel\EtsyApiKeysModel;
use resgef\synclist\system\datatypes\localinventoryitem\LocalInventoryItem;
use resgef\synclist\system\datatypes\model\Model;
use resgef\synclist\system\exceptions\apikeynotfoundexception\ApiKeyNotFoundException;
use resgef\synclist\system\exceptions\stocksyncnotrequiredexception\StockSyncNotRequiredException;
use resgef\synclist\system\exceptions\unmetdependency\UnmetDependency;
use Resgef\SyncList\System\Library\EbayApi\EbayApiResponse\EbayApiResponse;
use resgef\synclist\system\library\ebayapi\reviseinventorystatus\ReviseInventoryStatus;
use resgef\synclist\system\library\etsy\etsyapi\EtsyApi;
use resgef\synclist\system\library\uniremoteapi\remoteresponse\RemoteResponse;

/**
 * Class ListingRow
 * @package resgef\synclist\system\datatypes\listingrow
 * @property int multiply throws Exception if the dependency(registry) not set
 * @property string title_short a short form of title
 * @property integer available
 * @property integer balance
 */
class ListingRow extends Model
{
    public $uniqid;
    public $salesdata_save_time;
    public $itemid;
    public $has_variations;
    public $option;
    public $sku;
    public $seller;
    public $title;
    public $pic_url;
    public $item_url;
    public $quantity;
    public $sold;
    /** @var string $listing_provider */
    public $listing_provider;
    public $active;
    public $account_name;
    public $synced;

    private $_multiply;

    /** @var \Registry $registry */
    private $registry;

    function dependency_injection(\Registry $registry)
    {
        $this->registry = $registry;
    }

    //TODO
    function reload()
    {

    }

    /**
     * @param \Registry $registry
     * @return EbayApiKeysModel|EtsyApiKeysModel
     */
    public function api_keys(\Registry $registry = null)
    {
        if ($this->listing_provider == 'ebay') {
            return $registry->db->get_object("select * from sl_ebay_api_keys where account_name='{$this->account_name}'", EbayApiKeysModel::class)->row;
        } elseif ($this->listing_provider == 'etsy') {
            return $this->registry->db->get_object("select * from sl_etsy_api_keys where account_name='{$this->account_name}'", EtsyApiKeysModel::class)->row;
        }
    }

    /**
     * @param \Registry|null $registry do not provide it if already injected to listingrow object
     * @throws UnmetDependency
     */
    public function save(\Registry $registry = null)
    {
        if (!$registry && $this->registry) {
            $registry = $this->registry;
        }
        if (!$registry) {
            throw new UnmetDependency("registry object required");
        }
        $statement = $registry->pdo->prepare("INSERT INTO sl_listings(uniqid, salesdata_save_time, itemid, `option`, sku, seller, title, pic_url, item_url, quantity, sold, listing_provider, active, account_name, synced) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE salesdata_save_time=values(salesdata_save_time),itemid=VALUES(itemid),`option`=values(`option`),sku=VALUES(sku),seller=values(seller),title=values(title),pic_url=values(pic_url),item_url=values(item_url),quantity=values(quantity),sold=values(sold),listing_provider=values(listing_provider),active=values(active),account_name=values(account_name),synced=values(synced)");
        $statement->bindParam(1, $this->uniqid);
        $statement->bindParam(2, $this->salesdata_save_time);
        $statement->bindParam(3, $this->itemid);
        $statement->bindParam(4, $this->option);
        $statement->bindParam(5, $this->sku);
        $statement->bindParam(6, $this->seller);
        $statement->bindParam(7, $this->title);
        $statement->bindParam(8, $this->pic_url);
        $statement->bindParam(9, $this->item_url);
        $statement->bindParam(10, $this->quantity);
        $statement->bindParam(11, $this->sold);
        $statement->bindParam(12, $this->listing_provider);
        $statement->bindParam(13, $this->active);
        $statement->bindParam(14, $this->account_name);
        $statement->bindParam(15, $this->synced, \PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * http://developer.ebay.com/devzone/xml/docs/reference/ebay/ReviseInventoryStatus.html#Request.InventoryStatus.Quantity
     * important for ebay:
     * remember for ebay: in a ReviseInventoryStatus request, the Quantity to set means the total quantity
     * if the $new_quantity is same as the remote item quantity available(quantity-sold) then ebay rejects the update
     * for ebay Get calls, the returned Item.Quantity means total quantity (QuantityAvailable+Sold)
     * see the ReviseInventoryStatus doc page bottom example
     * @param  LocalInventoryItem $inventoryItem
     * @return RemoteResponse
     * @throws UnmetDependency
     * @throws ApiKeyNotFoundException
     * @throws StockSyncNotRequiredException
     * @throws \Exception
     */
    function update_quantity_to_remote(LocalInventoryItem $inventoryItem)
    {
        if (!$this->registry) {
            throw new UnmetDependency('unmet dependency: Registry object');
        }
        if ($this->listing_provider == 'ebay') {
            if ($inventoryItem->balance == $this->balance) {
                throw new StockSyncNotRequiredException("listing#{$this->uniqid} local balance is {$this->balance}, new proposed balance is {$inventoryItem->balance}");
            }
            
            $new_quantity = $inventoryItem->balance;

            # handle the multiply
            if ($multiply = $this->_get_multiply()) {
                $new_quantity = ($new_quantity / $multiply);
            }
            print("trying new quantity $new_quantity\n");

            $api_keys = $this->api_keys($this->registry);
            $api = new ReviseInventoryStatus($api_keys);
            $requestxml = '<?xml version="1.0" encoding="utf-8"?>
                                    <ReviseInventoryStatusRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                                    <RequesterCredentials>
                                    <eBayAuthToken>' . $api_keys->requestToken . '</eBayAuthToken>
                                    </RequesterCredentials>
                                    <InventoryStatus>
                                        <ItemID>' . $this->itemid . '</ItemID>
                                        <SKU>' . $this->sku . '</SKU>
                                        <Quantity>' . $new_quantity . '</Quantity>
                                    </InventoryStatus>
                                    <MessageID>1</MessageID>
                                    <WarningLevel>High</WarningLevel>
                                    <Version>' . $api_keys->compatLevel . '</Version>
                                    </ReviseInventoryStatusRequest>​';

            /** @var EbayApiResponse $response */
            $response = $api->execute($requestxml);
            $resp = new RemoteResponse($response->http_status_code, $response->error, empty($response->error), ['xml' => $response->xml]);
            return $resp;
        } elseif ($this->listing_provider == 'etsy') {
            /** @var EtsyApiKeysModel $api_keys */
            $api_keys = $this->api_keys();
            if (empty($api_keys)) {
                throw new ApiKeyNotFoundException("etsy api key for listing#{$this->uniqid} not found");
            }
            $api = new EtsyApi($api_keys);
            $api->updateInventory([

            ]);
        }
    }

    /**
     * @return int
     * @throws \Exception
     */
    private function _get_multiply()
    {
        if (!isset($this->registry)) {
            throw new \Exception("registry not injected inside listingrow");
        }
        $row = $this->registry->db->query("select multiply from sl_local_inventory_linked_item where item_uniqid='{$this->uniqid}'")->row;
        if (!empty($row)) {
            return $row['multiply'];
        } else {
            throw new \Exception("#{$this->uniqid} is not a linked item");
        }
    }

    function __get($name)
    {
        switch ($name) {
            case 'multiply':
                if (!isset($this->_multiply)) {
                    $this->_multiply = $this->_get_multiply();
                }
                return $this->_multiply;
                break;
            case 'title_short':
                return substr($this->title, 0, 16) . '...';
                break;
            case 'available':
            case 'balance':
                return $this->quantity - $this->sold;
                break;
        }
    }
}