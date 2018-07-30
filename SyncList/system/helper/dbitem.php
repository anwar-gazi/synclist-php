<?php

/*
 * helps working with items in database
 */

class DB_item {
    /*
     * compare a given collection of items(item object or item info array)
     * with respect to the items in database
     * and find the newer items in the given array
     * @param Arrar $ItemsArray, an array of items or items iterator.
     * each item may be an item object(EbayItem)
     * or iteminfo array having atleast itemid($item['itemid'])
     * return array only the nerer items in the given array
     */

    static function diff_items($ItemsArray) {
        if (is_object($ItemsArray) && (get_class($ItemsArray) == "EbayItemsIterator")) {
            $ItemsArray = $ItemsArray->collection();
        }
        $db_items_indexed = self::rows_indexed();

        if (empty($db_items_indexed)) {
            return $ItemsArray;
        }
        $ItemsArray = self::list_savable($ItemsArray);
        $new_items = array_filter($ItemsArray, function(EbayItem $Item) use ($db_items_indexed) {
            $key = $Item->itemid . "-" . $Item->sku;
            if (array_key_exists($key, $db_items_indexed)) {
                return false;
            } else {
                return true;
            }
        });
        return $new_items;
    }

    /*
     * find excess items in db which are not in the provided $Items array
     * @param Array $ItemsArray, array of EbayItem objects(with variations too)
     * @return array of db items(EbayItem object) that dont exist inside $ItemsArray
     */

    static function diff_db(Array $ItemsArray) {
        /*
         * to assure data types
         */
        $ItemsArray = array_filter($ItemsArray, function(EbayItem $Item) {
            return true;
        });

        /*
         * prepare for diff
         */
        $ItemsArray = self::list_savable($ItemsArray);
        $Items_indexed = self::indexed($ItemsArray);
        $db_items = self::rows();
        /*
         * now do the diff
         */
        $db_excess_items = array_filter($db_items, function(Array $item_info) use ($Items_indexed) {
            $key = $item_info['itemid'] . "-" . $item_info['sku'];
            if (array_key_exists($key, $Items_indexed)) {
                return false;
            } else {
                return true;
            }
        });
        /*
         * now convert item info to EbayItem object
         */
        $db_excess_items = array_map(function(Array $item_info) {
            $Item = new EbayItem($item_info);
            return $Item;
        }, $db_excess_items);

        return $db_excess_items;
    }

    /*
     * group existing items in database by last sales data fetch time
     * @return associative Array, salesdata_last_fetch_time as keys,
     * values are Item objects of that salesdata_last_fetch_time
     */

    static function group_by_lft() {
        $grouped = array();
        $items = self::ItemObj_all();
        foreach ($items as $Item) {
            $itemid = $Item->itemid;
            $lft = $Item->salesdata_last_fetch_time;
            $grouped[$lft] = [$Item];
        }
        return $grouped;
    }

    /*
     * @return plain Array of last fetch times of the items in database
     */

    static function last_fetch_time_array() {
        $group = self::group_by_lft();
        return array_keys($group);
    }

    /*
     * check if the database items salesdata last fetch times are same or not
     * if same then return that same time
     * if not then return false
     * @return mixed truthy/false: the same salesdata_last_fetch_time or false
     * if database has no items then null returned
     */

    static function last_fetch_time() {
        if (self::db_empty())
            return null;
        $grouped = self::group_by_lft();
        if (count($grouped) == 1) {
            return key($grouped);
        } else {
            return null;
        }
    }

    /*
     * get the itemid in an array for items in db
     */

    static function itemid_array() {
        return array_map(function($dbitem) {
            return $dbitem['itemid'];
        }, self::ebay_items());
    }

    static function ItemObj($itemid, $sku = '') {
        $info = self::row($itemid, $sku);
        $Item = new EbayItem($info);
        return $Item;
    }

    /**
     * get all db items as Item object(with variations)
     * @return Array(numeric indexed) of EbayItem object with unique itemid(variations omitted)
     * variations are added in the EbayItem object property
     */
    static function ItemObj_all() {
        $items = self::rows();

        $all_ItemObj_indexed = array();
        $variations = array();

        // two foreach loops: dirtyfix for item which itself is the only variation
        // index the items
        foreach ($items as $item_info) {
            $Item = new EbayItem($item_info);
            $all_ItemObj_indexed[$Item->itemid] = $Item;
        }
        // index the items
        foreach ($items as $item_info) {
            $Item = new EbayItem($item_info);
            if ($Item->is_variation) { // not variation, meta item
                $variations[$Item->itemid][] = $Item;
            }
        }

        // now add the variations
        /*
         * we doing this after because in the database, a variation row might be before the meta item row
         */
        foreach ($variations as $itemid => $VarArray) {
            $all_ItemObj_indexed[$itemid]->set_variations($VarArray);
        }

        /*
         * now removing the itemid index
         */
        $Items = array();
        foreach ($all_ItemObj_indexed as $Item) {
            $Items[] = $Item;
        };

        return $Items;
    }

