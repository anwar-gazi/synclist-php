<?php

class LobAddressVerification extends SyncListModule
{

    /** @var SyncListApi */
    private $api;
    private $lob;

    function __construct(\SyncListKernel $kernel, \stdClass $config, $module_path)
    {
        parent::__construct($kernel, $config, $module_path);
        $this->api = $this->load->module('app.api');
        $this->lob = new \Lob\Lob('test_0b33eea95f3efc2afdb942c3c57df43cd14');
    }

    function run()
    {
        $this->lob = new \Lob\Lob('test_0b33eea95f3efc2afdb942c3c57df43cd14');

        $count = $this->api->db->query("select count(*) as num from {$this->api->table_name('orders')} where verify_tried<>1 and ShippingCountry='US'")->row['num'];
        print("verification for $count shipping address required\n");
        $chunk = 1000;
        for ($index = 0; $index <= $count; $index += $chunk) {
            $required = $this->api->db->query("select * from {$this->api->table_name('orders')} where verify_tried<>1 and ShippingCountry='US' limit $index,$chunk")->rows;

            foreach ($required as $i => $order) {
                print(($index + $i + 1) . "/$count: order #{$order['OrderID']} shipping address verify request ... \n");

                $verified_addr = [];
                $verified_addr['verify_tried'] = 1;
                try {
                    $resp = $this->lob->addresses()->verify([
                        'name' => $order['ShippingName'],
                        'address_line1' => $order['ShippingStreet1'],
                        'address_line2' => $order['ShippingStreet2'],
                        'address_city' => $order['ShippingCityName'],
                        'address_state' => $order['ShippingStateOrProvince'],
                        'address_zip' => $order['ShippingPostalCode'],
                        'address_country' => $order['ShippingCountry'],
                    ]);

                    $msg = @$resp['message'];
                    $verified_addr['verified_addr_line1'] = @$resp['address']['address_line1'];
                    $verified_addr['verified_addr_line2'] = @$resp['address']['address_line2'];
                    $verified_addr['verified_addr_city'] = @$resp['address']['address_city'];
                    $verified_addr['verified_addr_state'] = @$resp['address']['address_state'];
                    $verified_addr['verified_addr_zip'] = @$resp['address']['address_zip'];
                    $verified_addr['verified_addr_country'] = @$resp['address']['address_country'];

                    if ($msg) {
                        print("message: $msg\n");
                    }
                } catch (Exception $e) {
                    $msg = "cannot verify this address. ".$e->getMessage();
                    print("Error: " . $e->getMessage() . "\n");
                }

                $verified_addr['verified_addr_message'] = $msg;

                $sql_set_arr = [];
                foreach ($verified_addr as $key => $val) {
                    $val = $this->api->db->escape($val);
                    $sql_set_arr[] = "$key='$val'";
                }
                $sql_set = implode(',', $sql_set_arr);

                $this->api->db->query("update {$this->api->table_name('orders')} set $sql_set where OrderID='{$order['OrderID']}'");
            }
        }
    }

}
