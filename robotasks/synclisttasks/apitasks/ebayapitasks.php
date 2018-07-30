<?php

class EbayApiTasks
{

    use \ReflectionFacility;

    use \Robo\Common\TaskIO;
    use \Exec;

    private $api;
    private $provider = 'ebay';

    function __construct(SyncListApi $api)
    {
        $this->api = $api;
    }

    public function command__set_tracking_code($seller, $sales_rec, $tracking_code, $shipping_carrier)
    {
        return $this->api->RemoteProvider
            ->set_tracking_code($this->provider, $seller, $sales_rec, $tracking_code, $shipping_carrier);
    }

    public function command__get_tracking_code($seller, $orderid, $sales_rec)
    {

        $api_keys = $this->api->api_keys($this->provider, $seller);

        if (!$orderid && $sales_rec) {
            $orderid = @$this->api->db->query("select OrderID from {$this->api->table_name('orders')} where SalesRecordNumber='$sales_rec'")->row['OrderID'];
            if (!$orderid) {
                $this->printTaskError("sales record number $sales_rec not found in database");
                return 1;
            }
        }

        $row = $this->api->db->query("select * from {$this->api->table_name('transactions')} where OrderID='{$orderid}'")->row;
        $orderline_itemid = @$row['OrderLineItemID'];
        $itemid = @$row['ItemID'];
        if (!$orderline_itemid) {
            $this->printTaskError("orderid $orderid's line itemid not found in local db'");
            return 1;
        }

        $request = $this->api->load->module('ebayapirequest', $seller, $api_keys);

        $xml = $this->api->load->module('ebayapirequestxmlbuilder')
            ->GetItemTransactions->item_transactions($itemid, $api_keys->userToken, 1);

        $resp = $request->request('GetItemTransactions', $xml);
        file_put_contents(__DIR__ . '/resp.xml', $resp);

        $error = EbayXmlHelper::check_error_node($resp);

        if ($error) {
            $this->printTaskError($error);
            return 1;
        }

        $resp = EbayXmlHelper::xmlstr_to_xmldom($resp);

        /** @var  $trans SimpleXMLElement */
        foreach ($resp->TransactionArray->Transaction as $trans) {
            if (strpos($trans->asXML(), 'ShipmentTrackingNumber') !== FALSE) {
                //$trans_id = (string)$trans->TransactionID;
                $order_id = (string)$trans->ContainingOrder->OrderID;
                $carrier = (string)$trans->ShippingDetails
                    ->ShipmentTrackingDetails->ShippingCarrierUsed;
                $tracking_code = (string)$trans->ShippingDetails
                    ->ShipmentTrackingDetails->ShipmentTrackingNumber;
                //$created = (string)$trans->CreatedDate;
                $shipped = (string)$trans->ShippedTime;

                $this->printTaskSuccess(
                    ($shipped ? '' : '***NOT SHIPPED***')
                    . "OrderID:$order_id carrier:$carrier "
                    . "ShipmentTrackingNumber:$tracking_code");
            }
        }
    }

}