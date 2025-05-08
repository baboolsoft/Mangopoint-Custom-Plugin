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

    public function __construct()
    {
        require_once STORE_PLUGIN_DIR . '/admin/helpers/store.php';
        $store = new storeHelper();
        $this->store_table = $store->tableName();

        require_once STORE_PLUGIN_DIR . '/admin/helpers/config.php';
        $config = new configHelper();
        $this->config_table = $config->tableName();

        require_once STORE_PLUGIN_DIR . '/admin/helpers/product.php';
        $product = new productHelper();
        $this->product_table = $product->tableName();

        require_once STORE_PLUGIN_DIR . '/admin/helpers/fare.php';
        $fare = new fareHelper();
        $this->fare_table = $fare->tableName();

        add_action('admin_menu', [$this, 'menuList']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('rest_api_init', [$this, 'rest_api_routes']);
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
            `is_default` tinyint(1) NOT NULL DEFAULT 0,
            `is_restrict` tinyint(1) NOT NULL DEFAULT 0,
            `lat` varchar(255) NOT NULL,
            `lng` varchar(255) NOT NULL,
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

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($store_sql);
        dbDelta($product_sql);
        dbDelta($config_sql);
        dbDelta($fare_sql);
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
        </div>';
    }

    public function managestoreList()
    {
        $api_key = $this->fetchConfig('map');
        $store = new storeHelper();
        $list = $store->getStoreList();
        require_once STORE_PLUGIN_DIR . '/admin/pages/shop-list.php';
    }

    public function manageProductInventory()
    {
        $storeId = $_GET['store_id'] ?? null;
        $productList = [];

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
        $radius = $this->fetchConfig('radius');
        require_once STORE_PLUGIN_DIR . '/admin/pages/config.php';
    }

    public function manageOrders()
    {
        require_once STORE_PLUGIN_DIR . '/admin/pages/orders.php';
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
}
