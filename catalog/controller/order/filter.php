<?php

/** @property Request $request */

/** @property SyncListApi $synclist_api */
class ControllerOrderFilter extends Controller
{

    function index()
    {
        $this->synclist_api->load_api('Listing');
        $this->synclist_api->load_api('Orders');

        $data = $this->load->_controller('common/layout')->context();

        /** custom values, add their support in the search module in frontend */
        $data['remote_providers'] = $this->synclist_api->Orders->listing_providers();
        $data['buyer_countries'] = array_filter($this->synclist_api->Orders->buyer_countries());
        $data['seller_usernames'] = ['Hanksminerals'];
        $data['order_statuses'] = ['success'];
        $data['order_local_status_options'] = json_encode($this->synclist_api->Orders->local_status_options());
        $data['payment_status_all'] = ['complete'];
        $data['items'] = $this->synclist_api->Listing->active_items_grouped();
        $data['shipping_services'] = $this->synclist_api->Orders->field_vals('ShippingService');
        $data['buyer_names'] = $this->synclist_api->Orders->field_vals('ShippingName');
        $data['shipping_dest_cities'] = $this->synclist_api->Orders->field_vals('ShippingCityName');
        $data['shipping_dest_states'] = $this->synclist_api->Orders->field_vals('ShippingStateOrProvince');
        $data['shipping_dest_countries'] = array_filter($this->synclist_api->Orders->field_vals('ShippingCountryName'));
        $data['payment_methods'] = $this->synclist_api->Orders->field_vals('PaymentMethod');

        $this->response->setOutput($this->load->twig('order/filter.twig', $data));
    }

}
