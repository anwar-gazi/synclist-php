<?php

class ModelEbayUser extends Model {
    function all_usernames() {
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $users = $this->modules->data_ebay->item_dbrows();
        $names = array();
        foreach($users as $user) {
            $names[] = $user['SellerUserID'];
        }
        $names = array_filter($names);
        return array_unique($names);
    }
}