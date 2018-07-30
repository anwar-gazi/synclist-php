<?php

/**
 * all stores combined
 */
class SyncListAPiOrders
{

    /** @var DBMySQLi Description */
    private $db;

    /** @var SyncListApi */
    private $api;

    function __construct(SyncListApi $api)
    {
        $this->db = $api->db;
        $this->api = $api;
    }

    function listing_providers()
    {
        $providers = array_map(function (Array $row) {
            return $row['listing_provider'];
        }, $this->db->query("SELECT DISTINCT(listing_provider) FROM sl_orders ORDER BY listing_provider")->rows);
        return $providers;
    }

    function active_items()
    {
        return $this->db->query("SELECT * FROM sl_listings WHERE `active`=1")->rows;
    }

    function shipping_label_tpl()
    {
        $row = $this->db->query("SELECT * FROM sl_shipping_label_templates WHERE `title`='sample'")->row;
        return (array_key_exists('content', $row) ? $row['content'] : '');
    }

    /**
     * @return array
     */
    public function buyer_countries()
    {
        return array_map(function (Array $row) {
            return $row['ShippingCountryName'];
        }, $this->db->query("SELECT DISTINCT(ShippingCountryName) FROM sl_orders ORDER BY ShippingCountryName")->rows);
    }

    /**
     * search ebay transactions by sales record number
     * etsy transactions by etsy orderid, which is etsy's receipt_id
     * @param String $needle
     * @return array
     */
    public function search_transactions($needle)
    {
        # index active items by itemid
        $items_indexed = [];
        $items = $this->db->query("SELECT * FROM sl_listings WHERE `active`=1")->rows;
        foreach ($items as $item) {
            $items_indexed[$item['itemid']] = $item;
        }

        # now search orders by sales record number(ebay) or orderid/receipt_id(etsy)
        if (strlen($needle) >= 9) { # etsy
            $orders = $this->api->db->query("select * from sl_orders where `OrderID` like '%{$needle}%'")->rows;
        } else { # ebay
            $orders = $this->api->db->query("select * from sl_orders where `SalesRecordNumber` like '%{$needle}%'")->rows;
        }

        # index the orders by orderid, and
        # build an array of `OrderID`='#OrderID' to get their transactions in one query
        $orders_indexed = [];
        $OrderIDs = [];
        foreach ($orders as $order) {
            $orders_indexed[$order['OrderID']] = $order;
            $OrderIDs[] = "`OrderID`='{$order['OrderID']}'";
        }

        # now get the orders transactions and modify
        $order_transactions = $this->api->db->query("SELECT * FROM sl_transactions WHERE " . implode(' or ', $OrderIDs))->rows;
        $transactions = [];
        foreach ($order_transactions as $trans) {
            $transactions[] = [
                'orderid' => $trans['OrderID'],
                'sales_rec_num' => $trans['SalesRecordNumber'],
                'item_url' => @$items_indexed[$trans['ItemID']]['item_url'],
                'pic_url' => @$items_indexed[$trans['ItemID']]['pic_url'],
                'itemid' => @$trans['ItemID'],
                'itemtitle' => $trans['Title'],
                'listing_provider' => $trans['listing_provider'],
                'option' => $trans['option'],
                'qty' => $trans['QuantityPurchased'],
                'ship_to' => $orders_indexed[$trans['OrderID']]['ShippingName']
            ];
        }
        return ['transactions' => $transactions];
    }

    public function all_filters()
    {
        return $this->db->query("SELECT * FROM sl_order_filter")->rows;
    }

    public function all_filter_orders_count()
    {
        return \array_map(function ($f) {
            return [
                'filter_id' => $f['id'],
                'orders_count' => $this->filter_orders(['filter' => \json_decode($f['rules'], true)], 0, 1)['total_orders_count']
            ];
        }, $this->db->query("select * from {$this->api->table_name('order_filter')}")->rows);
    }

    /**
     * log order search history
     * @param string $txt the search text(incoming from frontend)
     * @return boolean
     */
    public function log_search_history($txt)
    {
        if (!$txt) {
            return false;
        }
        $now = \Carbon\Carbon::now()->format(DateTime::ISO8601);
        $this->db->query("insert into sl_synclist_logs set `log`='$txt',`time`='$now',`type`='ordersearch'");
        return $this->db->countAffected();
    }

