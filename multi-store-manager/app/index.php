<?php
class appManager
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('rest_api_init', [$this, 'rest_api_routes']);
    }

    public function rest_api_routes()
    {
        require_once STORE_PLUGIN_DIR . 'app/api.php';

        $routes = [
            ["slug" => "fetch-products/", "callback" => "retriveProducts"],
            ["slug" => "store-place/", "callback" => "sessionPlace"],
            ["slug" => "shipping-fare", "callback" => "calcualteFare"],
        ];

        foreach ($routes as $key => $value) {
            register_rest_route('multi-store-manager/v1/app/api/', $value["slug"], [
                'methods' => $value["method"] ?? 'POST',
                'callback' => $value["callback"]
            ]);
        }
    }

    function enqueue_assets()
    {
        $path = plugin_dir_url(__FILE__);
        $version = ASSET_VERSION;
        wp_enqueue_style('msm-style', "{$path}assets/style.css", [], $version, 'all');
        wp_enqueue_style('toaster', "{$path}assets/toaster/style.css", [], $version, 'all');
        wp_enqueue_style('popup', "{$path}assets/popup.css", [], $version, 'all');
        wp_enqueue_script('script', "{$path}assets/script.js", ["jquery"], $version, true);
        wp_enqueue_script('toaster', "{$path}assets/toaster/script.js", ["jquery"], $version, true);

        require_once STORE_PLUGIN_DIR . '/admin/helpers/config.php';
        $config = new configHelper();
        $api = base64_decode($config->getConfig("map"));
        $google_maps_url = "https://maps.googleapis.com/maps/api/js?key={$api}&libraries=places&callback=initMap";
        wp_enqueue_script('google-maps', $google_maps_url, [], null, true);
    }
}
