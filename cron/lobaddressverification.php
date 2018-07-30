<?php

namespace ResGef\SyncList\Cron\LobAddressVerification;

use Lob\Lob;
use resgef\synclist\system\datatypes\lobavapikeysmodel\LobAVApiKeysModel;
use resgef\synclist\system\interfaces\croninterface\CronInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Class LobAddressVerification
 * @package ResGef\SyncList\Cron\LobAddressVerification
 * @property \Modellobavapikeys $model_lobav_apikeys
 */
class LobAddressVerification extends \Controller implements CronInterface
{
    function execute()
    {
        $output = new ConsoleOutput();
        $output->writeln("<info>Lob Address Verification</info>");

        $this->load->model('lobav/apikeys');

        /** @var LobAVApiKeysModel $api_keys */
        $api_keys = $this->model_lobav_apikeys->get();

        $lob = new Lob($api_keys->test);

        $count = $this->db->query("SELECT count(*) AS num FROM sl_orders WHERE verify_tried<>1 AND ShippingCountry='US'")->row['num'];
        $output->writeln("verification for $count shipping address required");
        $chunk = 1000;
        for ($index = 0; $index <= $count; $index += $chunk) {
            $required = $this->db->query("select * from sl_orders where verify_tried<>1 and ShippingCountry='US' limit $index,$chunk")->rows;
            foreach ($required as $i => $order) {
                $output->writeln(($index + $i + 1) . "/$count: order #{$order['OrderID']} shipping address verify request ... ");
                $verified_addr = [];
                $verified_addr['verify_tried'] = 1;
                try {
                    $resp = $lob->addresses()->verify([
                        'name' => $order['ShippingName'],
                        'address_line1' => $order['ShippingStreet1'],
                        'address_line2' => $order['ShippingStreet2'],
                        'address_city' => $order['ShippingCityName'],
                        'address_state' => $order['ShippingStateOrProvince'],
                        'address_zip' => $order['ShippingPostalCode'],
                        'address_country' => $order['ShippingCountry'],
                    ], 'intl_verifications');

                    $msg = @$resp['message'];
                    $verified_addr['verified_addr_line1'] = @$resp['address']['address_line1'];
                    $verified_addr['verified_addr_line2'] = @$resp['address']['address_line2'];
                    $verified_addr['verified_addr_city'] = @$resp['address']['address_city'];
                    $verified_addr['verified_addr_state'] = @$resp['address']['address_state'];
                    $verified_addr['verified_addr_zip'] = @$resp['address']['address_zip'];
                    $verified_addr['verified_addr_country'] = @$resp['address']['address_country'];

                    if ($msg) {
                        $output->writeln("message: $msg");
                    }
                } catch (\Exception $e) {
                    $msg = "cannot verify this address. " . $e->getMessage();
                    $output->writeln("<error>" . $e->getMessage() . "</error>");
                }

                $verified_addr['verified_addr_message'] = $msg;

                $sql_set_arr = [];
                foreach ($verified_addr as $key => $val) {
                    $val = $this->db->escape($val);
                    $sql_set_arr[] = "$key='$val'";
                }
                $sql_set = implode(',', $sql_set_arr);

                $this->db->query("update sl_orders set $sql_set where OrderID='{$order['OrderID']}'");
            }
        }
    }
}