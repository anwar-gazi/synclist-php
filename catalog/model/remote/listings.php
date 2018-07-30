<?php

class Modelremotelistings extends Model
{
    function index()
    {
        $ebay = array_map(function ($row) {
            return $row['account_name'];
        }, $this->db->query("SELECT account_name FROM sl_ebay_api_keys")->rows);
        $etsy = array_map(function ($row) {
            return $row['account_name'];
        }, $this->db->query("SELECT account_name FROM sl_etsy_api_keys")->rows);
        return [
            'ebay' => $ebay,
            'etsy' => $etsy
        ];
    }

    /**
     * @param string $seller
     * @param string $listing_provider
     * @return integer
     */
    function count_active_items($seller, $listing_provider)
    {
        return $this->db->query("SELECT count(*) as total_count FROM sl_listings where active=1 and listing_provider='$listing_provider' and seller='$seller'")->row['total_count'];
    }
}