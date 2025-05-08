<?php
function defaultStore()
{
    global $wpdb;
    $store_table = tableName("store");

    $default_store = $wpdb->get_row("SELECT * FROM {$store_table} WHERE is_default = 1 LIMIT 1");

    if ($default_store) {
        return $default_store;
    } else {
        return false;
    }
}

function getNearestLocation($lat, $lng)
{
    global $wpdb;
    $store_table = tableName("store");
    $radius = getConfig("radius");

    $query = "SELECT *, ( 6371 * acos( cos( radians($lat) ) * cos( radians( `lat` ) ) * cos( radians( `lng` ) - radians($lng) ) + sin( radians($lat) ) * sin( radians( `lat` ) ) ) ) AS distance FROM {$store_table} WHERE `lat` != '' AND `lng` != '' AND `status` = '1' HAVING distance < $radius ORDER BY distance";

    $storeList = $wpdb->get_results($query);
    $selectedStore = defaultStore();

    foreach ($storeList as $store) {
        if ($store->is_restrict == "0") {
            $selectedStore = $store;
            break;
        } else if ($store->is_restrict == "1") {
            $distance = getDistanceInKm($lat, $lng, $store->lat, $store->lng);
            if ($store->radius >= $distance) {
                $selectedStore = $store;
                break;
            }
        }
    }

    return $selectedStore;
}

function uniqueIds($store_id)
{

    $product_table = tableName("product");
    global $wpdb;

    $query = "SELECT DISTINCT `product_id` FROM {$product_table} WHERE store_id = '$store_id' AND `status` = 1 ";
    return $wpdb->get_results($wpdb->prepare($query));
}

function productInfo($productId, $storeId, $detail = false)
{

    global $wpdb;

    $product = wc_get_product($productId);
    $product_table = tableName("product");

    $store_data = $wpdb->get_row($wpdb->prepare(
        "SELECT `store_id`,`price`,`stock`,`order`,`delivery_estimate`,`best_selling` FROM {$product_table} WHERE `product_id` = {$productId} AND `store_id` = {$storeId} AND `status` = 1"
    ));

    if ($product->is_type('variable')) {
        $pr = $wpdb->get_row(
            $wpdb->prepare("SELECT MIN(price) AS min_price, MAX(price) AS max_price FROM {$product_table} WHERE store_id = %d AND product_id = %d AND `status` = '1' ", $storeId, $productId)
        );
        $price = $pr->min_price . " - " . $pr->max_price;
        $price_html = wc_price($pr->min_price) . " - " . wc_price($pr->max_price);
        $stock = $wpdb->get_row(
            $wpdb->prepare("SELECT SUM(`stock`) as stockTotal FROM {$product_table} WHERE store_id = %d AND product_id = %d AND `status`='1' ", $storeId, $productId)
        )->stockTotal;
    } else {
        $price = isset($store_data->price) ? $store_data->price : $product->get_price();
        $price_html = wc_price($price);
        $stock = isset($store_data->stock) ? $store_data->stock : $product->get_stock_quantity();
    }

    $product_categories = get_the_terms($productId, 'product_cat');
    $cat = [];
    foreach ($product_categories as $category) {
        array_push($cat, [
            "id" => $category->term_id,
            "name" => $category->name
        ]);
    }

    $value = [
        'id' => $product->get_id(),
        'name' => $product->get_name(),
        'storeId' => $store_data->store_id,
        'slug' => $product->get_permalink(),
        "rating" => wc_get_rating_html($product->get_average_rating()),
        'price_html' => $price_html,
        'image' => wp_get_attachment_image_url($product->get_image_id(), 'woocommerce_thumbnail') ?: wc_placeholder_img_src('woocommerce_thumbnail'),
        'stock' => (int)$stock,
        "categories" => $cat,
        'order' => (int)$store_data->order,
        'type' => $product->get_type(),
        'option' => (int)$store_data->delivery_estimate == 1 ? 'same-day-delivery' : 'scheduled-delivery',
        'best_selling' => (int)$store_data->best_selling
    ];

    if ($detail) {
        $variants = [];
        if ($product->is_type('variable')) {
            $variation_ids = $product->get_children();
            foreach ($variation_ids as $variation_id) {
                $variation = wc_get_product($variation_id);
                if (isset($variation->get_attributes()["pa_size"])) {
                    array_push($variants, [
                        "title" => $variation->get_attributes()["pa_size"],
                    ]);
                }
            }
        }
        array_push($value, [
            "price" => $price,
            "variants" => $variants
        ]);
    }

    return $value;
}

