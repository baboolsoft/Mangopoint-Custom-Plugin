<?php

class cartManager
{
    public function __construct()
    {
        add_action('woocommerce_order_status_changed', [$this, 'cartStatusChanger'], 10, 3);
    }

    public function cartStatusChanger($order_id, $old_status, $new_status)
    {
        // echo "workigin";exit;
        if (in_array($new_status, ["cancelled", "failed", "refunded", "order-returned"])) {

            global $wpdb;

            require_once STORE_PLUGIN_DIR . "admin/helpers/orders.php";
            $orderManager = new orderHelper();

            $orderStatus = $ins->fetchOrderStatus($orderId, $_SESSION["store"]["id"]);
            if ($orderStatus->status == "1") {
                $orderManager->udpateOrderStatus($orderId, $_SESSION["store"]["id"], 2);

                require_once STORE_PLUGIN_DIR . "admin/helpers/product.php";
                $product = new productHelper();

                $order = wc_get_order($order_id);

                foreach ($order->get_items() as $item) {
                    $product_id = $item->get_product_id();
                    $quantity = (int)$item->get_quantity();
                    $variation = $item->get_meta("variant_value");
                    $store_id = $item->get_meta("store_id");

                    $wpdb->query(
                        $wpdb->prepare(
                            "UPDATE {$product->tableName()} 
                         SET `stock` = `stock` + %d 
                         WHERE `store_id` = %d AND `product_id` = %d AND `quantity` = %s",
                            $quantity,
                            $store_id,
                            $product_id,
                            $variation
                        )
                    );
                }
            }
        }
    }
}
