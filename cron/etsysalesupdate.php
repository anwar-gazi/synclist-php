<?php

namespace resgef\synclist\cron\etsysalesupdate;

use Guzzle\Http\Client;
use Guzzle\Plugin\Oauth\OauthPlugin;
use resgef\synclist\system\datatypes\etsyapikeysmodel\EtsyApiKeysModel;
use resgef\synclist\system\datatypes\etsylistingrow\EtsyListingRow;
use resgef\synclist\system\exceptions\etsynoactiveitemsexception\EtsyNoActiveItemsException;
use resgef\synclist\system\helper\http\Http;
use resgef\synclist\system\interfaces\croninterface\CronInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class EtsySalesUpdate
 * @package resgef\synclist\cron\etsysalesupdate
 * @property \Modeletsyapikeys $model_etsy_apikeys
 * @property \PDO $pdo
 */
class EtsySalesUpdate extends \Controller implements CronInterface
{
    /** @var ConsoleOutput $output */
    private $output;

    /**
     * currently we are not accounting for errors, paginations ... etc
     * uniqid pattern: etsy<listing_id>
     * uniqid pattern for variation: etsy<listing_id>opt<property_id><value_id>
     * @throws EtsyNoActiveItemsException
     */
    public function execute()
    {
        $this->output = new ConsoleOutput();
        $output = $this->output;
        $output->writeln("<info>etsy sales update</info>");
        $this->load->model('etsy/apikeys');
        /** @var EtsyApiKeysModel $keys */
        foreach ($this->model_etsy_apikeys->all() as $keys) {
            $output->writeln($keys->account_name);
            if (!$this->db->query("select count(*) as all_count from sl_listings where listing_provider='etsy' and account_name='{$keys->account_name}' and active=1")->row['all_count']) {
                $output->writeln("<info>new store linked</info>");
            }
            $resp_json = Http::request("https://openapi.etsy.com/v2/shops/hanksminerals/listings/active?api_key=" . $keys->key_string . "&includes=MainImage");
            $response = json_decode($resp_json);
            $output->writeln("{$response->count} active items");

            if (!$response->count) {
                throw new EtsyNoActiveItemsException();
            }

            /** @var EtsyListingRow[] $items */
            $items = [];
            #list the items and variations
            foreach ($response->results as $item) {
                $uniqid = 'etsy' . $item->listing_id;
                $sold = $this->fetch_item_sold($item->listing_id);

                $listingrow = new EtsyListingRow();
                $listingrow->account_name = $keys->account_name;
                $listingrow->uniqid = $uniqid;
                $listingrow->itemid = $item->listing_id;
                $listingrow->option = '';
                $listingrow->active = 1;
                $listingrow->seller = $keys->account_name;
                $listingrow->title = $item->title;
                $listingrow->pic_url = $item->MainImage->url_fullxfull;
                $listingrow->item_url = $item->url;
                $listingrow->sold = $sold->{$item->listing_id};
                if ($item->quantity) {
                    $listingrow->quantity = $item->quantity;
                }

                if ($item->has_variations) {
                    $output->writeln("getting variations");
                    $options = $this->get_variations($item->listing_id);
                    $output->writeln("item {$item->listing_id} has " . count($options) . " variations");
                    foreach ($options as $opt) { //each variation item
                        $varitem = $listingrow;
                        $option2 = "";
                        foreach ($opt as $key => $val) { //each property of the variation
                            $option2 .= strtolower($key) . ":" . strtolower($val) . ",";
                        }
                        $option = rtrim($option2, ",");
                        $listingrow->sold = $sold->$option;
                        $listingrow->option = $option;
                        $listingrow->uniqid = $listingrow->uniqid . '-' . substr(md5($option), 0, 8);
                        $items[] = $varitem;
                    }
                } else {
                    $items[] = $listingrow;
                }
            }

            # now save
            $output->writeln("total " . count($items) . " items");
            $this->pdo->beginTransaction();
            foreach ($items as $listingRow) {
                $statement = $this->pdo->prepare("INSERT INTO sl_listings 
                (uniqid, salesdata_save_time, itemid, `option`, sku, seller, title, pic_url, item_url, quantity, sold, listing_provider, active, account_name) 
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?) 
                ON DUPLICATE KEY UPDATE salesdata_save_time=VALUES(salesdata_save_time),itemid=VALUES(itemid),`option`=VALUES(`option`),sku=VALUES(sku),seller=VALUES(seller),title=VALUES(title),pic_url=VALUES(pic_url),item_url=VALUES(item_url),quantity=VALUES(quantity),sold=VALUES(sold),listing_provider=values(listing_provider),active=values(active),account_name=values(account_name)");

                $statement->bindParam(0, $listingRow->uniqid);
                $statement->bindParam(1, $listingRow->salesdata_save_time);
                $statement->bindParam(2, $listingRow->itemid);
                $statement->bindParam(3, $listingRow->option);
                $statement->bindParam(4, $listingRow->sku);
                $statement->bindParam(5, $listingRow->seller);
                $statement->bindParam(6, $listingRow->title);
                $statement->bindParam(7, $listingRow->pic_url);
                $statement->bindParam(8, $listingRow->item_url);
                $statement->bindParam(9, $listingRow->quantity);
                $statement->bindParam(10, $listingRow->sold);
                $statement->bindParam(11, $listingRow->listing_provider);
                $statement->bindParam(12, $listingRow->active, \PDO::PARAM_INT);
                $statement->bindParam(13, $listingRow->account_name);

                $statement->execute();
            }
            $this->pdo->commit();
        }
    }