    public function get_filter($title)
    {
        return $this->db->query("select * from sl_order_filter where title='$title'")->row;
    }

    public function get_filter_by_id($id)
    {
        return $this->db->query("select * from sl_order_filter where id=$id")->row;
    }

    /**
     * title is primary key
     * @param array $filters
     * @param string $title
     * @return int
     */
    public function save_filter(Array $filters, $title)
    {
        $title = $this->db->escape($title);
        $filters_json = $this->db->escape(json_encode($filters));
        $exists = $this->db->query("select * from sl_order_filter where title='{$title}'")->num_rows;
        if ($exists) {
            $this->db->query("update sl_order_filter set rules='{$filters_json}' where title='{$title}'");
        } else {
            $this->db->query("insert into sl_order_filter set title='{$title}',rules='{$filters_json}'");
        }
        return $this->db->countAffected();
    }

    public function delete_filter($title)
    {
        $title = $this->db->escape($title);
        $this->db->query("delete from sl_order_filter where title='$title'");
        return $this->db->countAffected();
    }

    public function field_vals($field)
    {
        $data = array_map(function (Array $info) use ($field) {
            return $info[$field];
        }, $this->db->query("select $field from sl_orders")->rows);
        return array_unique(array_filter($data));
    }

    public function save_order_local_status($order_id, $status)
    {
        $order_id = $this->db->escape($order_id);
        $status = $this->db->escape($status);
        if ($this->db->query("select * from sl_orders where id='$order_id'")->num_rows) {
            $this->db->query("update sl_orders set local_status='$status' where id='$order_id'");
        } else {
            $this->db->query("update sl_combined_orders set local_status='$status' where id='$order_id'");
        }
        return $this->db->countAffected();
    }

    public function save_transaction(Array $fields)
    {
        $set_qstr = array_map(function ($val, $key) {
            return "`$key`='{$this->db->escape($val)}'";
        }, $fields, array_keys($fields));

        if (!$this->db->query("select * from sl_transactions where TransactionID='{$fields['TransactionID']}'")->num_rows) {
            $this->db->query("INSERT INTO sl_transactions SET " . implode(',', $set_qstr));
            return $this->db->getLastId();
        } else {
            $this->db->query("update sl_transactions set " . implode(',', $set_qstr) . " where TransactionID='{$fields['TransactionID']}'");
            return $this->db->countAffected();
        }
    }

    public function save_order(Array $fields)
    {
        $set_qstr = array_map(function ($val, $key) {
            return "`$key`='{$this->db->escape($val)}'";
        }, $fields, array_keys($fields));

        if (!$this->db->query("select * from sl_orders where OrderID='{$fields['OrderID']}'")->num_rows) {
            $this->db->query("INSERT INTO sl_orders SET " . implode(',', $set_qstr));
            return $this->db->getLastId();
        } else {
            $this->db->query("update sl_orders set " . implode(',', $set_qstr) . " where OrderID='{$fields['OrderID']}'");
            return $this->db->countAffected();
        }
    }

    /**
     * combined status aware. search by id, if the order is member of combined order then the combined order is returned
     * @param $id
     * @return array
     */
    public function get_order($id)
    {
        $order = $this->db->query("select * from sl_orders where id='$id'")->row;
        if (empty($order)) {
            $order = $this->db->query("select * from sl_combined_orders where id='$id'")->row;
        } elseif ($order['combine_id']) {
            $order = $this->db->query("select * from sl_combined_orders where combine_id='{$order['combine_id']}'")->row;
        }
        return $this->representable_order($order);
    }

    public function get_order_by_id($id)
    {
        $id = $this->db->escape($id);
        $order = $this->db->query("select * from sl_orders where `id`='{$id}'")->row;
        if (empty($order)) {
            $order = $this->db->query("select * from sl_combined_orders where `id`='{$id}'")->row;
        }
        return $this->representable_order($order);
    }

