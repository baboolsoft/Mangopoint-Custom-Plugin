<?php
/**
 * Plugin Name: WooCommerce Multi-Store Delivery
 * Description: Manage multiple stores with different inventory, pricing, and delivery options
 * Version: 1.0.0
 * Author: Baboolsoft
 * Author URI: https://baboolsoft.com
 * Text Domain: wc-multi-store
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_MULTI_STORE_VERSION', '1.0.0');
define('WC_MULTI_STORE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_MULTI_STORE_PLUGIN_URL', plugin_dir_url(__FILE__));
require_once WC_MULTI_STORE_PLUGIN_DIR . 'api.php';
require_once WC_MULTI_STORE_PLUGIN_DIR . 'controller.php';

// Check if WooCommerce is active
function wc_multi_store_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'wc_multi_store_woocommerce_missing_notice');
        return false;
    }
    return true;
}

// Admin notice for missing WooCommerce
function wc_multi_store_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p><?php _e('WooCommerce Multi-Store Delivery requires WooCommerce to be installed and active.', 'wc-multi-store'); ?></p>
    </div>
    <?php
}

// Initialize the plugin
function wc_multi_store_init() {
    if (!wc_multi_store_check_woocommerce()) {
        return;
    }
    
    // Include required files
    require_once WC_MULTI_STORE_PLUGIN_DIR . 'includes/class-wc-multi-store.php';
    require_once WC_MULTI_STORE_PLUGIN_DIR . 'includes/class-wc-multi-store-admin.php';
    require_once WC_MULTI_STORE_PLUGIN_DIR . 'includes/class-wc-multi-store-db.php';
    require_once WC_MULTI_STORE_PLUGIN_DIR . 'includes/class-wc-multi-store-elementor.php';
    require_once WC_MULTI_STORE_PLUGIN_DIR . 'includes/class-wc-multi-store-cart.php';
    
    // Initialize the main plugin class
    $wc_multi_store = new WC_Multi_Store();
    $wc_multi_store->init();
}
add_action('plugins_loaded', 'wc_multi_store_init');

// Activation hook
register_activation_hook(__FILE__, 'wc_multi_store_activate');
function wc_multi_store_activate() {
    require_once WC_MULTI_STORE_PLUGIN_DIR . 'includes/class-wc-multi-store-db.php';
    $db = new WC_Multi_Store_DB();
    $db->create_tables();
    
    // Add default options
    add_option('wc_multi_store_google_maps_api_key', '');
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'wc_multi_store_deactivate');
function wc_multi_store_deactivate() {
    // Clean up transients and cached data
    delete_transient('wc_multi_store_cities');
}

// Uninstall hook
register_uninstall_hook(__FILE__, 'wc_multi_store_uninstall');
function wc_multi_store_uninstall() {
    require_once WC_MULTI_STORE_PLUGIN_DIR . 'includes/class-wc-multi-store-db.php';
    $db = new WC_Multi_Store_DB();
    $db->drop_tables();
    
    // Remove all options
    delete_option('wc_multi_store_google_maps_api_key');
    
    // Remove any other plugin data
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'wc_multi_store_%'");
}

// custom code begin

$instance = new controller();

add_action('rest_api_init', function () {
    // Register a new route
    register_rest_route('api/', 'validate-checkout/', [
        'methods' => 'POST',
        'callback' => 'validateCheckout',
    ]);
});
