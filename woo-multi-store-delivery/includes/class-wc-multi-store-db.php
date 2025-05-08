<?php

/**
 * Database functionality
 */
class WC_Multi_Store_DB
{
    /**
     * Table names
     */
    private $stores_table;
    private $product_data_table;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $wpdb;
        $this->stores_table = $wpdb->prefix . 'wc_multi_store_stores';
        $this->product_data_table = $wpdb->prefix . 'wc_multi_store_product_data';
    }

    /**
     * Create database tables
     */
    public function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $stores_table = "CREATE TABLE {$this->stores_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            city varchar(255) NOT NULL,
            is_default tinyint(1) NOT NULL DEFAULT 0,
            isRestrict tinyint(1) NOT NULL DEFAULT 0,
            lat varchar(255) NOT NULL,
            lng varchar(255) NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY name (name)
        ) $charset_collate;";

        $product_data_table = "CREATE TABLE {$this->product_data_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            store_id mediumint(9) NOT NULL,
            product_id mediumint(9) NOT NULL,
            quantity varchar(9) NOT NULL DEFAULT 0,
            price decimal(10,2) NOT NULL DEFAULT 0,
            stock int(11) NOT NULL DEFAULT 0,
            delivery_estimate int(11) NOT NULL DEFAULT 1,
            best_selling tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY store_product (store_id, product_id, quantity)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($stores_table);
        dbDelta($product_data_table);
    }

    /**
     * Drop database tables
     */
    public function drop_tables()
    {
        global $wpdb;

        $wpdb->query("DROP TABLE IF EXISTS {$this->stores_table}");
        $wpdb->query("DROP TABLE IF EXISTS {$this->product_data_table}");
    }

    /**
     * Get all stores
     */
    public function get_stores()
    {
        global $wpdb;

        return $wpdb->get_results("SELECT * FROM {$this->stores_table} ORDER BY name ASC");
    }

    /**
     * Get store by ID
     */
    public function get_store($store_id)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->stores_table} WHERE id = %d", $store_id));
    }

    /**
     * Get store by city
     */
    public function get_store_by_city($city)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->stores_table} WHERE city = %s", $city));
    }

    /**
     * Get default store
     */
    public function get_default_store()
    {
        global $wpdb;

        return $wpdb->get_row("SELECT * FROM {$this->stores_table} WHERE is_default = 1");
    }

    /**
     * Get all store cities
     */
    public function get_store_cities()
    {
        global $wpdb;

        return $wpdb->get_col("SELECT city FROM {$this->stores_table} ORDER BY city ASC");
    }

    // get only available cities
    public function get_avail_cities()
    {
        global $wpdb;

        return $wpdb->get_col("SELECT city FROM {$this->stores_table} WHERE isRestrict != 1 ORDER BY city ASC");
    }

    // get only restricted cities
    public function get_restricted_cities()
    {
        global $wpdb;

        return $wpdb->get_col("SELECT city FROM {$this->stores_table} WHERE isRestrict = 1 ORDER BY city ASC");
    }

    public function get_default_store_city()
    {
        global $wpdb;
        return $wpdb->get_col("SELECT `city` FROM {$this->stores_table} WHERE is_default = 1 limit 1");
    }

    public function get_default_store_city_info()
    {
        global $wpdb;
        return $wpdb->get_col("SELECT * FROM {$this->stores_table} WHERE is_default = 1 limit 1");
    }

    /**
     * Check if store name exists
     */
    public function store_name_exists($name, $exclude_id = 0)
    {
        global $wpdb;

        $query = "SELECT COUNT(*) FROM {$this->stores_table} WHERE name = %s";
        $args = array($name);

        if ($exclude_id > 0) {
            $query .= " AND id != %d";
            $args[] = $exclude_id;
        }

        return (int) $wpdb->get_var($wpdb->prepare($query, $args)) > 0;
    }

    /**
     * Add a new store
     */
    public function add_store($name, $city, $is_default = false, $isRestrict = false, $lat = 0, $lng = 0)
    {
        global $wpdb;

        return $wpdb->insert(
            $this->stores_table,
            array(
                'name' => $name,
                'city' => $city,
                'is_default' => $is_default ? 1 : 0,
                'isRestrict' => $isRestrict ? 1 : 0,
                'lat' => $lat,
                'lng' => $lng,
            ),
            array('%s', '%s', '%d', '%d', '%s', '%s')
        );
    }

    /**
     * Update a store
     */
    public function update_store($store_id, $name, $city, $is_default = false, $isRestrict = false, $lat = 0, $lng = 0)
    {
        global $wpdb;

        return $wpdb->update(
            $this->stores_table,
            array(
                'name' => $name,
                'city' => $city,
                'is_default' => $is_default ? 1 : 0,
                'isRestrict' => $isRestrict ? 1 : 0,
                'lat' => $lat,
                'lng' => $lng,
            ),
            array('id' => $store_id),
            array('%s', '%s', '%d', '%d', '%s', '%s'),
            array('%d')
        );
    }

    /**
     * Delete a store
     */
    public function delete_store($store_id)
    {
        global $wpdb;

        // Delete store
        $result = $wpdb->delete(
            $this->stores_table,
            array('id' => $store_id),
            array('%d')
        );

        // Delete associated product data
        if ($result) {
            $wpdb->delete(
                $this->product_data_table,
                array('store_id' => $store_id),
                array('%d')
            );
        }

        return $result;
    }

    /**
     * Unset default store
     */
    public function unset_default_store()
    {
        global $wpdb;

        return $wpdb->update(
            $this->stores_table,
            array('is_default' => 0),
            array('is_default' => 1),
            array('%d'),
            array('%d')
        );
    }

    /**
     * Get product data for a store
     */
    public function get_product_data($store_id, $product_id)
    {
        global $wpdb;

        $data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->product_data_table} WHERE store_id = %d AND product_id = %d",
                $store_id,
                $product_id
            ),
            ARRAY_A
        );

        return $data ? $data : array();
    }

    public function get_product_data_by_filter($store_id, $product_id, $delivery_type, $best_selling)
    {
        global $wpdb;

        $query = "SELECT * FROM {$this->product_data_table} WHERE store_id = '$store_id' AND product_id = '$product_id'";

        if ($delivery_type === 'same_day') {
            $query .= " AND delivery_estimate = 1";
        } else if ($delivery_type === 'estimated') {
            $query .= " AND delivery_estimate > 1";
        }

        if ($best_selling) {
            $query .= " AND pd.best_selling = 1";
        }

        $data = $wpdb->get_row($wpdb->prepare($query), ARRAY_A);

        return $data ? $data : array();
    }

    public function get_product_by_store($store_id, $delivery_type, $best_selling)
    {
        global $wpdb;

        $query = "SELECT * FROM {$this->product_data_table} WHERE store_id = '$store_id' ";

        if ($delivery_type === 'same_day') {
            $query .= " AND delivery_estimate = 1";
        } else if ($delivery_type === 'estimated') {
            $query .= " AND delivery_estimate > 1";
        }

        if ($best_selling) {
            $query .= " AND pd.best_selling = 1";
        }

        $data = $wpdb->get_row($wpdb->prepare($query), ARRAY_A);

        return $data ? $data : array();
    }

    public function get_variant_product_data($store_id, $product_id, $quantity)
    {
        global $wpdb;

        $data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->product_data_table} WHERE store_id = %d AND product_id = %d AND quantity = %s",
                $store_id,
                $product_id,
                $quantity
            ),
            ARRAY_A
        );

        return $data ? $data : array();
    }

    /**
     * Get Variant product price
     */
    public function get_variant_price($store_id, $product_id)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare( "SELECT MIN(price) AS min_price, MAX(price) AS max_price FROM {$this->product_data_table} WHERE store_id = %d AND product_id = %d",$store_id,$product_id)
        );
    }

    /**
     * Get product stock for a store
     */
    public function get_product_stock($store_id, $product_id)
    {
        global $wpdb;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT stock FROM {$this->product_data_table} WHERE store_id = %d AND product_id = %d",
                $store_id,
                $product_id
            )
        );
    }

    /**
     * Update product data for a store
     */
    public function update_product_data($store_id, $product_id, $price, $stock, $delivery_estimate, $best_selling, $quantity)
    {
        global $wpdb;

        $data = $this->get_product_data($store_id, $product_id);

        if (isset($quantity) && $quantity != '-') {
            $data = $this->get_variant_product_data($store_id, $product_id, $quantity);
        }

        if (empty($data)) {
            // Insert new record
            return $wpdb->insert(
                $this->product_data_table,
                array(
                    'store_id' => $store_id,
                    'product_id' => $product_id,
                    'price' => $price,
                    'stock' => $stock,
                    'delivery_estimate' => $delivery_estimate,
                    'best_selling' => $best_selling ? 1 : 0,
                    'quantity' => $quantity
                ),
                array('%d', '%d', '%f', '%d', '%d', '%d', '%s')
            );
        } else {
            // Update existing record
            return $wpdb->update(
                $this->product_data_table,
                array(
                    'price' => $price,
                    'stock' => $stock,
                    'delivery_estimate' => $delivery_estimate,
                    'best_selling' => $best_selling ? 1 : 0,
                ),
                array(
                    'store_id' => $store_id,
                    'product_id' => $product_id,
                    'quantity' => $quantity
                ),
                array('%f', '%d', '%d', '%d'),
                array('%d', '%d', '%s')
            );
        }
    }

    /**
     * Update product stock for a store
     */
    public function update_product_stock($store_id, $product_id, $stock)
    {
        global $wpdb;

        $data = $this->get_product_data($store_id, $product_id);

        if (empty($data)) {
            // Insert new record with default values
            return $wpdb->insert(
                $this->product_data_table,
                array(
                    'store_id' => $store_id,
                    'product_id' => $product_id,
                    'price' => 0,
                    'stock' => $stock,
                    'delivery_estimate' => 1,
                    'best_selling' => 0,
                ),
                array('%d', '%d', '%f', '%d', '%d', '%d')
            );
        } else {
            // Update existing record
            return $wpdb->update(
                $this->product_data_table,
                array('stock' => $stock),
                array(
                    'store_id' => $store_id,
                    'product_id' => $product_id,
                ),
                array('%d'),
                array('%d', '%d')
            );
        }
    }

    /**
     * Get products by delivery type and store
     */
    public function get_products_by_delivery_type($store_id, $delivery_type, $best_selling = false)
    {
        global $wpdb;

        $query = "SELECT p.* FROM {$this->product_data_table} pd
                  JOIN {$wpdb->posts} p ON pd.product_id = p.ID
                  WHERE pd.store_id = %d AND p.post_type = 'product' AND p.post_status = 'publish'";

        echo $query;
        exit;

        $args = array($store_id);

        // Filter by delivery type
        if ($delivery_type === 'same_day') {
            $query .= " AND pd.delivery_estimate = 1";
        } else if ($delivery_type === 'estimated') {
            $query .= " AND pd.delivery_estimate > 1";
        }

        // Filter by best selling
        if ($best_selling) {
            $query .= " AND pd.best_selling = 1";
        }

        // Filter by stock
        $query .= " AND pd.stock != -1";

        return $wpdb->get_results($wpdb->prepare($query, $args));
    }

    public function get_store_product_ids($store_id, $delivery_type, $best_selling = false)
    {
        global $wpdb;

        $query = "SELECT DISTINCT `product_id` FROM {$this->product_data_table} WHERE store_id = '$store_id' ";

        if ($delivery_type === 'same_day') {
            $query .= " AND delivery_estimate = 1";
        } else if ($delivery_type === 'estimated') {
            $query .= " AND delivery_estimate > 1";
        }

        if ($best_selling) {
            $query .= " AND pd.best_selling = 1";
        }

        return $wpdb->get_results($wpdb->prepare($query));
    }

    public function get_unique_products_by_delivery_type($store_id, $delivery_type, $best_selling = false, $limit = -1, $page = 1, $city = '')
    {
        global $wpdb;
        $db = new WC_Multi_Store_DB();
        $products = [];

        $product_ids = $db->get_store_product_ids($store_id, $delivery_type, $best_selling);
        $total = count($product_ids);
        $total_pages = ceil($total / $limit);
        $offset = ($page - 1) * $limit;
        $product_ids = array_slice($product_ids, $offset, $limit);

        foreach ($product_ids as $item) {
            $id = $item->product_id;
            $data = wc_get_product($id);

            if (!$data) {
                continue;
            }

            $store_data = $db->get_product_data($store_id, $id);

            if ($data->is_type('variable')) {
                $pr = $db->get_variant_price($store_id, $id);
                $price = $pr->min_price." - ".$pr->max_price;
                $price_html = wc_price($pr->min_price)." - ".wc_price($pr->max_price);
            } else {
                $price = isset($store_data['price']) ? $store_data['price'] : $data->get_price();
                $price_html = wc_price($price);
            }

            array_push($products, [
                'id' => $id,
                'name' => $data->get_name(),
                'permalink' => $data->get_permalink(),
                'price' => $price,
                'price_html' => $price_html,
                'image' => wp_get_attachment_image_url($data->get_image_id(), 'woocommerce_thumbnail') ?: wc_placeholder_img_src('woocommerce_thumbnail'),
                'stock' => isset($store_data['stock']) ? $store_data['stock'] : $data->get_stock_quantity(),
                'type' => $data->get_type(),
                'add_to_cart_url' => $data->add_to_cart_url(),
                "city" => $city
            ]);
        }

        return [
            'products' => $products,
            'total' => $total,
            'total_pages' => $total_pages
        ];
    }
}
