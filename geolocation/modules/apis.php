<?php

function searchPlace(WP_REST_Request $request)
{
    $place = $request->get_param('place');

    global $wpdb;
    $query = $wpdb->prepare("SELECT * FROM `geo_location` WHERE id = 1");
    $result = $wpdb->get_row($query);

    if (isset($result->api)) {
        $api = base64_decode($result->api);
        $response = wp_remote_get("https://maps.googleapis.com/maps/api/place/textsearch/json?query=$place&key=$api");
        if (is_wp_error($response)) {
            return new WP_REST_Response([
                "status" => false
            ], 500);
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            return new WP_REST_Response([
                "status" => true,
                "data" => $data
            ], 200);
        }
    } else {
        return new WP_REST_Response([
            "status" => false
        ], 500);
    }
}

function renderDom($body, $target)
{
    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML($body);
    libxml_clear_errors();
    return $dom->getElementsByTagName($target);
}

function fetchProductInfo($id)
{
    $info = wc_get_product($id);

    if ($info) {

        $product_categories = get_the_terms($id, 'product_cat');
        $cat = [];
        foreach ($product_categories as $category) {
            array_push($cat, $category->term_id);
        }

        return [
            "id" => $id,
            "title" => $info->get_name(),
            "slug" => $info->get_permalink(),
            "rating" => wc_get_rating_html($info->get_average_rating()),
            "img" => get_the_post_thumbnail_url($id, 'shop_catalog'),
            "price" => $info->is_type('variable') ?
                '₹' . number_format($info->get_variation_price("min"), 2) . " - ₹" . number_format($info->get_variation_price("max"), 2) :
                '₹' . number_format($info->get_price(), 2),
            "rprice" => $info->get_price() == $info->get_regular_price() ? 0 : ('₹' . number_format((int)$info->get_regular_price(), 2)),
            "order" => (int)$info->get_menu_order(),
            "cat" => $cat,
            "sale" => $info->get_total_sales(),
            "type" => $info->get_type(),
            "status" => $info->get_stock_status()
        ];
    }
    return null;
}

function retrieveId($products, $pages = [], $lat, $lng)
{
    if ((count($pages) > 0)) {
        foreach ($pages as $page) {
            $response = wp_remote_get(site_url() . "/shop/page/$page/?orderby=date&radius_lat=$lat&radius_lng=$lng");
            if (!is_wp_error($response)) {
                $body = renderDom(wp_remote_retrieve_body($response), "li");
                foreach ($body as $ele) {
                    preg_match('/"id":"(\d+)"/', $ele->nodeValue, $matches);
                    if (isset($matches[1])) {
                        $info = fetchProductInfo($matches[1]);
                        if ($info != null) {
                            array_push($products, $info);
                        }
                    }
                }
            }
        }
    }

    return $products;
}

function getSortedProductListFromShop($url)
{

    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return [
            "status" => false,
        ];
    } else {

        $pages = [];
        $products = [];

        $body = renderDom(wp_remote_retrieve_body($response), "li");

        foreach ($body as $key => $ele) {
            preg_match('/"id":"(\d+)"/', $ele->nodeValue, $matches);
            if (isset($matches[1])) {
                $info = fetchProductInfo($matches[1]);
                if ($info != null) {
                    array_push($products, $info);
                }
            } else {
                foreach ($ele->childNodes as $child) {
                    if (
                        ($child->nodeType == XML_ELEMENT_NODE) &&
                        ($child->nodeName == "a") &&
                        ($child->getAttribute("class") == "page-numbers") &&
                        (strpos($child->getAttribute("href"), "/shop/page/") != false)
                    ) {
                        array_push($pages, $child->nodeValue);
                    }
                }
            }
        }

        $products = retrieveId($products, $pages, $lat, $lng);

        usort($products, function ($a, $b) {
            return $a['order'] <=> $b['order'];
        });

        return [
            "status" => true,
            "data" => $products
        ];
    }
}

function fetchProducts(WP_REST_Request $request)
{
    $lat = $request->get_param('latitude');
    $lng = $request->get_param('longitude');

    global $wpdb;
    $query = $wpdb->prepare("SELECT * FROM `geo_location` WHERE id = 1");
    $result = $wpdb->get_row($query);
    $slug = base64_decode($result->slug);

    if ($lat == 0 && $lng == 0) {
        $data = getSortedProductListFromShop(site_url() . "/store/$slug/");
    } else {
        $data = getSortedProductListFromShop(site_url() . "/shop?radius_range=15&orderby=date&radius_lat=$lat&radius_lng=$lng");
    }


    if (isset($data["status"]) && $data["status"] == true) {
        if (isset($data["data"]) && (count($data["data"]) > 0)) {
            return new WP_REST_Response($data, 200);
        } else {

            $result = getSortedProductListFromShop(site_url() . "/store/$slug/");
            if (isset($result["status"]) && $result["status"] == true) {
                if (isset($result["data"]) && (count($result["data"]) > 0)) {
                    $result["warning"] = "no-product";
                    return new WP_REST_Response($result, 200);
                }
            }
        }
    }
    return new WP_REST_Response([
        "status" => false
    ], 500);
}