    /*
     * all items info along with variations
     */

    static function iteminfo_all() {
        $items = self::rows();
        $items_indexed = array();

        // index the main items/meta items
        foreach ($items as $item) {
            $itemid = $item['itemid'];
            if (!$item['is_variation']) { // this is a meta item
                $items_indexed[$itemid] = $item;
            }
        }
        // now index the variations
        foreach ($items as $item) {
            $itemid = $item['itemid'];
            if ($item['is_variation']) { // this is a meta item
                if (empty($items_indexed[$itemid])) {
                    trigger_error("item variation without the meta item");
                }
                $items_indexed[$itemid]['variations'] = $item;
            }
        }

        return $items_indexed;
    }

    static function ebay_items() {
        return self::rows();
    }

    /*
     * get a db item
     * @param bool $array, return all matching item in an array, or only one iteminfo array
     */

    static function row($itemid, $sku = '') {
        if (!$itemid) {
            return null;
        }
        $items = self::rows($itemid, $sku);
        return reset($items);
    }

    /*
     * ge tall items matching,
     * @param string $itemid optional, if not provided then we return all items from the database
     * @return Array of items info entries in the database
     */

    static function rows($itemid = '', $sku = '') {
        if (!$itemid) {
            $sql = "SELECT * FROM " . EBAY_ITEMS_TABLE;
        } else {
            if (!$sku)
                $sku = '';
            $sql = "SELECT * FROM " . EBAY_ITEMS_TABLE . " WHERE itemid ='" . $itemid . "' AND sku='$sku'";
        }
        return DBH::query($sql)->rows;
    }

    static function num_rows($itemid = '', $sku = '') {
        $rows = self::rows($itemid, $sku);
        return count($rows);
    }

    static function count($itemid = '', $sku = '') {
        return self::num_rows($itemid, $sku);
    }

    /*
     * all db items indexed by itemid-sku combo
      @return array of item info array
     */

    static function rows_indexed() {
        $items = self::rows();
        $items_indexed = array();
        foreach ($items as $item_info) {
            $itemid = $item_info['itemid'];
            $sku = $item_info['sku'];
            $items_indexed["$itemid-$sku"] = $item_info;
        }
        return $items_indexed;
    }

    /*
     * all provided items indexed by itemid-sku combo
      @return array of item info array
     */

    static function indexed(Array $Items) {
        $items_indexed = array();
        foreach ($Items as $Item) {
            $itemid = $Item->itemid;
            $sku = $Item->sku;
            $items_indexed["$itemid-$sku"] = $Item;
        }
        return $items_indexed;
    }

