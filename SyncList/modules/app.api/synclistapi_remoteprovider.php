<?php

use Guzzle\Http\Client;
use Guzzle\Plugin\Oauth\OauthPlugin;

class SyncListApiRemoteProvider
{

    private $api;

    function __construct(SyncListApi $api)
    {
        $this->api = $api;
    }

    public function set_tracking_code($remote_provider, $seller, $sales_rec, $tracking_code, $shipping_servc)
    {
        $ret = [
            'success' => false,
            'msg' => '',
            'error' => '',
            'more' => ''
        ];
        $remote_provider = \strtolower($remote_provider);
        if ($remote_provider == 'ebay') {

            $row = $this->api->db->query(
                "select OrderID,ShippedTime from {$this->api->table_name('orders')}"
                . " where SalesRecordNumber='$sales_rec'")->row;
            $OrderID = @$row['OrderID'];
            $shipped_time = @$row['ShippedTime'];
            if (!$OrderID) {
                $ret['success'] = false;
                $ret['error'] = "SalesRecordNumber $sales_rec didnt resolve an OrderID from local db";
                return $ret;
            }

            /** @var stdClass $api_keys */
            $api_keys = $this->api->api_keys($remote_provider, $seller);
            /** @var EbayApiRequest $request */
            $request = $this->api->load->module('ebayapirequest', 'hanksminerals', $api_keys);
            /** @var EbayApiRequestXmlBuilder $builder */
            $builder = $this->api->load->module('ebayapirequestxmlbuilder');
            /** @var SetShipmentTrackingInfoRequestXmlBuilder $req_xml_builder */
            $req_xml_builder = $builder->CompleteSale;

            $xml = $req_xml_builder->set_shipment_tracking($api_keys->userToken, $OrderID, $tracking_code, $shipping_servc, $shipped_time);

            $resp = '';
            for ($i = 1; $i <= 3; $i++) {
                $resp = $request->request('CompleteSale', $xml);
                if (!$resp && ($i < 3)) {
                    print("empty response, retry ...\n");
                } else {
                    break;
                }
            }

            $error = \EbayXmlHelper::check_error_node($resp);
            /** @var SimpleXMLElement $resp */
            $resp = \EbayXmlHelper::xmlstr_to_xmldom($resp);

            $errors = $error;
            $ack = empty($resp->Ack) ? '' : (string)$resp->Ack;
            $success = (($ack == 'Success') || ($ack == 'Warning')) ? true : false;

            $ret['success'] = $success;
            $ret['error'] = $errors;
            $ret['more'] = $errors ? "request\t" . htmlentities($xml) . "\nresponse\t" . htmlentities($resp->asXML()) : '';

            return $ret;
        } elseif ($remote_provider == 'etsy') {

            $keys = $this->api->api_keys('etsy', 'hanksminerals');
            $receipt_id = $sales_rec;

            /** @var EtsyApiClient $request */
            $request = $this->api->load->module('etsyapiclient', $this->api, $seller, $keys);
            $response = $request->submitTracking([
                'receipt_id' => $receipt_id,
                'tracking_code' => $tracking_code,
                'carrier_name' => $shipping_servc
            ]);

            $ret['success'] = $response->has_error() ? false : true;
            //$ret['msg'] = $ret['success'] ? sprintf("update success: receipt {$response->response->receipt_id} tracking_code {$response->response->shipments->tracking_code} tracking_url {$response->response->shipments->tracking_url}") : '';
            $ret['error'] = $response->has_error();
            $ret['more'] = $response->as_json();

            return $ret;
        }
    }

}
