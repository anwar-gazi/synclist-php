<?php

class Modelinventoryinventory extends Model
{
    /**
     * @param string $inv_id
     * @return \resgef\synclist\system\datatypes\localinventoryitem\LocalInventoryItem
     */
    function get_item($inv_id)
    {
        return $this->db->get_object("select * from sl_local_inventory where inventory_itemid='$inv_id'", \resgef\synclist\system\datatypes\localinventoryitem\LocalInventoryItem::class)->row;
    }

    /**
     * @return \resgef\synclist\system\datatypes\localinventoryitem\LocalInventoryItem[]
     */
    function items()
    {
        /** @var \resgef\synclist\system\datatypes\localinventoryitem\LocalInventoryItem[] $items */
        $items = $this->db->get_object("SELECT * FROM sl_local_inventory", \resgef\synclist\system\datatypes\localinventoryitem\LocalInventoryItem::class)->rows;
        foreach ($items as $inventoryItem) {
            $inventoryItem->dependency_injection($this->registry);
        }
        return $items;
    }

    /**
     * @param $inv_id
     * @return \resgef\synclist\system\datatypes\listingrow\ListingRow[]
     */
    function linked_items($inv_id)
    {
        return $this->db->get_object("SELECT * FROM sl_local_inventory_linked_item ln LEFT JOIN sl_listings ON ln.item_uniqid=sl_listings.uniqid where ln.inventory_itemid='$inv_id'", \resgef\synclist\system\datatypes\listingrow\ListingRow::class)->rows;
    }

    function update_quantity($inv_id, $new_quantity)
    {
        $this->pdo->exec("update sl_local_inventory set quantity='$new_quantity' WHERE inventory_itemid='$inv_id'");
    }

    function get_quantity($inv_id)
    {
        return $this->db->query("select quantity from sl_local_inventory where inventory_itemid='$inv_id'")->row['quantity'];
    }
}