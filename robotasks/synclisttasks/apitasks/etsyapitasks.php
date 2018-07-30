<?php

class EtsyApiTasks
{
    use \ReflectionFacility;

    use \Robo\Common\TaskIO;
    use \Exec;

    private $api;
    private $provider = 'etsy';

    function __construct(SyncListApi $api)
    {
        $this->api = $api;
    }

    public function command__get_tracking_code($seller)
    {
        $provider = $this->provider;

        $api_keys = $this->api->api_keys($provider, $seller);

        /** @var EtsyApiClient $request */
        $request = $this->api->load->module('etsyapiclient', $this->api, $seller, $api_keys);

        $resp = $request->findAllShopReceipts([
            'shop_id' => $seller
        ]);

        if ($error = $resp->has_error()) {
            $this->printTaskError($error);
            return;
        }

        foreach ($resp->response->results as $entry) {
            if (!empty($entry->shipments)) {
                foreach ($entry->shipments as $shipment) {
                    $receipt_id = $entry->receipt_id;
                    $shipped = $entry->was_shipped;
                    $carrier = $shipment->carrier_name;
                    $tracking_code = $shipment->tracking_code;
                    $this->printTaskSuccess(($shipped ? '' : '***NOT SHIPPED***') . "receipt_id:$receipt_id carrier:$carrier tracking_code:$tracking_code");
                }
            }
        }
    }

    public function command__set_tracking_code($seller, $receipt_id, $tracking_code, $shipping_carrier)
    {
        $provider = $this->provider;

        $response = $this->api->RemoteProvider
            ->set_tracking_code($provider, $seller, $receipt_id, $tracking_code, $shipping_carrier);
        if ($response['success']) {
            $this->printTaskSuccess("success");
            $this->printTaskInfo($response['more']);
            return 0;
        } else {
            $this->printTaskError($response['error']);
            return 1;
        }
    }

    public function command__get_permission_scopes($seller)
    {

        $keys = $this->api->api_keys($this->provider, $seller);

        /** @var EtsyApiClient $request */
        $response = $this->api->load->module('etsyapiclient', $this->api, $seller, $keys)
            ->scopes();

        print_r($response->response);
    }
}