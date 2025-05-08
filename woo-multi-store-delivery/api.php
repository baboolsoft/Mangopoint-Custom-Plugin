<?php

function validateCheckout(WP_REST_Request $request)
{
    global $wpdb, $db;
    $place = $request->get_param('place');
    $pincode = $request->get_param('postcode');
    $table = $wpdb->prefix . 'wc_multi_store_product_data';

    $store_table = $wpdb->prefix . 'wc_multi_store_stores';
    $stores = $wpdb->get_results("SELECT `city`,`id`,`is_default` FROM $store_table");

    try {

        WC()->initialize_session();
        WC()->initialize_cart();

        $api_key = get_option('wc_multi_store_google_maps_api_key', '');
        $encoded_address = rawurlencode($place . ' ' . $pincode);
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=$encoded_address&key=$api_key";
        $city = null;

        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            return new WP_REST_Response([
                "status" => false,
                "error" => $response->get_error_message()
            ], 500);
        } else {
            $body = json_decode(wp_remote_retrieve_body($response));
            if ($body->status === 'OK' && count($body->results) > 0) {
                $location = $body->results[0];
                $city = str_replace(', Tamil Nadu, India', '', $location->formatted_address);
            }

            $res = [];
            foreach (WC()->cart->get_cart() as $cart_item) {
                $data = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM $table WHERE product_id = %d",
                        $cart_item['product_id']
                    ),
                    ARRAY_A
                );
                $product_store = $data['store_id'];
                $exist = false;
                $a = '';
                $b = '';
                $c = false;
                foreach ($stores as $store) {
                    if (($store->id == $product_store)) {
                        if (($store->is_default == 1) || (strpos($city, $store->city) !== false)) {
                            $exist = true;
                            $a = $store->id;
                            $b = $city;
                            $c = $store->is_default == 1 ? true : false;
                            $d = strpos($city, $store->city);
                        }
                    }
                }
                array_push($res, [
                    "product_id" => $cart_item['product_id'],
                    "store_id" => $product_store,
                    "exist" => $exist,
                    "a" => $a,
                    "b" => $b,
                    "c" => $c,
                    "d" => $d
                ]);
            }

            return new WP_REST_Response([
                "status" => true,
                "data" => $res
            ], 200);
            
        }
        return new WP_REST_Response([
            "status" => false,
            "error" => "Something went wrong!"
        ], 500);
    } catch (\Throwable $th) {
        return new WP_REST_Response([
            "status" => false,
            "error" => $th->getMessage()
        ], 500);
    }
}
