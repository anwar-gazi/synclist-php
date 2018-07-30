<?php

class EtsyItemHelper {

    /**
     * stich togethether all attributes
     * @param array $variations
     * @return type
     */
    static function option_string(Array $variations) {
        $opt_arr = [];
        foreach ($variations as $variation) { //each attribute
            $opt_arr[] = "{$variation->formatted_name}:{$variation->formatted_value}";
        }
        $option = implode(',', $opt_arr);
        return strtolower($option);
    }

    static function uniqid($item_id) {
        return "etsy$item_id";
    }

    static function option_uniqid($item_id, Array $variations) {
        return self::uniqid($item_id) . "-" . substr(md5(self::option_string($variations)), 0, 8);
    }

}
