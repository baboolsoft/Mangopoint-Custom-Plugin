<?php

/**
 * Plugin Name: Multi Store Manager
 * Description: A custom plugin for managing multiple stores in wordpress.
 * Version: 1.0
 * Author: Baboolsoft
 */


if (! defined('ABSPATH')) {
    exit;
}

if (session_id() == '') {
    session_start();
}

define('STORE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ASSET_VERSION', "1.0");

$isRest = strpos($_SERVER['REQUEST_URI'], 'wp-json/multi-store-manager/v1/api') !== false;
$isAppRest = strpos($_SERVER['REQUEST_URI'], 'wp-json/multi-store-manager/v1/app/api') !== false;

if (is_admin() || $isRest) {
    require_once STORE_PLUGIN_DIR . 'admin/index.php';
    new manager();
}
if (!is_admin() || $isAppRest) {
    require_once STORE_PLUGIN_DIR . 'app/index.php';
    require_once plugin_dir_path(__FILE__) . 'app/cart-manager.php';
    require_once plugin_dir_path(__FILE__) . 'app/product-page-manager.php';
    new appManager();
    new cartManager();
    new productPageManager();
}
require_once plugin_dir_path(__FILE__) . 'app/widget.php';

function initManager()
{
    $adminIns = new manager();
    $adminIns->create_table();
}

function destroyManager()
{
    $adminIns = new manager();
    $adminIns->drop_table();
}

register_activation_hook(__FILE__, 'initManager');
// register_deactivation_hook(__FILE__, 'destroyManager');
register_uninstall_hook(__FILE__, 'destroyManager');
