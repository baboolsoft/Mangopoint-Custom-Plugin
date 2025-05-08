<?php

/**
 * Plugin Name: Geo Location
 * Description: A custom plugin for filtering products by location.
 * Version: 1.0
 * Author: Baboolsoft
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'modules/widget/index.php';
require_once plugin_dir_path(__FILE__) . 'modules/controller.php';
require_once plugin_dir_path(__FILE__) . 'modules/apis.php';

$adminIns = new controller();

function initGeo()
{
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $create_table = "CREATE TABLE `geo_location` (
            `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
            `api` TEXT NOT NULL,
            `slug` TEXT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($create_table);
}

function destroyGeo()
{
    global $wpdb;
    echo "destroying";
    exit;
    $wpdb->query("DROP TABLE IF EXISTS `geo_location`");
}

add_action('rest_api_init', function () {
    // Register a new route
    register_rest_route('api/', 'search-place/', [
        'methods' => 'POST',
        'callback' => 'searchPlace',
    ]);
    register_rest_route('api/', 'products/', [
        'methods' => 'POST',
        'callback' => 'fetchProducts',
    ]);
});

add_action('widgets_init', 'initWidget');

register_activation_hook(__FILE__, 'initGeo');
register_uninstall_hook(__FILE__, 'destroyGeo');