function fetchProduct($storeId)
{
    $productIds = uniqueIds($storeId);

    $products = [];

    foreach ($productIds as $item) {
        $id = $item->product_id;
        array_push($products, productInfo($id, $storeId));
    }
    return $products;
}

function getConfig($key)
{
    global $wpdb;
    $table = tableName("config");
    $config = $wpdb->get_row($wpdb->prepare("SELECT `config_value` FROM {$table} WHERE `config_key` = %s", $key), ARRAY_A);
    return base64_decode($config["config_value"]) ?? "-";
}

function getPlaceInfo($address = null)
{
    require_once STORE_PLUGIN_DIR . '/admin/helpers/config.php';
    $config = new configHelper();

    if ($address != null) {
        $customer = WC()->customer;
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . ($address) . "&key=" . base64_decode($config->getConfig("map"));

        $response = file_get_contents($url);

        if ($response) {
            $data = json_decode($response);
            if ($data->status === 'OK') {
                return $data->results;
            }
        }
    }
    return [];
}

function getStoreInfo($storeId)
{
    require_once STORE_PLUGIN_DIR . '/admin/helpers/api.php';
    if ($storeId) {
        global $wpdb;
        $query = "SELECT * FROM `" . tableName(("store")) . "` WHERE id = '$storeId' ";
        return $wpdb->get_row($wpdb->prepare($query));
    }
    return null;
}

function getFareList($storeId)
{
    require_once STORE_PLUGIN_DIR . '/admin/helpers/api.php';
    if ($storeId) {
        global $wpdb;
        $query = "SELECT * FROM `" . tableName(("fare")) . "` WHERE `store_id` = '$storeId' ";
        return $wpdb->get_results($wpdb->prepare($query));
    }
    return null;
}

function calculateFare($storeId, $distance)
{
    require_once STORE_PLUGIN_DIR . '/admin/helpers/api.php';

    if ($storeId) {
        global $wpdb;
        $query = "SELECT * FROM `" . tableName(("fare")) . "` WHERE `store_id` = '$storeId' ";
        $fares = $wpdb->get_results($wpdb->prepare($query));
        $fare = 0;

        $withinMatches = array_filter($fares, function ($f) use ($distance) {
            return $f->type === 'with-in' && $distance <= (float)$f->km;
        });

        if (!empty($withinMatches)) {
            // Get the best match (lowest km that covers the distance)
            usort($withinMatches, fn($a, $b) => $a->km <=> $b->km);
            $fare = $withinMatches[0]->fare;
        } else {
            // 2. Try to match the lowest "more-than" rule
            $moreThanMatches = array_filter($fares, function ($f) use ($distance) {
                return $f->type === 'more-than' && $distance > (float)$f->km;
            });

            if (!empty($moreThanMatches)) {
                usort($moreThanMatches, fn($a, $b) => $a->km <=> $b->km);
                $fare = $moreThanMatches[0]->fare;
            }
        }
        return $fare;
    }
    return 0;
}

function getDistanceInKm($lat1, $lon1, $lat2, $lon2)
{
    $earthRadius = 6371; // Radius of the Earth in km

    // Convert degrees to radians
    $lat1 = deg2rad($lat1);
    $lon1 = deg2rad($lon1);
    $lat2 = deg2rad($lat2);
    $lon2 = deg2rad($lon2);

    // Haversine formula
    $dlat = $lat2 - $lat1;
    $dlon = $lon2 - $lon1;

    $a = sin($dlat / 2) * sin($dlat / 2) +
        cos($lat1) * cos($lat2) *
        sin($dlon / 2) * sin($dlon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earthRadius * $c;

    return $distance;
}

function can_store_allow_order($lat, $lng, $storeId = null)
{
    if ($storeId == null) {
        if (isset($_SESSION["store"])) {
            $storeId = $_SESSION["store"]->id;
        } else {
            return false;
        }
    }

    $store = getStoreInfo($storeId);

    
    if ($store->is_default == "1") {
        return true;
    } else {
        $distance = getDistanceInKm($lat, $lng, $store->lat, $store->lng);
        if ($store->radius >= $distance) {
            return true;
        }
    }
    return false;
}
