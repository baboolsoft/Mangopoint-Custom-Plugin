<?php

function formSubmit(WP_REST_Request $request)
{
    global $wpdb;
    $manage = $request->get_param('manage');

    if ($manage == "config") {
        $table = tableName("config");
        $datas = $request->get_param('data');

        foreach ($datas as $key => $value) {
            $query = $wpdb->prepare("SELECT * FROM `$table` WHERE `config_key` = '$key' ");
            $result = $wpdb->get_row($query);
            $val = [
                'config_key' => $key,
                'config_value' => base64_encode($value)
            ];
            if (empty($result)) {
                $wpdb->insert($table, $val);
            } else {
                $wpdb->update($table, $val, ['config_key' => $key]);
            }
        }
        return new WP_REST_Response([
            "status" => true,
            "message" => "Configuration updated successfully"
        ], 200);
    } else if ($manage == "store") {

        $table = tableName("store");
        $val = [
            "name" => $_POST["name"],
            "city" => $_POST["city"],
            "is_default" => (int)$_POST["default"],
            "is_restrict" => (int)$_POST["restrict"],
            "lat" => $_POST["lat"],
            "lng" => $_POST["lng"],
            "radius" => $_POST["radius"],
            "status" => $_POST["status"]
        ];

        if (isset($_POST["id"])) {
            $id = $_POST["id"];
            $query = $wpdb->prepare("SELECT * FROM `$table` WHERE `id` = '$id' ");
            $result = $wpdb->get_row($query);
            if (empty($result)) {
                $wpdb->insert($table, $val);
            } else {
                $wpdb->update($table, $val, ['id' => $id]);
            }
        } else {
            $wpdb->insert($table, $val);
        }
        return new WP_REST_Response([
            "status" => true,
            "message" => "Store added successfully"
        ], 200);
    } else if ($manage == "ship") {
        $table = tableName("fare");
        $val = [
            "store_id" => $_POST["storeId"],
            "km" => $_POST["km"],
            "type" => $_POST["type"],
            "fare" => $_POST["fare"]
        ];
        if (isset($_POST["id"])) {
            $id = $_POST["id"];
            $query = $wpdb->prepare("SELECT * FROM `$table` WHERE `id` = '$id' ");
            $result = $wpdb->get_row($query);
            if (empty($result)) {
                $wpdb->insert($table, $val);
            } else {
                $wpdb->update($table, $val, ['id' => $id]);
            }
        } else {
            $wpdb->insert($table, $val);
        }
        return new WP_REST_Response([
            "status" => true,
            "message" => "Shipping Fare added successfully"
        ], 200);
    }

    return new WP_REST_Response([
        "status" => false,
        "message" => "Invalid request"
    ], 500);
}

function deleteItem(WP_REST_Request $request)
{
    global $wpdb;
    $toDelete = $request->get_param('delete');
    if ($toDelete) {
        $action = $wpdb->delete(
            tableName($request->get_param('table')),
            ['id' => $request->get_param('id')],
            ['%d']
        );
        if ($action) {
            return new WP_REST_Response([
                "status" => true,
                "message" => "Item deleted successfully"
            ], 200);
        }
        return new WP_REST_Response([
            "status" => false,
            "message" => "Something went wrong"
        ], 500);
    }
    return new WP_REST_Response([
        "status" => false,
        "message" => "Invalid request"
    ], 500);
}

function updateProductInfo(WP_REST_Request $request)
{
    global $wpdb;
    $products = $request->get_param('products');
    $canUpdate = $request->get_param('update') ?? false;
    $table = tableName("product");

    foreach ($products as $item) {

        $storeId = $item["storeId"];
        $productId = $item["id"];
        $variant = $item["variant"] ?? 0;

        $query = $wpdb->prepare("SELECT * FROM `$table` WHERE `store_id` = '$storeId' AND `product_id` = '$productId' AND `quantity` = '$variant' ");
        $result = $wpdb->get_row($query);
        $val = [
            "store_id" => $storeId,
            "product_id" => $productId,
            "quantity" => $variant,
            "price" => $item["price"],
            "stock" => $item["stock"],
            "order" => $item["order"],
            "delivery_estimate" => $item["delivery_estimate"],
            "best_selling" => $item["best_selling"],
            "status" => $item["status"] ?? 0
        ];
        if (empty($result)) {
            $wpdb->insert($table, $val);
        } else if ($canUpdate) {
            $wpdb->update($table, $val, ['id' => $result->id]);
        }
    }

    return new WP_REST_Response([
        "status" => true,
        "message" => "Product updated successfully"
    ], 200);
}

function tableName($case)
{
    if ($case == "config") {
        require_once STORE_PLUGIN_DIR . '/admin/helpers/config.php';
        $config = new configHelper();
        return  $config->tableName();
    } else if ($case == "store") {
        require_once STORE_PLUGIN_DIR . '/admin/helpers/store.php';
        $store = new storeHelper();
        return $store->tableName();
    } else if ($case == "product") {
        require_once STORE_PLUGIN_DIR . '/admin/helpers/product.php';
        $product = new productHelper();
        return $product->tableName();
    } else if ($case == "fare") {
        require_once STORE_PLUGIN_DIR . '/admin/helpers/fare.php';
        $fare = new fareHelper();
        return $fare->tableName();
    }
}
