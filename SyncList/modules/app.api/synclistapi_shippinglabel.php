<?php

class SyncListApiShippingLabel
{
    private $api;

    private $db;

    private $sqldate = 'Y-m-d G:i:s';

    function __construct(SyncListApi $api)
    {
        $this->api = $api;
        $this->pdo = $this->api->db->pdo->link;
    }

    public function delete_template($id) {
        $this->api->db->query("delete from sl_shipping_label_templates where id=$id");
        return $this->api->db->countAffected();
    }

    function save_template($id, $title, $html)
    {
        $response = [
            'id' => $id,
            'error' => ''
        ];
        if ($id) { //update
            $statement = $this->pdo->prepare("UPDATE sl_shipping_label_templates SET title=:title, html=:html,updated=:updated WHERE id=:id");
            $statement->execute([
                ':id' => $id,
                ':title' => $title,
                ':html' => $html,
                ':updated' => \Carbon\Carbon::now()->format($this->sqldate)
            ]);
            $countaffected = $statement->rowCount();
            $statement->closeCursor();
            if (!$countaffected) {
                $response['error'] = 'update fail';
            }
        } else { //insert
            $statement = $this->pdo->prepare("INSERT INTO sl_shipping_label_templates (title, html, created) VALUES (:title, :html, :created)");
            $statement->execute(
                [
                    ':title' => $title,
                    ':html' => $html,
                    ':created' => \Carbon\Carbon::now()->format($this->sqldate)
                ]
            );
            $statement->closeCursor();
            $response['id'] = $this->pdo->lastInsertId();
        }
        return $response;
    }

    public function get_list()
    {
        $statement = $this->pdo->prepare("SELECT * FROM sl_shipping_label_templates");
        $statement->execute();
        $res = $statement->fetchAll();
        $statement->closeCursor();
        return $res;
    }

    public function get_by_title($title)
    {
        return $this->api->db->query("select * from sl_shipping_label_templates where title='$title'")->row;
    }

    public function get_by_id($id)
    {
        return $this->api->db->query("select * from sl_shipping_label_templates where id=$id")->row;
    }

    public function compile($tpl_html, Array $representable_order)
    {
        $context = [
            'OrderNumber' => $representable_order['order_hash'],
            'ShipAddress' => $representable_order['shipping_address'],
            'EbaySKU' => '',
            'OrderItems' => call_user_func(function () use ($representable_order) {
                $trs = "";
                foreach ($representable_order['transactions'] as $transaction) {
                    $trs .= "<tr><td><img src='{$transaction['pic_url']}' title='{$transaction['pic_url']}'></td><td>{$transaction['Title']}</td><td>{$transaction['option']}</td><td>{$transaction['QuantityPurchased']}</td></tr>";
                }
                return "<table>$trs</table>";
            }),
            'BuyerNotes' => $representable_order['note'],
            'FilterTags' => call_user_func(function () use ($representable_order) {
                $tags_arr = call_user_func(function () use ($representable_order) {
                    $all_tags = [];
                    foreach ($this->api->Orders->in_which_filter($representable_order['id']) as $fid) {
                        $tags = $this->api->Orders->get_filter_tags($fid);
                        $all_tags = array_merge($all_tags, $tags);
                    }
                    return $all_tags;
                });
                return implode(',', $tags_arr);
            }),
        ];
        foreach ($context as $key => $val) {
            $tpl_html = str_replace("<$key>", $val, $tpl_html);
        }
        return $tpl_html;
    }

}