    private function fetch_item_sold($listing_id)
    {
        $output = $this->output;
        $output->writeln("fetching item $listing_id transactions to calculate sold");
        $client = new Client('https://openapi.etsy.com/v2/');
        $sold = new \stdClass();
        foreach ($this->model_etsy_apikeys->all() as $keys) {
            $oauth = new OauthPlugin(array(
                'consumer_key' => $keys->key_string,
                'consumer_secret' => $keys->shared_secret,
                'token' => $keys->oauth_token,
                'token_secret' => $keys->oauth_token_secret
            ));
            $client->addSubscriber($oauth);

            $sold->$listing_id = 0;

            $total = 100; //initial, actual value coming later
            for ($offset = 0; ($offset <= $total); $offset = $offset + 100) { //fetch all pages
                $trans_json = $client->get("listings/$listing_id/transactions?limit=100&offset=$offset")->send()->getBody();
                $item_trans = json_decode($trans_json);
                $total = $item_trans->count;
                foreach ($item_trans->results as $trans) {
                    if ($trans->variations) {
                        $opt_arr = [];
                        /* make key=>value pair of attributes*/
                        foreach ($trans->variations as $var) { //calculate for the variations
                            $opt_arr[$var->formatted_name] = $var->formatted_value;
                        }
                        ksort($opt_arr);
                        $option2 = '';
                        foreach ($opt_arr as $key => $val) {
                            $option2 .= strtolower($key . ':' . $val . ',');
                        }
                        $option = rtrim($option2, ',');
                        if (!property_exists($sold, $option)) {
                            $sold->$option = 0;
                        }
                        if ($trans->quantity) {
                            $sold->$option += $trans->quantity;
                        }
                    } else {
                        if ($trans->quantity) {
                            $sold->$listing_id += $trans->quantity;
                        }
                    }
                }
            }
        }

        return $sold;
    }

    private
    function get_variations($listing_id)
    {
        $client = new Client('https://openapi.etsy.com/v2');
        $opt = [];
        foreach ($this->model_etsy_apikeys->all() as $keys) {
            $oauth = new OauthPlugin(array(
                'consumer_key' => $keys->key_string,
                'consumer_secret' => $keys->shared_secret,
                'token' => $keys->oauth_token,
                'token_secret' => $keys->oauth_token_secret
            ));
            $client->addSubscriber($oauth);
            $r = $client->get("listings/$listing_id/variations")->send();
            $vars = json_decode($r->getBody());
            foreach ($vars->results as $vi => $vars_by_cat) { //each category
                $name = strtolower($vars_by_cat->formatted_name);
                $opt2 = [];
                foreach ($vars_by_cat->options as $var) { //each value
                    $value = trim(preg_replace('#\[.+\]?#', '', strtolower($var->formatted_value)));
                    if ($vi == 0) {
                        $opt2[] = [$name => $value];
                    } else {
                        foreach ($opt as $o) {
                            $o[$name] = $value;
                            ksort($o);
                            $opt2[] = $o;
                        }
                    }
                }
                $opt = $opt2;
            }
        }
        return $opt;
    }
}