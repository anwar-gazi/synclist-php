<?php

use Respect\Validation\Validator as v;

/**
 * Class ControllerListingItems
 * @property Modellistinglisting $model_listing_listing
 */
class ControllerListingItems extends Controller
{

    function index()
    {
        $this->load->model('listing/listing');
        $seller = $this->request->get['seller'];
        $listing_provider = $this->request->get['listing_provider'];

        $data = [];

        if (v::stringType()->validate($seller) and v::stringType()->validate($listing_provider)) {
            $data['seller'] = $seller;
            $data['listing_provider'] = $listing_provider;
            $data['items'] = $this->model_listing_listing->seller_items($seller, $listing_provider);
        }
        $this->response->setOutput($this->load->view('listing/listing.twig', $data));
    }

}
