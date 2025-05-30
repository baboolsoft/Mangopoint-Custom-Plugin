<?php

class manager
{

    /**
     * Table names
     */
    private $store_table;
    private $product_table;
    private $config_table;
    private $fare_table;
    private $order_table;
    private $store_list;

    public function __construct()
    {
        require_once STORE_PLUGIN_DIR . '/admin/helpers/store.php';
        $store = new storeHelper();
        $this->store_table = $store->tableName();
        $this->store_list = $store->getStoreList();

        require_once STORE_PLUGIN_DIR . '/admin/helpers/config.php';
        $config = new configHelper();
        $this->config_table = $config->tableName();

        require_once STORE_PLUGIN_DIR . '/admin/helpers/product.php';
        $product = new productHelper();
        $this->product_table = $product->tableName();

        require_once STORE_PLUGIN_DIR . '/admin/helpers/fare.php';
        $fare = new fareHelper();
        $this->fare_table = $fare->tableName();

        require_once STORE_PLUGIN_DIR . '/admin/helpers/orders.php';
        $order = new orderHelper();
        $this->order_table = $order->tableName();

        add_action('admin_menu', [$this, 'menuList']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('rest_api_init', [$this, 'rest_api_routes']);
        add_filter('woocommerce_get_order_item_totals', [$this, 'remove_shipping'], 10, 3);

        add_action('woocommerce_order_status_changed', [$this, 'cartStatusChanger'], 10, 3);
    }

    // creating table
    public function create_table()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $store_sql = "CREATE TABLE IF NOT EXISTS {$this->store_table} (
            `id` mediumint(9) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `city` varchar(255) NOT NULL,
            `mail` varchar(255) NOT NULL,
            `is_default` tinyint(1) NOT NULL DEFAULT 0,
            `is_restrict` tinyint(1) NOT NULL DEFAULT 0,
            `lat` varchar(255) NOT NULL,
            `lng` varchar(255) NOT NULL,
            `payment_methods` varchar(255) NOT NULL DEFAULT '',
            `radius` mediumint(9) NOT NULL,
            `status` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (`id`),
            UNIQUE KEY name (`name`)
        ) $charset_collate;";

