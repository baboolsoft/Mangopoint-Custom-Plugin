<?php
class productHelper
{
    private $table_name = "";

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'multi_store_product_datas';
    }

    public function tableName()
    {
        return $this->table_name;
    }

    public function productData($product_id, $store_id, $quantity = '0')
    {
        global $wpdb;
        $product = $wpdb->get_row("SELECT * FROM {$this->table_name} WHERE `product_id` = '{$product_id}' AND `store_id` = '{$store_id}' AND `quantity` = '{$quantity}' LIMIT 1", ARRAY_A);
        return $product;
    }

    public function getProductList($storeId)
    {
        $products = array();

        $wc_products = wc_get_products(['status' => 'publish', 'limit' => -1]);
        foreach ($wc_products as $product) {
            $product_id = $product->get_id();

            if ($product->is_type('variable')) {
                $variation_ids = $product->get_children();
                foreach ($variation_ids as $variation_id) {
                    $variation = wc_get_product($variation_id);
                    if (isset($variation->get_attributes()["pa_size"])) {
                        $quantity = $variation->get_attributes()["pa_size"];
                        array_push($products, $this->productStruct($product, $this->productData($product_id, $storeId, $quantity), $storeId, $quantity));
                    }
                }
            } else {
                array_push($products, $this->productStruct($product, $this->productData($product_id, $storeId), $storeId));
            }
        }

        return $products;
    }

    public function productStruct($product, $product_data, $storeId, $quantity = '0')
    {
        return [
            'id' => $product->get_id(),
            'storeId' => $storeId,
            'name' => $product->get_name(),
            'price' => isset($product_data['price']) ? $product_data['price'] : $product->get_price(),
            'stock' => isset($product_data['stock']) ? $product_data['stock'] : ($product->get_stock_quantity()??0),
            'delivery_estimate' => isset($product_data['delivery_estimate']) ? $product_data['delivery_estimate'] : 1,
            'best_selling' => isset($product_data['best_selling']) ? $product_data['best_selling'] : 0,
            'order' => isset($product_data['order']) ? $product_data['order'] : 0,
            'variant' => $quantity,
            'status' => (int)isset($product_data['status']) ? $product_data['status'] : 0,
        ];
    }

    public function checkDelivery($product_id, $store_id, $quantity){
        global $wpdb;
        $product = $wpdb->get_row("SELECT `delivery_estimate` FROM {$this->table_name} WHERE `product_id` = '{$product_id}' AND `store_id` = '{$store_id}' AND `quantity` = '{$quantity}' LIMIT 1", ARRAY_A);
        return $product;
    }
}
