<?php

class controller
{
    public function __construct()
    {
        if (function_exists('is_plugin_active')) {
            add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        }
    }

    function enqueue_assets()
    {
        $path = plugin_dir_url(__FILE__);
        wp_enqueue_style('style', "{$path}/assets/css/style.css", [], '1.0', 'all');
        wp_enqueue_script('script', "{$path}/assets/js/script.js", ["jquery"], '1.0', true);
    }
}