        $product_sql = "CREATE TABLE IF NOT EXISTS {$this->product_table} (
            `id` mediumint(9) NOT NULL AUTO_INCREMENT,
            `store_id` mediumint(9) NOT NULL,
            `product_id` mediumint(9) NOT NULL,
            `quantity` varchar(9) NOT NULL DEFAULT 0,
            `price` decimal(10,2) NOT NULL DEFAULT 0,
            `stock` int(11) NOT NULL DEFAULT 0,
            `order` mediumint(9) NOT NULL DEFAULT 0,
            `delivery_estimate` int(11) NOT NULL DEFAULT 1,
            `best_selling` tinyint(1) NOT NULL DEFAULT 0,
            `status` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (`id`),
            UNIQUE KEY store_product (`store_id`, `product_id`, `quantity`)
        ) $charset_collate;";

        $config_sql = "CREATE TABLE IF NOT EXISTS {$this->config_table} (
            `id` mediumint(9) NOT NULL AUTO_INCREMENT,
            `config_key` tinytext NOT NULL,
            `config_value` text NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (`id`)
        ) $charset_collate;";

        $fare_sql = "CREATE TABLE IF NOT EXISTS {$this->fare_table} (
            `id` mediumint(9) NOT NULL AUTO_INCREMENT,
            `store_id` mediumint(9) NOT NULL,
            `km` mediumint(9) NOT NULL,
            `type` varchar(15) NOT NULL,
            `fare` text NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (`id`),
            UNIQUE KEY store_product (`store_id`, `km`, `type`)
        ) $charset_collate;";

        $order_sql = "CREATE TABLE IF NOT EXISTS {$this->order_table} (
            `id` mediumint(9) NOT NULL AUTO_INCREMENT,
            `store_id` mediumint(9) NOT NULL,
            `order_id` mediumint(9) NOT NULL,
            `status` mediumint(9) NOT NULL DEFAULT '0',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (`id`),
            UNIQUE KEY unique_order (`store_id`, `order_id`)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($store_sql);
        dbDelta($product_sql);
        dbDelta($config_sql);
        dbDelta($fare_sql);
        dbDelta($order_sql);
    }

    // destroying table
    public function drop_table()
    {
        global $wpdb;

        $wpdb->query("DROP TABLE IF EXISTS {$this->store_table}");
        $wpdb->query("DROP TABLE IF EXISTS {$this->product_table}");
        $wpdb->query("DROP TABLE IF EXISTS {$this->config_table}");
    }

    // adding menu
    public function menuList()
    {
        // main menu
        add_menu_page(
            'Home | Multi Store Manager',
            'Multi Store Manager',
            'manage_options',
            'multi-store-manager',
            [$this, 'widgetContext'],
            'dashicons-store',
            6
        );

        $submenus = [
            [
                "title" => "Manage Store",
                "page_title" => "Manage Store | Multi Store Manager",
                "slug" => "multi-store-manager/manage-store",
                "callback" => [$this, 'managestoreList'],
            ],
            [
                "title" => "Configurations",
                "page_title" => "Configurations | Multi Store Manager",
                "slug" => "multi-store-manager/configurations",
                "callback" => [$this, 'manageConfig'],
            ],
            [
                "title" => "Product Inventory",
                "page_title" => "Product Inventory | Multi Store Manager",
                "slug" => "multi-store-manager/product-inventory",
                "callback" => [$this, 'manageProductInventory'],
            ],
            [
                "title" => "Shipping Fare",
                "page_title" => "Shipping Fare | Multi Store Manager",
                "slug" => "multi-store-manager/shipping-fare",
                "callback" => [$this, 'manageFare'],
            ],
            [
                "title" => "Orders",
                "page_title" => "Orders List | Multi Store Manager",
                "slug" => "multi-store-manager/orders-list",
                "callback" => [$this, 'manageOrders'],
            ]
        ];

        foreach ($this->store_list as $item) {
            array_push($submenus,[
                "title" => $item->name,
                "page_title" => $item->name." | Multi Store Manager",
                "slug" => "multi-store-manager/manage-".(strtolower(str_replace(" ", "-", $item->name)))."/".$item->id,
                "callback" => [$this, 'managestoreList'],
            ]);
        }

        foreach ($submenus as $submenu) {
            add_submenu_page(
                'multi-store-manager',
                $submenu['page_title'],
                $submenu['title'],
                'manage_options',
                $submenu['slug'],
                $submenu['callback']
            );
        }
    }

    public function widgetContext()
    {
        echo '<div class="wrap">
            <h1>Multi Store Manager</h1> <hr />
            <h2 style="line-height: 1.33;">
                Steps to configure before getting started with Multi Store Manager<br/>
                <small>This will prevent any conflicts with the plugin\'s functionality and ensure smooth operation.</small>
            </h2>
            <ul>
                <li>
                    <h4>Step 1: </h4>
                    <div style="padding-left: 12px">
                        Please ensure that stock management is disabled in the WooCommerce inventory settings.
                        <a href="' . admin_url() . 'admin.php?page=wc-settings&tab=products&section=inventory">Click Here!</a> for inventory setting page
                    </div>
                </li>
                <li>
                    <h4>Step 2: </h4>
                    <p style="padding-left: 12px">
                        Please ensure that all products are set to "In stock" status in WooCommerce.
                    </p>
                    <a style="padding-left: 12px" href="' . admin_url() . 'admin.php?page=wc-settings&tab=products&section=inventory">Click Here!</a> for inventory setting page
                </li>
            </ul>
        </div>';
    }

    public function managestoreList()
    {
        $pageSlug = isset($_GET['page']) ? $_GET['page'] : null;
        $storeId = null;
        $isStorePage = false;

        if (count(explode("/",$pageSlug)) > 2) {
            $storeId = explode("/",$pageSlug)[2];
        }
        
        $api_key = $this->fetchConfig('map');
        $store = new storeHelper();
        $list = $store->getStoreList();
        if ($pageSlug !== null && $storeId != null) {
            $isStorePage = true;
            $displayInfo = true;
            $storeInfo = $store->getStoreList($storeId);
        }

        require_once STORE_PLUGIN_DIR . '/admin/pages/shop-list.php';
    }

    public function manageProductInventory()
    {
        $storeId = $_GET['store_id'] ?? null;
        $productList = [];
        $isStorePage = (isset($_GET["store_info"]) && ($_GET["store_info"] == "true")) ? true : false;

        if ($storeId !== null) {
            require_once STORE_PLUGIN_DIR . '/admin/helpers/store.php';
            $store = new storeHelper();
            $products = new productHelper();
            $productList = $products->getProductList($storeId);
        }
        require_once STORE_PLUGIN_DIR . '/admin/pages/product-list.php';
    }

    public function manageFare()
    {
        $storeId = $_GET['store_id'] ?? null;
        $productList = [];
        $isStorePage = (isset($_GET["store_info"]) && ($_GET["store_info"] == "true")) ? true : false;

        if ($storeId !== null) {
            require_once STORE_PLUGIN_DIR . '/admin/helpers/store.php';
            require_once STORE_PLUGIN_DIR . '/admin/helpers/fare.php';
            $store = new storeHelper();
            $fare = new fareHelper();
            $fare_list = $fare->getFareList($storeId);
        }
        require_once STORE_PLUGIN_DIR . '/admin/pages/shipping-fare.php';
    }

    public function manageConfig()
    {
        $mapKey = $this->fetchConfig('map');
        $minPrice = $this->fetchConfig('minPrice');
        require_once STORE_PLUGIN_DIR . '/admin/pages/config.php';
    }

    public function manageOrders()
    {
        $storeId = $_GET['store_id'] ?? null;
        $isStorePage = (isset($_GET["store_info"]) && ($_GET["store_info"] == "true")) ? true : false;

        if ($storeId !== null) {
            require_once STORE_PLUGIN_DIR . '/admin/helpers/orders.php';
            $order = new orderHelper();
            $count = $order->getOrderCount($storeId)->total_order ?? 0;
            $list = $order->fetchList($storeId);
            require_once STORE_PLUGIN_DIR . '/admin/pages/orders.php';
        }
    }

    public function fetchConfig($key)
    {
        $config = new configHelper();
        return base64_decode($config->getConfig($key));
    }

    public function enqueue_assets()
    {
        $path = plugin_dir_url(__FILE__);
        $version = ASSET_VERSION;
        wp_enqueue_style('style', "{$path}assets/style.css", [], $version, 'all');
        wp_enqueue_script('script', "{$path}assets/script.js", ["jquery"], $version, true);
    }

    public function rest_api_routes()
    {
        require_once STORE_PLUGIN_DIR . '/admin/helpers/api.php';

        $routes = [
            ["slug" => "form-submit/", "callback" => "formSubmit"],
            ["slug" => "delete/", "callback" => "deleteItem"],
            ["slug" => "update-product-info/", "callback" => "updateProductInfo"],
            ["slug" => "sync-product/", "callback" => "updateProductInfo"]
        ];

        foreach ($routes as $key => $value) {
            register_rest_route('multi-store-manager/v1/api/', $value["slug"], [
                'methods' => $value["method"] ?? 'POST',
                'callback' => $value["callback"]
            ]);
        }
    }

    public function cartStatusChanger($order_id, $old_status, $new_status)
    {
        if (in_array($new_status, ["cancelled", "failed", "refunded", "order-returned"])) {

            global $wpdb;

            require_once STORE_PLUGIN_DIR . "admin/helpers/orders.php";
            $orderManager = new orderHelper();
            $orderList = wc_get_order($order_id);
            $storeId = null;

            foreach ($orderList->get_items() as $item) {
                if ($storeId == null) {
                    $storeId = $item->get_meta("store_id");
                    break;
                }
            }

            if ($storeId != null) {
                $orderStatus = $orderManager->fetchOrderStatus($order_id, $storeId);
                if ($orderStatus->status == "1") {
                    $orderManager->udpateOrderStatus($order_id, $storeId, 2);

                    foreach ($orderList->get_items() as $item) {
                        $product_id = $item->get_product_id();
                        $quantity = (int)$item->get_quantity();
                        $variation = $item->get_meta("variant_value");
                        $store_id = $item->get_meta("store_id");

                        $wpdb->query(
                            $wpdb->prepare(
                                "UPDATE {$this->product_table} 
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

    public function remove_shipping($totals, $order, $tax_display)
    {
        if (isset($totals['shipping'])) {
            unset($totals['shipping']);
        }
        return $totals;
    }
}