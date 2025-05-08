<?php

/**
 * Main plugin class
 */
class WC_Multi_Store
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Initialize properties
    }

    /**
     * Initialize the plugin
     */
    public function init()
    {
        // Initialize admin
        $admin = new WC_Multi_Store_Admin();
        $admin->init();

        // Initialize Elementor widgets
        $elementor = new WC_Multi_Store_Elementor();
        $elementor->init();

        // Initialize cart validation
        $cart = new WC_Multi_Store_Cart();
        $cart->init();

        // Register scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));

        // Add AJAX handlers
        add_action('wp_ajax_wc_multi_store_get_cities', array($this, 'ajax_get_cities'));
        add_action('wp_ajax_nopriv_wc_multi_store_get_cities', array($this, 'ajax_get_cities'));

        add_action('wp_ajax_wc_multi_store_get_products', array($this, 'ajax_get_products'));
        add_action('wp_ajax_nopriv_wc_multi_store_get_products', array($this, 'ajax_get_products'));

        // Handle order completion
        add_action('woocommerce_order_status_completed', array($this, 'update_store_inventory'), 10, 1);
    }

    /**
     * Register scripts and styles
     */
    public function register_scripts()
    {
        // Register main script
        wp_register_script(
            'wc-multi-store',
            WC_MULTI_STORE_PLUGIN_URL . 'assets/js/wc-multi-store.js',
            array('jquery'),
            WC_MULTI_STORE_VERSION,
            true
        );

        // Localize script with data
        wp_localize_script('wc-multi-store', 'wcMultiStoreData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wc_multi_store_nonce'),
            'google_maps_api_key' => get_option('wc_multi_store_google_maps_api_key'),
        ));

        // Register Google Maps API
        $api_key = get_option('wc_multi_store_google_maps_api_key');
        if (!empty($api_key)) {
            wp_register_script(
                'google-maps',
                'https://maps.googleapis.com/maps/api/js?key=' . $api_key . '&libraries=places',
                array(),
                null,
                true
            );
        }

        // Register styles
        wp_register_style(
            'wc-multi-store',
            WC_MULTI_STORE_PLUGIN_URL . 'assets/css/wc-multi-store.css',
            array(),
            WC_MULTI_STORE_VERSION
        );
    }

    /**
     * AJAX handler for getting cities
     */
    public function ajax_get_cities()
    {
        check_ajax_referer('wc_multi_store_nonce', 'nonce');

        $db = new WC_Multi_Store_DB();
        $cities = $db->get_store_cities();

        wp_send_json_success($cities);
    }

    /**
     * AJAX handler for getting products
     */
    public function ajax_get_products()
    {
        check_ajax_referer('wc_multi_store_nonce', 'nonce');
        $db = new WC_Multi_Store_DB();

        // parsing post datas
        $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
        $delivery_type = isset($_POST['delivery_type']) ? sanitize_text_field($_POST['delivery_type']) : '';
        $best_selling = isset($_POST['best_selling']) && $_POST['best_selling'] === 'true';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 12;

        // handling city selection if not exist
        if (empty($city)) {
            $default_city = $db->get_default_store_city();
            if (count($default_city) > 0) {
                $city = $default_city[0];
            } else {
                wp_send_json_error(array('message' => __('City is required - ', 'wc-multi-store')));
                return;
            }
        }

        // fetching store and handling error
        $store = $db->get_store_by_city($city);
        if (!$store) {
            wp_send_json_error(array('message' => __('No store found for the selected city for ' . $city, 'wc-multi-store')));
        }

        // sessioning store data
        WC()->session->set('store_data', [
            "id" => $store->id,
            "city" => $store->city
        ]);

        // Get products
        $products_data = $db->get_unique_products_by_delivery_type($store->id, $delivery_type, $best_selling, $per_page, $page, $city);
        wp_send_json_success($products_data);
    }

    /**
     * Update store inventory when an order is completed
     */
    public function update_store_inventory($order_id)
    {
        $order = wc_get_order($order_id);
        $shipping_city = $order->get_shipping_city();

        $db = new WC_Multi_Store_DB();
        $store = $db->get_store_by_city($shipping_city);

        if (!$store) {
            // If no store matches the shipping city, use the default store
            $store = $db->get_default_store();
        }

        if (!$store) {
            return;
        }

        // Update inventory for each product in the order
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $quantity = $item->get_quantity();

            // Get current stock
            $current_stock = $db->get_product_stock($store->id, $product_id);

            // Update stock
            if ($current_stock !== false && $current_stock > 0) {
                $new_stock = max(0, $current_stock - $quantity);
                $db->update_product_stock($store->id, $product_id, $new_stock);
            }
        }
    }
}
