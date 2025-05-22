<?php
class orderHelper
{
    private $table_name = "";

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'multi_store_orders';
    }

    public function tableName()
    {
        return $this->table_name;
    }

    public function getOrderCount($storeId)
    {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(*) AS `total_order` FROM `$this->table_name` WHERE `store_id` = %d",
                $storeId
            )
        );
    }

    public function fetchList($store_id = 0)
    {
        global $wpdb;
        $results = $wpdb->get_results("SELECT `order_id` FROM {$this->table_name} WHERE `store_id`='$store_id' ORDER BY `id` DESC");
        $datas = [];
        foreach ($results as $item) {
            $order = wc_get_order($item->order_id);
            $order_date = $order->get_date_created(); // Returns WC_DateTime object

            if ($order_date) {
                $time = human_time_diff($order_date->getTimestamp(), current_time('timestamp')) . ' ago';
            }
            array_push($datas, [
                "id" => $order->get_id(),
                "name" => $order->get_formatted_billing_full_name(),
                "status" => $order->get_status(),
                "date" => $time,
                "total" => $order->get_total(),
                "method" => $order->get_payment_method_title()
            ]);
        }
        return $datas;
    }

    public function insertOrder($orderId, $storeId)
    {
        global $wpdb;
        $wpdb->insert($this->table_name, ['store_id' => "$storeId", 'order_id' => "$orderId"]);
    }

    public function udpateOrderStatus($orderId, $storeId, $status = 1)
    {
        global $wpdb;
        $wpdb->update(
            $this->table_name,
            ['status' => $status],
            ['store_id' => "$storeId", 'order_id' => "$orderId"]
        );
    }

    public function fetchOrderStatus($orderId, $storeId)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT `status` FROM {$this->table_name} WHERE `store_id`='$storeId' AND `order_id`='$orderId' "));
    }
}