    public function get_order_by_orderid($orderid)
    {
        $order = $this->db->query("select * from sl_orders where `order_hash`='{$orderid}'")->row;
        if (empty($order)) {
            $order = $this->db->query("select * from sl_combined_orders where `order_hash`='{$orderid}'")->row;
        }
        return $this->representable_order($order);
    }

    public function changeAll(callable $func)
    {
        $db = $this->db->pdo->link;
        $res = $db->query("SELECT * FROM sl_orders");
        $updatecount = 0;
        while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
            $set = [];
            $row2 = $func($row);
            foreach ($row2 as $key => $val) {
                if ($val != $row[$key]) {
                    $set[] = "$key='$val'";
                }
            }
            if (!empty($set)) {
                $set = implode(',', $set);
                $sql = "update sl_orders set $set where OrderID='{$row['OrderID']}'";
                try {
                    $updatecount += $db->exec($sql);
                } catch (PDOException $e) {
                    print("$sql\n");
                    throw new Exception();
                }
            }
        }
        return $updatecount;
    }

    /** API
     * get filtered orders from a to b(pagination)
     * @param array $filter_spec must have key 'filter', optonal 'search', 'sort', 'more_qwhere': sql query where conditions without the 'where' clause
     * the 'search' key value must be an aray with keys 'needle_type' and 'needle'
     * @param integer $start_index
     * @param integer $end_index
     * @param boolean $exclude_combined_members
     * @return array
     */
    public function filter_orders(Array $filter_spec, $start_index, $end_index, $exclude_combined_members = true)
    {
        $qstr_filter_rule_group = function (Array $group) {
            $qstr_filter_rule_box = function (Array $rule_box) use ($group) {
                $qstr = [];
                foreach ($rule_box as $param => $value) {
                    $qwh = $this->qstr_filter_rule($param, $value, $group['rules']);
                    if ($qwh) {
                        $qstr[] = "({$qwh})";
                    }
                }
                return implode(" and ", $qstr);
            };
            $qwhere = [];
            foreach ($group['rules'] as $rule_box) {
                $qwh = $qstr_filter_rule_box($rule_box);
                if ($qwh) {
                    $qwhere[] = "({$qwh})";
                }
            }
            if ($group['clause'] === 'any') { //any rule satisfied
                $qwhere = implode(" or ", $qwhere);
            }
            if ($group['clause'] === 'all') { //any rule satisfied
                $qwhere = implode(" and ", $qwhere);
            }
            return $qwhere;
        };

        $filter = $filter_spec['filter'];
        $search = @$filter_spec['search'];
        $sort = empty($filter_spec['sort']) ? [] : $filter_spec['sort'];

        $query = [];
        foreach ($filter['groups'] as $group) {
            $qwh = $qstr_filter_rule_group($group);
            if ($qwh) {
                $query[] = "({$qwh})";
            }
        }
        if ($filter['clause'] === 'any') { //group1 || group2 || ...
            $query = implode(" or ", $query);
        }
        if ($filter['clause'] === 'all') { //group1 || group2 || ...
            $query = implode(" and ", $query);
        }
        $query = $query ? $query : "1=1";
        if (@$filter_spec['more_qwhere']) {
            $query = "{$filter_spec['more_qwhere']} and ($query)";
        }
        $qwhere = "where {$query}";

        # now adjust for search
        if (!empty($search)) {
            $qwhere_search = $this->qstr_filter_rule($search['needle_type'], $search['needle'], []);
            $qwhere .= " and $qwhere_search";
        }

        $sort_by = '';
        if (is_array($sort)) {
            $sort_by = "order by str_to_date(CreatedTime,'%Y-%m-%dT%T') "
                . (array_key_exists('CreatedTime', $sort) ? $sort['CreatedTime'] : 'desc');
        }
        if ($exclude_combined_members) {
            # TODO combine results from both query then operate
            $where_for_remote = "$qwhere and `order`.combine_id=''";
            $where_for_combined = "$qwhere";
            $count = $this->db->query("select count(*) as total from sl_orders `order` $where_for_remote")->row['total'] + $this->db->query("SELECT count(*) AS total FROM sl_combined_orders `order` $where_for_combined")->row['total'];
            $sql_remote = "select * from sl_orders `order` $where_for_remote $sort_by limit $start_index,$end_index";
            $sql_combined = "select * from sl_combined_orders `order` $where_for_combined $sort_by limit $start_index,$end_index";
            $orders_remote = array_map(function (Array $order) {
                return $this->representable_order($order);
            }, $this->db->query($sql_remote)->rows);
            $orders_combined = array_map(function (Array $order) {
                return $this->representable_order($order);
            }, $this->db->query($sql_combined)->rows);
            $orders = array_merge($orders_remote, $orders_combined);
            $sql = "$sql_remote;$sql_combined";
        } else { //just take plain list of remote orders, neglecting combined orders
            $count = $this->db->query("select count(*) as total from sl_orders `order` $qwhere")->row['total'];
            $sql = "select * from sl_orders `order` $qwhere $sort_by limit $start_index,$end_index";
            $orders = array_map(function (Array $order) {
                return $this->representable_order($order);
            }, $this->db->query($sql)->rows);
        }

        return [
            'sql' => $sql,
            'total_orders_count' => $count,
            'orders' => $orders
        ];
    }

    public function combine_orders(Array $order_ids)
    {
        if (!$this->orders_can_be_combined($order_ids)['combinable']) {
            return false;
        }
        if ($this->all_orders_are_combined($order_ids)) {
            return true;
        }
        $update_combine_id = function (Array $order_ids, $combined_id) {
            foreach ($order_ids as $id) {
                $id = $this->db->escape($id);
                $this->db->query("update sl_orders set combine_id='$combined_id' where `id`='$id'");
            }
            return $this->db->countAffected();
        };
        $set_values_qparts = function ($arr) {
            $parts = [];
            foreach ($arr as $key => $val) {
                if ($key == 'transactions') continue;
                $parts[] = "`$key`='$val'";
            }
            return implode(',', $parts);
        };
        $combined_order = $this->build_combined_order($order_ids);
        $update_combine_id($order_ids, $combined_order['combine_id']);
        $this->db->query("delete from sl_combined_orders where combine_id='{$combined_order['combine_id']}'");
        $this->db->query("delete from sl_combined_transactions where combine_id='{$combined_order['combine_id']}'");
        $this->db->query("INSERT INTO sl_combined_orders SET " . $set_values_qparts($combined_order));
        foreach ($combined_order['transactions'] as $trans) {
            $this->db->query("INSERT INTO sl_combined_transactions SET " . $set_values_qparts($trans));
        }
        return $this->db->countAffected();
    }

    public function uncombine_orders(Array $combined_order_ids)
    {
        foreach ($combined_order_ids as $id) {
            $combine_id = $this->db->query("select combine_id from sl_combined_orders where id='$id'")->row['combine_id'];
            $this->db->query("delete from sl_combined_orders where combine_id='$combine_id'");
            $this->db->query("delete from sl_combined_transactions where combine_id='$combine_id'");
            $this->db->query("update sl_orders set combine_id='' where combine_id='$combine_id'");
        }
        return $this->db->countAffected();
    }

    /**
     * @param array $order_ids the `id` field in order rows
     * @return array
     */
    public function orders_can_be_combined(Array $order_ids)
    {
        $result = [
            'combinable' => true,
            'cause' => ''
        ];

        if (count($order_ids) <= 1) {
            $result['combinable'] = false;
            return $result;
        }

        $shipping_addresses = [];
        $remote_providers = [];
        $local_statuses = [];
        foreach ($order_ids as $id) {
            $order = $this->get_order_by_id($id);
            if (!empty($shipping_addresses)) {
                if (!in_array($order['shipping_address'], $shipping_addresses)) {
                    $result['combinable'] = false;
                    $result['cause'] = sprintf('addresses not same ' . implode(';', $shipping_addresses) . ';' . $order['shipping_address']);
                }
                break;
            } else {
                $shipping_addresses[] = $order['shipping_address'];
            }
            if (!empty($remote_providers)) {
                if (!in_array($order['listing_provider'], $remote_providers)) {
                    $result['combinable'] = false;
                    $result['cause'] = 'remote provider not same';
                }
                break;
            } else {
                $remote_providers[] = $order['listing_provider'];
            }
            if ($order['shipped'] == 1) {
                $result['combinable'] = false;
                $result['cause'] = 'one or more orders shipped';
                break;
            }
            if (!empty($local_statuses)) {
                if (!in_array($order['local_status'], $local_statuses)) {
                    $result['combinable'] = false;
                    $result['cause'] = 'local status not same';
                }
                break;
            } else {
                $local_statuses[] = $order['local_status'];
            }
        }
        return $result;
    }

    private function gen_combine_id(Array $order_ids)
    {
        return \substr(\md5(\implode('', $order_ids)), 0, 10);
    }

    private function build_combined_order(Array $order_ids)
    {
        $member_orders = array_map(function ($id) {
            return $this->db->query("select * from sl_orders where id=$id")->row;
        }, $order_ids);

        $combined_order = $this->db->query("SELECT * FROM sl_orders WHERE ShippingServiceCost=(SELECT max(0+ShippingServiceCost) FROM sl_orders WHERE id IN (" . implode(',', $order_ids) . ")) AND id IN (" . implode(',', $order_ids) . ")")->row;
        $combined_order['combine_id'] = $this->gen_combine_id($order_ids);
        $combined_order['id'] = call_user_func(function () use ($member_orders) {
            $arr = array_map(function (Array $order) {
                return $order['id'];
            }, $member_orders);
            return implode('-', $arr);
        });;
        $combined_order['OrderID'] = call_user_func(function () use ($member_orders) {
            $arr = array_map(function (Array $order) {
                return $order['OrderID'];
            }, $member_orders);
            return implode('-', $arr);
        });
        $combined_order['order_hash'] = call_user_func(function () use ($member_orders) {
            $arr = array_map(function (Array $order) {
                return $order['order_hash'];
            }, $member_orders);
            return implode('-', $arr);
        });
        $combined_order['EIASToken'] = call_user_func(function () use ($member_orders) {
            $arr = array_map(function (Array $order) {
                return $order['EIASToken'];
            }, $member_orders);
            return implode('-', $arr);
        });
        $combined_order['Total'] = call_user_func(function () use ($member_orders) {
            $arr = array_map(function (Array $order) {
                return $order['Total'];
            }, $member_orders);
            return array_sum($arr);
        });
        $combined_order['AmountPaid'] = call_user_func(function () use ($member_orders) {
            $arr = array_map(function (Array $order) {
                return $order['AmountPaid'];
            }, $member_orders);
            return array_sum($arr);
        });
        $combined_order['SalesRecordNumber'] = call_user_func(function () use ($member_orders) {
            $arr = array_map(function (Array $order) {
                return $order['SalesRecordNumber'];
            }, $member_orders);
            return implode('-', $arr);
        });
        $combined_order['CreatedTime'] = \Carbon\Carbon::now()->toIso8601String();
        $combined_order['note'] = call_user_func(function () use ($member_orders) {
            $arr = array_map(function (Array $order) {
                return $order['note'];
            }, $member_orders);
            return implode(';', array_filter($arr));
        });
        $combined_order['transactions'] = call_user_func(function () use ($member_orders, $combined_order) {
            $transactions = [];
            foreach ($member_orders as $order) {
                $transactions = array_merge($transactions, $this->db->query("select * from sl_transactions where OrderID='{$order['OrderID']}'")->rows);
            }
            $transactions = array_map(function (Array $transaction) use ($combined_order) {
                $transaction['combine_id'] = $combined_order['combine_id'];
                $transaction['OrderID'] = $combined_order['OrderID'];
                return $transaction;
            }, $transactions);
            return $transactions;
        });
        return $combined_order;
    }

    /*
     * order filter management methods and api
     * rule: a single parameter and its value pair, eg. where quantity is 1
     * rule box: usually has one rule, but may have more, eg. where orderline item is ... with quantity=1
     * eg. where quantity=1, order total=5 AND shipping status=completed
     * rule group: rule boxes with caluse among them
     */

    /* for a rule=>value pair */

    private function qstr_filter_rule($rule_name, $rule_value, Array $group_rules, $order_type = 'remote')
    {
        /**
         * @param $rule_name
         * @return bool|integer|string false if rule not found, may return boolean false if rule found but empty valued
         */
        $group_has_rule = function ($rule_name) use ($group_rules) {
            foreach ($group_rules as $rule_box) {
                foreach ($rule_box as $key => $val) {
                    if ($key === $rule_name) {
                        return $val;
                    }
                }
            }
            return false;
        };

        $trans_table = 'sl_transactions';
        switch ($order_type) {
            case 'remote':
                $trans_table = 'sl_transactions';
                break;
            case 'combined':
                $trans_table = 'sl_combined_transactions';
                break;
        }

        $qparts = '';
        switch ($rule_name) {
            case 'local_status':
                if (!$rule_value) {
                    $qparts = '';
                } else {
                    $rule_value = $this->db->escape($rule_value);
                    $qparts = "`order`.local_status='$rule_value'";
                }
                break;
            case 'remote_provider':
                if (!$rule_value) {
                    $qparts = '';
                } else {
                    $rule_value = $this->db->escape($rule_value);
                    $qparts = "`order`.listing_provider='$rule_value'";
                }
                break;
            case 'orderline_ItemID':
                if (!$rule_value) { //any item then
                    $qparts = '';
                } else {
                    $rule_value = $this->db->escape($rule_value);
                    $exclusive = ($group_has_rule('orderline_item_exclusivity_flag') == 'exclusively');

                    $orderids_inclusive = array_map(function (Array $tr) {
                        return "'" . $tr['OrderID'] . "'";
                    }, $this->db->query("select trans.OrderID from {$trans_table} trans where trans.ItemID='{$rule_value}'")->rows);
                    $orderids_inclusive = array_unique($orderids_inclusive);

                    if ($exclusive) {
                        $orderids_unwanted = array_map(function (Array $tr) {
                            return "'" . $tr['OrderID'] . "'";
                        }, $this->db->query("select trans.OrderID from {$trans_table} trans where trans.OrderID in (" . implode(',', $orderids_inclusive) . ") and trans.ItemID <>'{$rule_value}' ")->rows);
                        $orderids_unwanted = array_unique($orderids_unwanted);
                        $orderids = array_diff($orderids_inclusive, $orderids_unwanted);
                    } else {
                        $orderids = $orderids_inclusive;
                    }

                    $qparts = "`order`.OrderID in (" . implode(',', $orderids) . ")";
                }
                break;
            # any order's all transactions QuantityPurchased sum === rule_value
            case 'orderline_item_quantity':
                if (!$rule_value) { //any quantity then
                    $qparts = '';
                } else {
                    $orderids = array_map(function (Array $tr) {
                        return "'" . $tr['OrderID'] . "'";
                    }, $this->db->query("select OrderID from {$trans_table} where QuantityPurchased={$rule_value}")->rows);
                    $qparts = "`order`.OrderID in (" . implode(',', $orderids) . ")";
                }
                break;
            case 'orderline_item_quantity_min':
                if ($group_has_rule('orderline_item_quantity')) {
                    $qparts = '';
                    break;
                }
                if (!$rule_value) {
                    $qparts = '';
                } else {
                    if ($max = $group_has_rule('orderline_item_quantity_max')) { //both min and max exists
                        $min = $this->db->escape($rule_value);
                        $max = $this->db->escape($max);
                        $orderids = array_map(function (Array $tr) {
                            return "'" . $tr['OrderID'] . "'";
                        }, $this->db->query("select OrderID from {$trans_table} where QuantityPurchased BETWEEN  {$min} AND {$max}")->rows);
                    } else { //only this min exists
                        $orderids = array_map(function (Array $tr) {
                            return "'" . $tr['OrderID'] . "'";
                        }, $this->db->query("select OrderID from {$trans_table} where QuantityPurchased >= {$rule_value}")->rows);
                    }
                    $qparts = "`order`.OrderID in (" . implode(',', $orderids) . ")";
                }
                break;
            case 'orderline_item_quantity_max':
                if ($group_has_rule('orderline_item_quantity')) {
                    $qparts = '';
                    break;
                }
                if (!$rule_value) {
                    $qparts = '';
                } else {
                    if ($group_has_rule('orderline_item_quantity_min')) { //already taken care of so skip
                        $qparts = '';
                        break;
                    } else {
                        $orderids = array_map(function (Array $tr) {
                            return "'" . $tr['OrderID'] . "'";
                        }, $this->db->query("select OrderID from {$trans_table} where QuantityPurchased <= {$rule_value}")->rows);
                        $qparts = $qparts . "`order`.OrderID in (" . implode(',', $orderids) . ")";
                    }
                }
                break;
            case 'order_total_min':
                $qparts = "0+`order`.Total>=$rule_value";
                break;
            case 'order_total_max':
                $qparts = "0+`order`.Total<='$rule_value'";
                break;
            case 'shipping_service':
                if (!$rule_value) { //any value
                    $qparts = '';
                } else {
                    $rule_value = $this->db->escape($rule_value);
                    $qparts = "`order`.ShippingService like '%$rule_value%'";
                }
                break;
            case 'payment_status':
                if ($rule_value == 'paid') {
                    $qparts = "`order`.paid=1";
                } else if ($rule_value == 'not-paid') {
                    $qparts = "`order`.paid<>1";
                } else { //any value
                    $qparts = '';
                }
                break;
            case 'shipment_status':
                if ($rule_value == 'shipped') {
                    $qparts = "`order`.shipped=1";
                } elseif ($rule_value == 'not-shipped') {
                    $qparts = "`order`.shipped<>1";
                } else {
                    $qparts = '';
                }
                break;
            case 'buyer_country':
                $rule_value = $this->db->escape($rule_value);
                $qparts = "`order`.ShippingCountryName like '%$rule_value%'";
                break;
            case 'order_hash':
                $rule_value = $this->db->escape($rule_value);
                $qparts = "`order`.order_hash like '%$rule_value%'";
                break;
            case 'local_order_id':
                $rule_value = $this->db->escape($rule_value);
                $qparts = "`order`.id like '%$rule_value%'";
                break;
            case 'buyer_name':
                $rule_value = $this->db->escape($rule_value);
                $qparts = "`order`.ShippingName like '%$rule_value%'";
                break;
            case 'buyer_ebay_username':
                $rule_value = strtolower($this->db->escape($rule_value));
                $qparts = "LOWER(`order`.BuyerUserID) like '%$rule_value%'";
                break;
        }
        return $qparts;
    }

    private function representable_order(Array $order)
    {
        $addr_verf_state = function (Array $order) {
            if ($order['ShippingCountry'] != 'US') {
                return 'NOTUS';
            }
            if ($order['verify_tried'] != 1) {
                return 'NOTVERF';
            }
            if ($order['verified_addr_message']) {
                return 'BADADDR';
            }
            if ((strtolower($order['ShippingStreet1']) != strtolower($order['verified_addr_line1'])) || (strtolower($order['ShippingStreet2']) != strtolower($order['verified_addr_line2'])) || (strtolower($order['ShippingCityName']) != strtolower($order['verified_addr_city'])) || (strtolower($order['ShippingStateOrProvince']) != strtolower($order['verified_addr_state'])) || (strtolower($order['ShippingPostalCode']) != strtolower($order['verified_addr_zip'])) || (strtolower($order['ShippingCountry']) != strtolower($order['verified_addr_country']))) {

                return "HASSUGG";
            }
            return '';
        };
        $shipping_address = function (Array $order) {
            $parts = [];
            //$parts[] = $order['ShippingName'];
            $parts[] = $order['ShippingStreet1'];
            $parts[] = $order['ShippingStreet2'];
            $parts[] = $order['ShippingCityName'];
            $parts[] = $order['ShippingStateOrProvince'];
            $parts[] = $order['ShippingStateOrProvince'];
            $parts[] = $order['ShippingPostalCode'];
            $parts = array_map(function ($p) {
                return strtoupper(trim($p));
            }, $parts);
            $parts = array_filter($parts);
            return implode(', ', $parts);
        };

        $is_combined_order = $order['combine_id'] ? true : false;

        if ($is_combined_order) {
            $order['is_combined_order'] = true;
            $transactions = $this->db->query("select * from sl_combined_transactions where combine_id='{$order['combine_id']}'")->rows;
        } else {
            $order['is_combined_order'] = false;
            $transactions = $this->db->query("select * from sl_transactions where OrderID='{$order['OrderID']}'")->rows;
        }

        $order['transactions'] = array_map(function (Array $trans) {
            $ItemID = $trans['ItemID'];
            $pic_url = $this->api->Listing->item_pic_url($ItemID);
            $trans['pic_url'] = $pic_url;
            return $trans;
        }, $transactions);
        $order['addr_verif_state'] = $addr_verf_state($order);
        $order['shipping_address'] = $shipping_address($order);

        return $order;
    }

    private function all_orders_are_combined(Array $order_ids)
    {
        $set = implode(',', $order_ids);
        $rows = $this->db->query("select distinct combine_id from sl_orders where id in ($set)")->rows;
        if (count($rows) == 1 && $combine_id = reset($rows)['combine_id']) {
            if ($this->db->query("select * from sl_combined_orders where combine_id='$combine_id'")->num_rows && $this->db->query("select * from sl_combined_transactions where combine_id='$combine_id'")->num_rows) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    private $local_statuses = [
        "cancelled",
        "waiting_on_customer_response",
        "shipped",
        "ready"
    ];

    public function local_status_options()
    {
        return $this->local_statuses;
    }

    public function determine_local_status($not_cancelled, $waiting_on_customer, $shipped)
    {
        if (!$not_cancelled) {
            return $this->local_statuses[0];
        }
        if ($waiting_on_customer) {
            return $this->local_statuses[1];
        }
        if ($shipped) {
            return $this->local_statuses[2];
        }
        return $this->local_statuses[3];
    }

    /**
     * @param string|integer $order_id ,the id field
     * @return boolean
     */
    public function is_combined_order($order_id)
    {
        return $this->get_order($order_id)['is_combined_order'];
    }

    public function in_which_filter($order_id)
    {
        $filter_ids = [];
        foreach ($this->all_filters() as $frow) {
            $res = $this->filter_orders(['filter' => json_decode($frow['rules'], true), 'more_qwhere' => "id='$order_id'"], 0, 1);
            if ($res['total_orders_count']) {
                $filter_ids[] = $frow['id'];
            }
        }
        return $filter_ids;
    }

    /**
     * @param $filter_id
     * @return array
     */
    public function get_filter_tags($filter_id)
    {
        $tags_str = $this->get_filter_by_id($filter_id)['tags'];
        return array_filter(explode(',', $tags_str));
    }

    public function add_filter_tag($filter_id, $tag)
    {
        $filter_id = $this->db->escape($filter_id);
        $tag = trim($this->db->escape($tag));
        $existing = $this->get_filter_tags($filter_id);
        $existing[] = $tag;
        $existing = array_unique($existing);
        $tags = implode(',', $existing);
        $this->db->query("update sl_order_filter set tags='$tags' where id=$filter_id");
        return $this->db->countAffected();
    }

    public function remove_filter_tag($filter_id, $tag)
    {
        $filter_id = $this->db->escape($filter_id);
        $tag = trim($this->db->escape($tag));

        $existing = $this->get_filter_tags($filter_id);
        if (($key = array_search($tag, $existing)) !== false) {
            $existing[$key] = '';
            $tags = implode(',', array_filter($existing));
            $this->db->query("update sl_order_filter set tags='{$tags}' where id=$filter_id");
            return $this->db->countAffected();
        } else {
            return true;
        }
    }

    public function get_combine_id($order_id)
    {
        $order = $this->get_order($order_id);
        return $order['combine_id'];
    }

    public function mark_as_label_printed($order_id)
    {
        $time = Carbon\Carbon::now()->toIso8601String();
        if ($this->is_combined_order($order_id)) {
            $this->db->query("update sl_combined_orders set label_print_time='$time' where id='$order_id'");
            $combine_id = $this->get_combine_id($order_id);
            $this->db->query("update sl_orders set label_print_time='$time' where combine_id='$combine_id'");
        } else {
            $this->db->query("update sl_orders set label_print_time='$time' where id=$order_id");
        }
        return $this->db->countAffected();
    }

}
