<?php

require_once STORE_PLUGIN_DIR . '/admin/helpers/api.php';
require_once STORE_PLUGIN_DIR . 'app/helper.php';

$product_table = tableName("product");
$store_table = tableName("store");
$config_table = tableName("config");

function retriveProducts(WP_REST_Request $request)
{
    $lat = $request->get_param('latitude');
    $lng = $request->get_param('longitude');

    // ini_set('display_errors', 1);
    //     ini_set('display_startup_errors', 1);
    //     error_reporting(E_ALL);

    if ($lat == 0 && $lng == 0) {
        $store = defaultStore();
    } else {
        $store = getNearestLocation($lat, $lng);
    }

    WC()->initialize_session();
    WC()->session->set('location_data', [
        "store_id" => $store->id,
        "lat" => $lat,
        "lng" => $lng
    ]);
    $_SESSION["location_data"] = [
        "store_id" => $store->id,
        "lat" => $lat,
        "lng" => $lng
    ];

    if (isset($store->id)) {
        return new WP_REST_Response([
            "status" => true,
            "products" => fetchProduct($store->id),
            "store" => $store
        ], 200);
    }

    return new WP_REST_Response([
        "status" => false
    ], 500);
}

function sessionPlace(WP_REST_Request $request)
{
    $lat = $request->get_param("latitude");
    $lng = $request->get_param("longitude");

    if ($lat == 0 && $lng == 0) {
        $store = defaultStore();
    } else {
        $store = getNearestLocation($lat, $lng);
    }

    WC()->initialize_session();
    WC()->session->set('store_data', [
        "id" => $store->id,
        "city" => $store->city
    ]);

    $_SESSION["store"] = [
        "id" => $store->id,
        "city" => $store->city
    ];

    echo json_encode(WC()->session->get('store_data'));
    exit;
}

function calcualteFare(WP_REST_Request $request)
{

    require_once STORE_PLUGIN_DIR . '/app/helper.php';
    $data = getPlaceInfo($request->get_param("pincode"));
    $fare = 0;
    $enable_order = false;
    $reason = "";

    if (count($data) > 0) {
        $location = $data[0]->geometry->location;

        $distance = getDistanceInKm($store->lat, $store->lng, $location->lat, $location->lng);
        $nearestStore = getNearestLocation($location->lat, $location->lng);
        $store = getStoreInfo($nearestStore->id);
        $_SESSION["store"] = [
            "id" => $nearestStore->id,
            "city" => $nearestStore->city
        ];
        $fare = calculateFare($nearestStore->id, getDistanceInKm($store->lat, $store->lng, $location->lat, $location->lng));

        if (($store->is_restrict == '0') || ($store->is_restrict == '1' && $store->radius > $distance)) {
            $enable_order = true;
        } else if ($store->is_restrict == '1') {
            $reason = "Out of radius";
        }
    }

    return new WP_REST_Response([
        "status" => true,
        "fare" => (int)$fare,
        "fare_html" => wc_price($fare),
        "enable_order" => $enable_order,
        "reason" => $reason,
        "locationInfo" => [
            "latitude" => (float)$location->lat,
            "longitude" => (float)$location->lng,
            "pincode" => $request->get_param("pincode")
        ]
    ], 200);
}