    static function db_empty() {
        $items = self::rows();
        if (empty($items)) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * check the item exists in ddatabase already
     * @param string or object $itemid, either itemid or an EbayItem object
     * @param string $sku, provide f $itemid is really itemid
     * @return boolean
     */

    static function has_item($itemid, $sku = '') {
        if ($itemid instanceof EbayItem) {
            $Item = $itemid;
            $itemid = $Item->itemid;
            $sku = $Item->sku;
        }
        $items = self::rows($itemid, $sku);
        if (count($items)) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * if the provided Item has something new to update, and return the fieldname and values needed to be updated
     * @return array, fields needed to be updated, field name as key, and updated value as value
     */

    static function need_update(EbayItem $Item) {
        $fields_to_update = array();

        $info = self::row($Item->itemid, $Item->sku);
        foreach ($info as $key => $val) { // over the existing item info fields
            /*
             * yeah, we got some different value
             */
            if ($Item->$key && ($Item->$key != $val)) {
                $fields_to_update[$key] = $Item->$key;
            }
        }
        if (!empty($fields_to_update)) {
            return $fields_to_update;
        } else {
            return false;
        }
    }

    /*
     * build query array(field=>value) with properties of $Item which are in db already
     */

    static function __queryArray(EbayItem $Item) {
        $db = DBH::db();
        /*
         * now building query string
         */
        $field_names = self::field_names();
        $qarray = array();
        foreach ($field_names as $key) {
            /*
             * take $Item properties named same as in db, and it must have a value
             */
            if (property_exists($Item, $key)) {
                if ($key == 'db') {
                    continue;
                }
                $qarray[$key] = $Item->$key;
            }
        }
        return $qarray;
    }

    static function __queryStr(Array $qarray) {
        $db = DBH::db();
        $sql = '';
        foreach ($qarray as $field => $value) {
            if ($field == 'id')
                continue;
            $value = $db->escape($value);
            $sql .= ",$field='$value'";
        }
        return trim($sql, ",");
    }

    /*
     * delete multiple items along with their variations
     */

    static function delete(Array $Items) {
        $Items = array_filter($Items, function(EbayItem $Item) {
            return true;
        });
        $Items_all = array();
        foreach ($Items as $Item) {
            $Items_all[] = $Item;
            $Items_all = array_merge($Items_all, $Item->variations);
        }
        foreach ($Items_all as $Item) {
            self::delete_item($Item);
        }
    }

    /*
     * list the items and variations from inside the item objects in a plain array
     * example: if an item has 13 variations inside the item object, then the savable array will contain 14 items: the meta item+its varaitions
     */

    static function list_savable(Array $Items) {
        $savable = array();
        foreach ($Items as $Item) { // the varaitions from inside the item object
            $savable[] = $Item;
            $variations = self::savable_variations($Item);
            $savable = array_merge($savable, $variations);
        }
        return $savable;
    }

    /*
     * save multiple items, this also checks for variations and saves
     * @param array $not_saved, an array which will be flled by items faced errors in saving
     */

    static function save(Array $Items, &$not_saved = array()) {
        $savable = self::list_savable($Items);
        foreach ($savable as $Item) {
            $res = self::save_item($Item);
            if (!$res)
                $not_saved[] = $Item;
        }
        if (empty($not_saved)) {
            return true;
        } else {
            trigger_error(count($not_saved) . " items not saved");
            return false;
        }
    }

    /*
     * save only one item(not even the variations if inside the provided $Item)
     * inserts or updates depnding on existence in db
     * if update needed(item not exist in db) then only update the fields with updates(diffing with db)
     * @return if inserted, returns the last id generated by db
     * if updated, then returns the countAffected
     * raises error on save failure, of affect failure
     */

    static function save_item(EbayItem $Item) {
        if (!$Item->itemid) {
            trigger_error("no save: item doesnt have itemid");
        }
        $db = DBH::db();

        $qarray = self::__queryArray($Item);

        $new_entry = false;
        if (!self::has_item($Item->itemid, $Item->sku)) { // new entry
            $new_entry = true;
            print("saving new entry " . $Item->itemid . " sku:" . $Item->sku . "\n");

            $qstr = self::__queryStr($qarray);
            $sql = "INSERT INTO " . EBAY_ITEMS_TABLE . " SET $qstr";

            $db->query($sql);
            $response = $db->getLastId();
        } else { // existing, update
            print("updating item " . $Item->itemid . " sku:" . $Item->sku . "\n");
            /*
             * check and show if the update really needed
             */
            $fields_to_update = self::need_update($Item);
            if (empty($fields_to_update)) {
                print("update not needed for " . $Item->itemid . " " . $Item->sku . "\n");
                $response = true; // show it as saved
                return true;
            }

            $existingItem = self::row($Item->itemid, $Item->sku);

            //$qarray = array_filter($qarray); // remove the empty valued fields
            //$qstr = self::__queryStr($qarray);
            /*
             * only save the fields that were updated
             */
            $qstr = self::__queryStr($fields_to_update);

            $sql = "UPDATE " . EBAY_ITEMS_TABLE . " SET $qstr WHERE itemid ='" . $Item->itemid . "' AND sku='" . $Item->sku . "'";

            $db->query($sql);
            $response = $db->countAffected();
        }

        if (!$response) {
            trigger_error(
                    "itemid# " . $Item->itemid .
                    ",sku: " . $Item->sku .
                    ",title: " . substr($Item->title, 0, 15) . "...\n"
                    .
                    ($new_entry ? "insert fail" :
                            "update fail, needed for " . implode(",", array_keys($fields_to_update)))
            );
        }

        return $response;
    }

    /*
     * in many cases, the variations from inside an Item object doesnt have fields like itemid, title ... etc,
     * build an array of variation Item objects filling up these fields from the parent meta Item
     * @return array of variation item objects(if variations found)
     *
     */

    static function savable_variations(EbayItem $Item) {
        $replace = array(
            'itemid',
            'title',
            'pic_url',
            'item_url',
            'salesdata_last_fetch_time'
        );
        $Vars = array_map(function(EbayItem $Var) use ($Item, $replace) {
            foreach ($replace as $field) {
                if (!$Var->$field && $Item->$field) {
                    $Var->$field = $Item->$field;
                }
            }
            return $Var;
        }, $Item->variations);
        return $Vars;
    }

    static function delete_item(EbayItem $Item) {
        $db = DBH::db();
        if ($Item->id) {
            $qwhere = "id=" . $Item->id;
        } else {
            $qwhere = "itemid='" . $Item->itemid . "' AND sku='" . $Item->sku . "'";
        }
        $db->query("DELETE FROM " . EBAY_ITEMS_TABLE . " WHERE $qwhere");
        return $db->countAffected();
    }

}
