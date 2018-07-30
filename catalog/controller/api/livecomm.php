<?php

use resgef\synclist\system\datatypes\apikeysinfo\ApiKeysInfo;
use resgef\synclist\system\datatypes\remoteprovider\RemoteProvider;

require_once DIR_SYSTEM . '/library/livecommresponse.php';

/**
 * @property SyncListApi $synclist_api
 * @property Config $config
 * @property Response $response
 * @property ModelLogLog $model_log_log
 * @property Modelebayapikeys $model_ebay_apikeys
 */
class ControllerApiLivecomm extends Controller
{
    use livecomm;

    function index()
    {
        $resp_arr = $this->livecommResponse()
            ->on('notification', function ($log_id) {
                $last_id = $this->synclist_api->Logger->last_id();
                if (!$log_id) { //init
                    $cronlogs = $this->synclist_api->Logger->recent_cron_logs();
                    $otherlogs = $this->synclist_api->Logger->recent_event_logs();
                } else { //regular
                    $cronlogs = $this->synclist_api->Logger->cronlogs_after($log_id);
                    $otherlogs = $this->synclist_api->Logger->otherlogs_after($log_id);
                }
                return [
                    'last_id' => $last_id,
                    'cronlogs' => $cronlogs,
                    'otherlogs' => $otherlogs,
                    'errorlogs' => []
                ];
            })->on('inv_page_cache', function () {
                $inv_items = [];
                foreach ($this->synclist_api->LocalInventory->items() as $item) {
                    $inv_items[$item['id']] = $item;
                }
                $listing_items = [];
                foreach ($this->synclist_api->Listing->items() as $item) {
                    $listing_items[$item['uniqid']] = $item;
                }
                return [
                    'inv_items' => $inv_items,
                    'listing_items' => $listing_items
                ];
            })->on('save_inventory', function ($local_inv_id, $title, array $linked_items = [], array $calc_input_history = []) {
                $this->load->model('log/log');
                $this->load->model('ebay/apikeys');

                $response = [
                    'success' => false,
                    'error' => '',
                    'messages' => [],
                    'data' => ''
                ];
                $messages = [];

                # now calculate the change amount(+ve or -ve) from the calculation commands
                $change = call_user_func(function () use ($calc_input_history) {
                    $change = 0;
                    foreach ($calc_input_history as $step) {
                        switch ($step['command']) {
                            case '+':
                                $change += (int)$step['input'];
                                break;
                            case '-':
                                $change -= (int)$step['input'];
                                break;
                        }
                    }
                    return $change;
                });

                $invitem = new \resgef\synclist\system\datatypes\localinventoryitem\LocalInventoryItem();
                $invitem->dependency_injection($this->registry);
                # if this is new inventory item, then save it
                if (!$local_inv_id) { # new item
                    $invitem->title = $title;
                    $invitem->quantity = $change;
                    $invitem->sync = false;
                    $invitem->inventory_itemid = $invitem->gen_id();
                    $invitem->save($this->registry);
                } else { # update item
                    $invitem->inventory_itemid = $local_inv_id;
                    $invitem->reload();
                    $invitem->title = $title;
                    $invitem->quantity += $change;
                }

                # now link items
                $invitem->unlink_items($this->registry);
                foreach ($linked_items as $entry) {
                    $invitem->link_item($entry['id'], $entry['multiply'], $this->registry);
                }

                $invitem->save($this->registry);

                # if sync disabled for this item the do not proceed anymore
                if ($invitem->sync == 1) {
                    /** @var \resgef\synclist\system\datatypes\listingrow\ListingRow $listingRow */
                    foreach ($invitem->linked_items() as $listingRow) {
                        if ($invitem->balance == $listingRow->quantity) {
                            $messages[] = "listing item [{$listingRow->listing_provider}] {$listingRow->title_short}... quantity is already same as the inventory item balance figure. So this item will not be synced.";
                            continue;
                        }
                        $listingRow->synced = false;
                        $listingRow->save($this->registry);

                        $messages[] = "listing item [{$listingRow->listing_provider}] {$listingRow->title_short}... current quantity {$listingRow->quantity}, will be synced to {$invitem->balance}";
                    }
                } else {
                    $messages[] = 'inventory item stock will not be synced;';
                }

                $response['success'] = true;
                $response['messages'] = $messages;

                return $response;
            })
            ->on('inv_sync_setting', function ($invid, $status) {
                $invitem = new \resgef\synclist\system\datatypes\localinventoryitem\LocalInventoryItem();
                $invitem->dependency_injection($this->registry);
                $invitem->inventory_itemid = $invid;
                $invitem->reload();
                $invitem->sync = $status == 'true' ? true : false;
                $invitem->save($this->registry);
                return true;
            })
            ->on('inv_sync_retry', function ($invid) {
                $invitem = new \resgef\synclist\system\datatypes\localinventoryitem\LocalInventoryItem();
                $invitem->dependency_injection($this->registry);
                $invitem->inventory_itemid = $invid;
                $invitem->reload();
                return $invitem->sync_linked_items_to_remote();
            })
            ->on('delete_inv_item', function ($inv_id) {
                return $this->synclist_api->LocalInventory->delete($inv_id);
            })->on('get_order', function ($order_id) {
                return $this->synclist_api->Orders->get_order($order_id);
            })
            ->on('filters_info', function () {
                $info = \array_map(function ($f) {
                    return [
                        'filter_id' => $f['id'],
                        'orders_count' => $this->synclist_api->Orders->filter_orders(['filter' => \json_decode($f['rules'], true)], 0, 1)['total_orders_count']
                    ];
                }, $this->synclist_api->Orders->all_filters());
                return $info;
            })
            ->on('cronstate', function () {
                return $this->synclist_api->CronState->percentage_completed('main');
            })
            ->on('set_remote_tracking_code', function (Array $remote_api_key_info, $sales_rec, $tracking_code, $shipping_servc) {
//                $remote_provider = '';
//                $seller = 'hanksminerals';
//                if (\strlen($sales_rec) >= 9) { # its etsy receiptid
//                    $remote_provider = 'etsy';
//                } elseif (\strlen($sales_rec) < 9) { //ebay sales record number
//                    $remote_provider = 'ebay';
//                }

                $key_info = new ApiKeysInfo(new RemoteProvider($remote_api_key_info['provider']), $remote_api_key_info['id'], $remote_api_key_info['account_name']);

                $key_info->dependency_injection($this->registry);

                $api_key = $key_info->api_key();

                $api_key->dependency_injection($this->registry);

                /** @var array $resp */
                $resp = $api_key->set_tracking_code($sales_rec, $tracking_code, $shipping_servc);

                return [
                    'success' => $resp['success'],
                    'msg' => @$resp['msg'],
                    'error' => @$resp['error'],
                    'data' => @$resp['data']
                ];
            })->on('save_order_filter', function (Array $filter, $title) {
                return $this->synclist_api->Orders->save_filter($filter, $title);
            })->on('delete_order_filter', function ($title) {
                return $this->synclist_api->Orders->delete_filter($title);
            })->on('orders_filter_page_cache', function () {
                return [
                    'label_tpl_src' => html_entity_decode($this->synclist_api->Orders->shipping_label_tpl())
                ];
            })->on('get_order', function ($order_id) {
                return $this->synclist_api->Orders->get_order($order_id);
            })->on('filter_orders', function ($filter, $start_index, $end_index) {
                return $this->synclist_api->Orders->filter_orders($filter, $start_index, $end_index);
            })
            ->on('save_local_status', function ($order_id, $status) {
                $count = $this->synclist_api->Orders->save_order_local_status($order_id, $status);
                return $count;
            })->on('search_transactions', function ($needle) {
                return $this->synclist_api->Orders->search_transactions($needle);
            })->on('orders_are_combinable', function (Array $selected_order_ids) {
                return $this->synclist_api->Orders->orders_can_be_combined($selected_order_ids);
            })->on('combine_orders', function (Array $order_ids) {
                return $this->synclist_api->Orders->combine_orders($order_ids);
            })->on('uncombine_orders', function (Array $combined_order_ids) {
                return $this->synclist_api->Orders->uncombine_orders($combined_order_ids);
            })->on('is_combined_order', function ($order_id) {
                # returns boolean
                return (boolean)$this->synclist_api->Orders->is_combined_order($order_id);
            })->on('get_filter_tags', function ($filter_id) {
                if ($filter_id) {
                    return $this->synclist_api->Orders->get_filter_tags($filter_id);
                } else {
                    return [];
                }
            })->on('add_filter_tag', function ($filter_id, $tag) {
                return (boolean)$this->synclist_api->Orders->add_filter_tag($filter_id, $tag);
            })->on('remove_filter_tag', function ($filter_id, $tag) {
                return (boolean)$this->synclist_api->Orders->remove_filter_tag($filter_id, $tag);
            })->on('mark_as_label_printed', function ($order_id) {
                return (boolean)$this->synclist_api->Orders->mark_as_label_printed($order_id);
            })->on('cronlogs', function ($start_index, $end_index) {
                return [
                    'rows' => $this->synclist_api->Logger->cronlogs($start_index, $end_index),
                    'total_rows_count' => $this->synclist_api->Logger->count_cronlogs()
                ];
            })->on('otherlogs', function ($start_index, $end_index) {
                return [
                    'rows' => $this->synclist_api->Logger->otherlogs($start_index, $end_index),
                    'total_rows_count' => $this->synclist_api->Logger->count_otherlogs()
                ];
            })->on('logs', function ($log_id) {
                $last_id = $this->synclist_api->Logger->last_id();
                if (!$log_id) { //init
                    $cronlogs = $this->synclist_api->Logger->recent_cron_logs();
                    $otherlogs = $this->synclist_api->Logger->recent_event_logs();
                } else { //regular
                    $cronlogs = $this->synclist_api->Logger->cronlogs_after($log_id);
                    $otherlogs = $this->synclist_api->Logger->otherlogs_after($log_id);
                }
                return [
                    'last_id' => $last_id,
                    'cronlogs' => $cronlogs,
                    'otherlogs' => $otherlogs,
                    'errorlogs' => []
                ];
            })
            ->build_response($_GET, $_POST, $_REQUEST);
        $this->response->setOutput(json_encode($resp_arr));
    }

}