<?php

class ModelEtsyUser extends Model {
    function all_usernames() {
        $names = array();
        if ($query = $this->db->query('SELECT seller FROM '.ETSY_ITEMS_TABLE)) {
            $users = $query->rows;
            $names = array();
            foreach($users as $user) {
                $names[] = $user['seller'];
            }
            $names = array_unique($names);
        }
        return $names;
    }
}