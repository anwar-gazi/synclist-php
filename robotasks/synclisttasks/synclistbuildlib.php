<?php

/**
 * build our system according to the environment: production, development(local)
 */
class SynclistBuildLib extends \Robo\Tasks {

    use \Robo\Common\TaskIO;
    use \Exec;

    static $permissions = [
        [755, ''],
        [777, 'system/cache/'],
    ];

    function build_demo_db($from_dbname, $tmp_sql, $mysql_root_pass) {
        $this->printTaskInfo("building demo database");
        $tmp_db = "tmp" . time();
        $this->exec("mysqldump -u root -p$mysql_root_pass $from_dbname > $tmp_sql"); //exported to the sql file
        $this->exec("mysql -u root -p$mysql_root_pass -e 'create database $tmp_db'"); //create tmp database
        $this->exec("mysql -u root -p$mysql_root_pass $tmp_db < $tmp_sql"); //import that sql file to tmp database
        // now obfuscate sensative data
        $this->printTaskInfo("obfuscating local inventory, listing and logs");
        //$fromdb = new DB('mysqli', 'localhost', 'root', $mysql_root_pass, $from_dbname);
        $db = new DB\MySQLi('localhost', 'root', $mysql_root_pass, $tmp_db);
        $db->query("update sl_local_inventory set quantity=999");
        $db->query("update sl_listings set quantity=999, sold=555");
        //delete logs
        $db->query("delete from sl_synclist_logs");

        $now = Carbon::now()->toISO8601String();

        //now obfuscate transactions
        $transactions = $db->query("select * from sl_transactions limit 0, 200")->rows;
        $this->printTaskInfo("obfuscating " . count($transactions) . " transactions");
        $orderis = [];
        foreach ($transactions as $trans) {
            $orderis[] = "'" . $trans["OrderID"] . "'";
            $data = [
                'TransactionID' => substr(sha1($trans['TransactionID']), 0, strlen($trans['TransactionID'])),
                'OrderLineItemID' => '',
                'ExtendedOrderID' => substr(sha1($trans['ExtendedOrderID']), 0, strlen($trans['ExtendedOrderID'])),
                'OrderID' => substr(sha1($trans['OrderID']), 0, strlen($trans['OrderID'])),
                'ItemID' => '',
                'Title' => '',
                'option' => '',
                'BuyerEIASToken' => substr(sha1($trans['BuyerEIASToken']), 0, strlen($trans['BuyerEIASToken'])),
                'BuyerEmail' => '',
                'QuantityPurchased' => $trans['QuantityPurchased'] * 15,
                'TransactionPrice' => $trans['TransactionPrice'] * 15,
                'EstimatedDeliveryTimeMin' => '',
                'EstimatedDeliveryTimeMax' => '',
                'listing_provider' => 'ebay'
            ];
            $db->query("update sl_transactions set " . array_to_qstr($data) . " where TransactionID={$trans['TransactionID']}");
        }

        //obfuscate orders
        $orders = $db->query("select * from sl_orders where OrderID in (" . implode(',', $orderis) . ")")->rows;
        $this->printTaskInfo("obfuscating " . count($orders) . " orders");
        foreach ($orders as $order) {
            $total = $order['Total'] * 15;
            $created = Carbon::now()->subDays(0, 30);
            $paid = $created;
            $shipped = $created->addDays(3);
            $shipped = $shipped->lt(Carbon::now()) ? $shipped : '';
            $data = [
                'save_time' => $now,
                'label_print_time' => '',
                'etsy_order_id' => substr(sha1($order['etsy_order_id']), 0, strlen($order['etsy_order_id'])),
                'OrderID' => substr(sha1($order['OrderID']), 0, strlen($order['OrderID'])),
                'ExtendedOrderID' => substr(sha1($order['ExtendedOrderID']), 0, strlen($order['ExtendedOrderID'])),
                'BuyerUserID' => substr(sha1($order['BuyerUserID']), 0, strlen($order['BuyerUserID'])),
                'SellerUserID' => '',
                'EIASToken' => substr(sha1($order['EIASToken']), 0, strlen($order['EIASToken'])),
                'Total' => $total,
                'AmountPaid' => $total,
                'currencyID' => '',
                'PaymentMethods' => 'standard',
                'OrderStatus' => '',
                'eBayPaymentStatus' => '',
                'CheckoutLastModifiedTime' => '',
                'PaymentMethod' => 'standard',
                'CheckoutStatus' => '',
                'ShippingName' => 'John ' . substr(md5(rand()), 0, 1),
                'ShippingStreet1' => '421 DEMO STREET',
                'ShippingStreet2' => '',
                'ShippingCityName' => 'TUSCON',
                'ShippingStateOrProvince' => 'AZ',
                'ShippingCountry' => 'US',
                'ShippingCountryName' => 'UNITED STATES',
                'ShippingPhone' => '',
                'ShippingPostalCode' => '85705',
                'ShippingService' => 'standard',
                'ShippingServiceCost' => '',
                'SalesRecordNumber' => substr(sha1($order['SalesRecordNumber'] . time()), 0, strlen($order['SalesRecordNumber'])),
                'CreatedTime' => $created->toISO8601String(),
                'PaidTime' => $paid->toISO8601String(),
                'ShippedTime' => $shipped ? $shipped->toISO8601String() : '',
                'verified_addr_line1' => '',
                'verified_addr_line2' => '',
                'verified_addr_city' => '',
                'verified_addr_state' => '',
                'verified_addr_zip' => '',
                'verified_addr_country' => '',
                'verified_addr_message' => ''
            ];
            $db->query("update sl_orders set " . array_to_qstr($data) . " where OrderID={$order['OrderID']}");
        }
        // now export the final things
        return $this->exec("mysqldump -u root -p$mysql_root_pass $tmp_db > $tmp_sql"); //exported to the sql file
    }
    
    function exec($cmd) {
        if ($this->exec($cmd)) {
            return $this;
        }
    }

}
