<?php

namespace resgef\synclist\cron\sellingstats;

use Carbon\Carbon;
use resgef\synclist\system\datatypes\localinventoryitem\LocalInventoryItem;
use resgef\synclist\system\interfaces\croninterface\CronInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class SellingStats extends \Controller implements CronInterface
{
    function execute()
    {
        $output = new ConsoleOutput();
        $output->writeln('<info>selling stats</info>');

        $stats = $this->build();

        if (!empty($stats)) {
            $statement = $this->pdo->prepare("UPDATE sl_local_inventory SET avg_sold_per_day=:avg_sold, etd_days_in_stock=:etd_days WHERE inventory_itemid=:invid");
            foreach ($stats as $invid => $stat) {
                $statement->bindParam(":avg_sold", $stat['daily_sold'], \PDO::PARAM_INT);
                $statement->bindParam(":etd_days", $stat['etd_days'], \PDO::PARAM_INT);
                $statement->bindParam(":invid", $invid);
                $statement->execute();
            }
        }
    }

    private function save()
    {
        $now = Carbon::now()->format('Y-m-d H:i:s');
        /** @var LocalInventoryItem[] $invitems */
        $invitems = $this->db->get_object("SELECT * FROM sl_local_inventory", LocalInventoryItem::class);
        foreach ($invitems as $invitem) {
            $statement = $this->db->pdo->pdo->prepare("INSERT INTO sl_sellingstats(inv_id, quantity, sold, create_date) VALUES(?,?,?,?)");
            $statement->bindColumn(1, $invitem->inventory_itemid);
            $statement->bindColumn(2, $invitem->quantity, \PDO::PARAM_INT);
            $statement->bindColumn(3, $invitem->sold, \PDO::PARAM_INT);
            $statement->bindColumn(4, $now);
            $statement->execute();
        }
    }

    private function build()
    {
        $dailysold_stats = [];
        /** @var \resgef\synclist\system\datatypes\localinventoryitem\LocalInventoryItem[] $invitems */
        $invitems = $this->db->get_object("SELECT * FROM sl_local_inventory", \resgef\synclist\system\datatypes\localinventoryitem\LocalInventoryItem::class)->rows;
        foreach ($invitems as $index => $invitem) {
            $invitem->dependency_injection($this->registry);
            $num_entry = $this->db->query("select count(*) as num from sl_sellingstats where inv_id='{$invitem->inventory_itemid}'")->row['num'];
            if ($num_entry) {
                $min_dt = $this->db->query("select MIN(create_date) as mindt from sl_sellingstats where inv_id='{$invitem->inventory_itemid}'")->row['mindt'];
                $max_dt = $this->db->query("select MAX(create_date) as maxdt from sl_sellingstats where inv_id='{$invitem->inventory_itemid}'")->row['maxdt'];
                $minsold = $this->db->query("select MIN(sold) as minsold from sl_sellingstats where inv_id='{$invitem->inventory_itemid}'")->row['minsold'];
                $maxsold = $this->db->query("select MAX(sold) as maxsold from sl_sellingstats where inv_id='{$invitem->inventory_itemid}'")->row['maxsold'];
                $duration_h = Carbon::createFromFormat('Y-m-d H:i:s', $max_dt)->diffInHours(Carbon::createFromFormat('Y-m-d H:i:s', $min_dt));
                $duration_d = $duration_h / 24;
                $daily_sold = ($maxsold - $minsold) / $duration_d;
                $etd_days = $invitem->balance > 0 ? ($daily_sold ? ($invitem->balance / ceil($daily_sold)) : $invitem->balance) : 0;
            } else {
                $daily_sold = 0;
                $etd_days = $invitem->balance;
            }
//            print("#{$invitem->inventory_itemid} balance {$invitem->balance} daily sold $daily_sold etd days $etd_days\n");
            $dailysold_stats[$invitem->inventory_itemid] = [
                'daily_sold' => ceil($daily_sold),
                'etd_days' => floor($etd_days)
            ];
        }
        return $dailysold_stats;
    }
}