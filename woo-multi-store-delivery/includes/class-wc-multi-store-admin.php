<?php

/**
 * Admin functionality
 */
class WC_Multi_Store_Admin
{
    /**
     * Initialize admin functionality
     */
    public function init()
    {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Register admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'register_admin_scripts'));

        // Add AJAX handlers for admin
        add_action('wp_ajax_wc_multi_store_save_store', array($this, 'ajax_save_store'));
        add_action('wp_ajax_wc_multi_store_delete_store', array($this, 'ajax_delete_store'));
        add_action('wp_ajax_wc_multi_store_update_product_inventory', array($this, 'ajax_update_product_inventory'));
        add_action('wp_ajax_wc_multi_store_save_api_key', array($this, 'ajax_save_api_key'));
        add_action('wp_ajax_wc_multi_store_update_all_products', array($this, 'ajax_update_all_products'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        // Main menu
        add_menu_page(
            __('Multi Store', 'wc-multi-store'),
            __('Multi Store', 'wc-multi-store'),
            'manage_woocommerce',
            'wc-multi-store',
            array($this, 'render_manage_stores_page'),
            'dashicons-store',
            56
        );

        // Manage Stores submenu
        add_submenu_page(
            'wc-multi-store',
            __('Manage Stores', 'wc-multi-store'),
            __('Manage Stores', 'wc-multi-store'),
            'manage_woocommerce',
            'wc-multi-store',
            array($this, 'render_manage_stores_page')
        );

        // Product Inventory submenu
        add_submenu_page(
            'wc-multi-store',
            __('Product Inventory', 'wc-multi-store'),
            __('Product Inventory', 'wc-multi-store'),
            'manage_woocommerce',
            'wc-multi-store-inventory',
            array($this, 'render_product_inventory_page')
        );

        // Google Map API Config submenu
        add_submenu_page(
            'wc-multi-store',
            __('Google Map API Config', 'wc-multi-store'),
            __('Google Map API Config', 'wc-multi-store'),
            'manage_woocommerce',
            'wc-multi-store-api-config',
            array($this, 'render_api_config_page')
        );
    }

    /**
     * Register admin scripts and styles
     */
    public function register_admin_scripts($hook)
    {
        // Only load on plugin pages
        if (strpos($hook, 'wc-multi-store') === false) {
            return;
        }

        // Register admin script
        wp_enqueue_script(
            'wc-multi-store-admin',
            WC_MULTI_STORE_PLUGIN_URL . 'assets/js/wc-multi-store-admin.js',
            array('jquery'),
            WC_MULTI_STORE_VERSION,
            true
        );

        // Localize script with data
        wp_localize_script('wc-multi-store-admin', 'wcMultiStoreAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_multi_store_admin_nonce'),
        ));

        // Register Google Maps API if on store management page
        if ($hook === 'toplevel_page_wc-multi-store') {
            $api_key = get_option('wc_multi_store_google_maps_api_key');
            if (!empty($api_key)) {
                wp_enqueue_script(
                    'google-maps',
                    'https://maps.googleapis.com/maps/api/js?key=' . $api_key . '&libraries=places',
                    array(),
                    null,
                    true
                );
            }
        }

        // Register admin styles
        wp_enqueue_style(
            'wc-multi-store-admin',
            WC_MULTI_STORE_PLUGIN_URL . 'assets/css/wc-multi-store-admin.css',
            array(),
            WC_MULTI_STORE_VERSION
        );
    }

    /**
     * Render Manage Stores page
     */
    public function render_manage_stores_page()
    {
        $db = new WC_Multi_Store_DB();
        $stores = $db->get_stores();

        include WC_MULTI_STORE_PLUGIN_DIR . 'templates/admin/manage-stores.php';
    }

    /**
     * Render Product Inventory page
     */
    public function render_product_inventory_page()
    {
        $db = new WC_Multi_Store_DB();
        $stores = $db->get_stores();

        // Get selected store
        $selected_store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;

        // Get products if store is selected
        $products = array();
        if ($selected_store_id > 0) {
            $args = array(
                'status' => 'publish',
                'limit' => -1,
            );
            $wc_products = wc_get_products($args);
            $product_count = wp_count_posts('product')->publish;

            foreach ($wc_products as $product) {
                $product_id = $product->get_id();

                if ($product->is_type('variable')) {
                    $variation_ids = $product->get_children();
                    foreach ($variation_ids as $variation_id) {
                        $variation = wc_get_product($variation_id);
                        if (isset($variation->get_attributes()["pa_size"])) {
                            array_push($products, [
                                ...$this->constructArr($product, $db->get_variant_product_data($selected_store_id, $product_id, $variation->get_attributes()["pa_size"])),
                                "quantity" => $variation->get_attributes()["pa_size"],
                            ]);
                        }
                    }
                } else {
                    $product_data = $db->get_product_data($selected_store_id, $product_id);
                    array_push($products, $this->constructArr($product, $product_data));
                }
            }
        }

        include WC_MULTI_STORE_PLUGIN_DIR . 'templates/admin/product-inventory.php';
    }

    public function constructArr($product, $product_data)
    {
        return [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'price' => isset($product_data['price']) ? $product_data['price'] : $product->get_price(),
            'stock' => isset($product_data['stock']) ? $product_data['stock'] : $product->get_stock_quantity(),
            'delivery_estimate' => isset($product_data['delivery_estimate']) ? $product_data['delivery_estimate'] : 1,
            'best_selling' => isset($product_data['best_selling']) ? $product_data['best_selling'] : false
        ];
    }

    /**
     * Render Google Map API Config page
     */
    public function render_api_config_page()
    {
        $api_key = get_option('wc_multi_store_google_maps_api_key', '');

        include WC_MULTI_STORE_PLUGIN_DIR . 'templates/admin/api-config.php';
    }

    /**
     * AJAX handler for saving store
     */
    public function ajax_save_store()
    {
        check_ajax_referer('wc_multi_store_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'wc-multi-store')));
        }

        $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $store_name = isset($_POST['store_name']) ? sanitize_text_field($_POST['store_name']) : '';
        $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
        $is_default = isset($_POST['is_default']) && $_POST['is_default'] === 'true';
        $isRestrict = isset($_POST['isRestrict']) && $_POST['isRestrict'] === 'true';
        $lat = isset($_POST['lat']) ? sanitize_text_field($_POST['lat']) : '';
        $lng = isset($_POST['lng']) ? sanitize_text_field($_POST['lng']) : '';

        $city = str_replace(', Tamil Nadu, India', '', $city);

        if (empty($store_name) || empty($city)) {
            wp_send_json_error(array('message' => __('Store name and city are required', 'wc-multi-store')));
        }

        $db = new WC_Multi_Store_DB();

        // Check if store name is unique
        if ($db->store_name_exists($store_name, $store_id)) {
            wp_send_json_error(array('message' => __('Store name already exists', 'wc-multi-store')));
        }

        // If this is set as default, unset any existing default
        if ($is_default) {
            $db->unset_default_store();
        }

        // Save store
        if ($store_id > 0) {
            $result = $db->update_store($store_id, $store_name, $city, $is_default, $isRestrict, $lat, $lng);
        } else {
            $result = $db->add_store($store_name, $city, $is_default, $isRestrict, $lat, $lng);
        }

        if ($result) {
            wp_send_json_success(array('message' => __('Store saved successfully', 'wc-multi-store')));
        } else {
            wp_send_json_error(array('message' => __('Failed to save store', 'wc-multi-store')));
        }
    }

    /**
     * AJAX handler for deleting store
     */
    public function ajax_delete_store()
    {
        check_ajax_referer('wc_multi_store_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'wc-multi-store')));
        }

        $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;

        if ($store_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid store ID', 'wc-multi-store')));
        }

        $db = new WC_Multi_Store_DB();
        $result = $db->delete_store($store_id);

        if ($result) {
            wp_send_json_success(array('message' => __('Store deleted successfully', 'wc-multi-store')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete store', 'wc-multi-store')));
        }
    }

    /**
     * AJAX handler for updating product inventory
     */
    public function ajax_update_product_inventory()
    {
        check_ajax_referer('wc_multi_store_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'wc-multi-store')));
        }

        $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
        $delivery_estimate = isset($_POST['delivery_estimate']) ? intval($_POST['delivery_estimate']) : 1;
        $best_selling = isset($_POST['best_selling']) && $_POST['best_selling'] === 'true';

        if ($store_id <= 0 || $product_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid store or product ID', 'wc-multi-store')));
        }

        $db = new WC_Multi_Store_DB();
        $result = $db->update_product_data($store_id, $product_id, $price, $stock, $delivery_estimate, $best_selling);

        if ($result) {
            wp_send_json_success(array('message' => __('Product inventory updated successfully', 'wc-multi-store')));
        } else {
            wp_send_json_error(array('message' => __('Failed to update product inventory', 'wc-multi-store')));
        }
    }

    /**
     * AJAX handler for saving API key
     */
    public function ajax_save_api_key()
    {
        check_ajax_referer('wc_multi_store_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'wc-multi-store')));
        }

        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';

        update_option('wc_multi_store_google_maps_api_key', $api_key);

        wp_send_json_success(array('message' => __('API key saved successfully', 'wc-multi-store')));
    }

    /**
     * AJAX handler for updating all products inventory
     */
    public function ajax_update_all_products()
    {

        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        check_ajax_referer('wc_multi_store_admin_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('Permission denied', 'wc-multi-store')));
        }

        $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $products = isset($_POST['products']) ? $_POST['products'] : array();

        if ($store_id <= 0 || empty($products)) {
            wp_send_json_error(array('message' => __('Invalid store ID or no products provided', 'wc-multi-store')));
        }

        $db = new WC_Multi_Store_DB();
        $success_count = 0;

        foreach ($products as $product) {
            $product_id = isset($product['id']) ? intval($product['id']) : 0;
            $price = isset($product['price']) ? floatval($product['price']) : 0;
            $quantity = isset($product['quantity']) ? ($product['quantity']) : "-";
            $stock = isset($product['stock']) ? intval($product['stock']) : 0;
            $delivery_estimate = isset($product['delivery_estimate']) ? intval($product['delivery_estimate']) : 1;
            $best_selling = isset($product['best_selling']) ? ($product['best_selling'] == "true") : false;

            if ($product_id > 0) {
                $result = $db->update_product_data($store_id, $product_id, $price, $stock, $delivery_estimate, $best_selling, $quantity);
                if ($result) {
                    $success_count++;
                }
            }
        }

        if ($success_count > 0) {
            wp_send_json_success(array('message' => sprintf(__('%d products updated successfully', 'wc-multi-store'), $success_count)));
        } else {
            wp_send_json_error(array('message' => __('Failed to update products', 'wc-multi-store')));
        }
    }
}
