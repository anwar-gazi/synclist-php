<?php

namespace resgef\synclist\system\helper\ebayorder;

class EbayOrder
{
    static function is_cancelled($order_status)
    {
        if ($order_status == 'CancelPending') {
            return true;
        } else {
            return false;
        }
    }

    static function is_waiting_on_customer_response($order_status)
    {
        if (in_array($order_status, ['Active', 'Inactive'])) {
            return true;
        } else {
            return false;
        }
    }

    static function is_complete($order_status)
    {
        if ($order_status == 'Completed') {
            return true;
        } else {
            return false;
        }
    }
}
