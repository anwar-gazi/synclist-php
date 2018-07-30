<?php

class SyncListApiSellingStats
{
    private $api;

    private $db;

    private $sqldate = 'Y-m-d G:i:s';

    function __construct(SyncListApi $api)
    {
        $this->api = $api;
        $this->db = $this->api->db->pdo;
    }

    /**
     * save current inventory stock figures
     */
    function save()
    {
        $now = \Carbon\Carbon::now()->format($this->sqldate);
        foreach ($this->api->LocalInventory->items() as $item) {
            $this->api->db->query("INSERT INTO sl_sellingstats SET inv_id='{$item['id']}', quantity='{$item['quantity']}', sold='{$item['sold']}', create_date='$now' ");
        }
    }

    function save_statsrow(Array $row)
    {
        $statement = $this->db->link->prepare("INSERT INTO sl_sellingstats (inv_id, quantity, sold, create_date) VALUES (:inv_id, :quantity, :sold, :create_date)");
        $statement->execute(
            [
                ':inv_id' => $row['id'],
                ':quantity' => $row['quantity'],
                ':sold' => $row['sold'],
                ':create_date' => \Carbon\Carbon::now()->format($this->sqldate)
            ]
        );
        return $this->db->link->lastInsertId();
    }

    function delete($inv_id, $qty)
    {
        $this->db->link->exec("delete from sl_sellingstats where inv_id='$inv_id' and `quantity`='$qty'");
    }

    function count()
    {
        return $this->db->query("SELECT count(inv_id) AS num FROM sl_sellingstats")->row['num'];
    }

    /**
     * build a statistics chart
     * @return array
     */
    function build()
    {
        $dailysold_stats = [];
        /** @var \resgef\synclist\system\datatypes\localinventoryitem\LocalInventoryItem[] $invitems */
        $invitems = $this->api->db->get_object("SELECT * FROM sl_local_inventory", \resgef\synclist\system\datatypes\localinventoryitem\LocalInventoryItem::class)->rows;
        foreach ($invitems as $invitem) {
            $invitem->dependency_injection($this->api);
            $dailysolds = [];
            /** @var \Carbon\Carbon $date */
            for ($date = \Carbon\Carbon::now(); ; $date = $date->subDay()) {
                if (\Carbon\Carbon::now()->diffInDays($date) > 7) {
                    break;
                }
                $date_str = $date->format('Y-m-d');
                $num_entry = $this->api->db->query("select count(*) as num from sl_sellingstats where DATE(create_date)='$date_str'")->row['num'];
                if ($num_entry) {
                    $minsold = $this->api->db->query("select MIN(sold) as minsold from sl_sellingstats where DATE(create_date)='$date_str'")->row['minsold'];
                    $maxsold = $this->api->db->query("select MAX(sold) as maxsold from sl_sellingstats where DATE(create_date)='$date_str'")->row['maxsold'];
                    $dailysolds[] = $maxsold - $minsold;
                }
            }
            $daily_sold = array_sum($dailysolds) / count($dailysolds);
            $etd_days = $invitem->balance / $daily_sold;
            $dailysold_stats[$invitem->inventory_itemid] = [
                'daily_sold' => floor($daily_sold),
                'etd_days' => floor($etd_days)
            ];
        }
        return $dailysold_stats;
    }

    public function monthly_selling()
    {
        $data = [];
        $orders = $this->api->db->query("SELECT CreatedTime, Total FROM sl_orders WHERE listing_provider='ebay' ORDER BY str_to_date(CreatedTime, '%Y-%m-%dT%H:%i:%s+%x') DESC")->rows;
        foreach (range(0, 11, 1) as $i) {
            $start = \Carbon\Carbon::now()->subMonths($i)->startOfMonth();
            $end = \Carbon\Carbon::now()->subMonths($i)->endOfMonth();
            $m_name = $start->format('F Y');
            $data[$m_name] = 0;

            foreach ($orders as $order) {
                $order_time = \Carbon\Carbon::createFromFormat(\Carbon\Carbon::ISO8601, $order['CreatedTime']);
                if ($order_time->gte($start) && $order_time->lte($end)) {
                    $data[$m_name] += $order['Total'];
                }
            }
        }
        return $data;
    }

}