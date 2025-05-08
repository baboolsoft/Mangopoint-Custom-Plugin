<?php

/**
 * Plugin Name: Brix Value
 * Description: A simple plugin to add brix value to WooCommerce products.
 * Version: 1.0
 * Author: Baboolsoft
 */

if (! defined('ABSPATH')) {
    exit;
}

function init()
{
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $create_table = "CREATE TABLE `brix_config` (
            `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
            `minValue` INT NOT NULL,
            `maxValue` INT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($create_table);
}

function destroy()
{
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS `brix_config`");
}

require_once plugin_dir_path(__FILE__) . 'controllers/brix-value.php';
require_once plugin_dir_path(__FILE__) . 'controllers/admin.php';

register_activation_hook(__FILE__, 'init');
register_uninstall_hook(__FILE__, 'destroy');

$brixIns = new brixValue();
$brixAdmin = new admin();
