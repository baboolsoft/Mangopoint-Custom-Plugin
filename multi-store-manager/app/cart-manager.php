<?php
class cartManager
{
    public function __construct()
    {
        add_filter('woocommerce_add_to_cart_validation', [$this, 'cartHandler'], 10, 5); // adding products to cart
        add_action('woocommerce_before_calculate_totals', [$this, 'changeCartPrice'], 20, 1); // modifying price of cart item
        add_action('wp_footer', [$this, 'enqueue_cart_open_script']);
        // shipping hooks begin
        add_filter('woocommerce_order_button_html', [$this, 'checkoutHandler']); // disabling "place order" button
        add_filter('woocommerce_cart_item_name', [$this, 'cartItemHandler'], 10, 3); // validating either product avil in store
        add_filter('woocommerce_quantity_input_args', [$this, 'cartQuantityHandler'], 10, 3); // validating quantity count in cart page
        add_filter('woocommerce_get_order_item_totals', [$this, 'remove_shipping'], 10, 3);

        add_action('woocommerce_checkout_create_order_line_item', [$this, 'orderItem'], 10, 4); //adding custom datas to order item
        add_action('woocommerce_cart_calculate_fees', [$this, 'cartFee']); // adding shipping fare
        add_action('woocommerce_thankyou', [$this, 'handleStockCount'], 10, 1); // handling stock deduction
        add_action('woocommerce_checkout_order_created', [$this, 'manageOrder'], 10, 1); // manage order id once order placed
        // shipping hooks end
    }
    public function cartHandler()
    {
        require_once STORE_PLUGIN_DIR . '/admin/helpers/product.php';
        $ins = new productHelper();
        $variant = ($_POST["variant"] ?? 0);
        $variantId = 0;
        $data = $ins->productData($_POST["product_id"], $_SESSION["store"]["id"], $variant);
        $quantity = (int)$_POST["quantity"];
        if (isset($data["id"])) {
            if ((int)$data["stock"] >= $quantity) {
                $product = wc_get_product($_POST["product_id"]);

                if ($product->is_type('variable')) {
                    if ($variant != 0) {
                        $variation_ids = $product->get_children();
                        foreach ($variation_ids as $variation_id) {
                            $variation = wc_get_product($variation_id);
                            if (isset($variation->get_attributes()["pa_size"]) && $_POST["variant"] == $variation->get_attributes()["pa_size"]) {
                                $variantId = $variation_id;
                                if ($variation->managing_stock() && $variation->get_stock_quantity() < $quantity) {
                                    $variation->set_stock_quantity($quantity);
                                    $variation->save();
                                }
                                if ($variation->get_stock_status() != "instock") {
                                    $variation->set_stock_status('instock');
                                    $variation->save();
                                }
                            }
                        }
                    } else {
                        echo json_encode([
                            "status" => false,
                            "messgae" => "product not exist"
                        ]);
                        exit;
                    }
                } else {
                    if ($product && $product->managing_stock() && $product->get_stock_quantity() < $quantity) {
                        $product->set_stock_quantity($quantity);
                        $product->save();
                    }
                    if ($product->get_stock_status() != "instock") {
                        $product->set_stock_status('instock');
                        $product->save();
                    }
                }

                $isExist = false;
$cart_items = WC()->cart->get_cart();
$_POST["variant"] = $_POST["variant"] ?? 0;

foreach ($cart_items as $cart_item_key => $cart_item) {
    if (
        ($cart_item["product_id"] == $_POST["product_id"]) &&
        ($cart_item["variant"] == $_POST["variant"]) &&
        ($_SESSION["store"]["id"] == $cart_item["storeId"])
    ) {
        $isExist = true;
        $existingQty = (int)$cart_item["quantity"];
        $newQty = $existingQty + $quantity;

        // Cap quantity based on stock
        if ((int)$data["stock"] < $newQty) {
            $newQty = (int)$data["stock"];
        }

        $action = WC()->cart->set_quantity($cart_item_key, $newQty);
        break;
    }
}
                if ($isExist == false) {
                    $action = WC()->cart->add_to_cart((int)$_POST["product_id"], (int)$_POST["quantity"], $variantId, [], [
                        "variant" => $_POST["variant"],
                        "storeId" => $_SESSION["store"]["id"],
                        "price" => $data["price"],
                        "deliveryOption" => $_POST["deliveryOption"] ?? "same-day-delivery"
                    ]);
                }
                $cart_items = WC()->cart->get_cart();
                $sub_total = 0;
                $productHtml = '';
                $showErr = false;
                foreach ($cart_items as $cart_item_key => $cart_item) {
                    $product_data = wc_get_product($cart_item["product_id"]);
                    $image = wp_get_attachment_image_url($product_data->get_image_id(), 'woocommerce_thumbnail') ?: wc_placeholder_img_src('woocommerce_thumbnail');
                    $price = (float)$cart_item["price"];
                    $total = (int)$cart_item["quantity"] * $price;
                    $sub_total = $sub_total + $total;
                    // if ($showErr == false && isset($_POST["deliveryOption"]) && isset($cart_item["deliveryOption"]) ) {
                    //     $showErr =  ($cart_item["deliveryOption"] !== $_POST["deliveryOption"]) ? true : false;
                    // }
                    $productHtml .= '<div data-key="' . ($cart_item["data_hash"]) . '" class="xoo-wsc-product">
                    <div class="xoo-wsc-img-col">
                        <a href="' . ($product_data->get_permalink()) . '">
                            <img width="300" height="300" src="' . ($image) . '"
                                class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="" decoding="async"
                            />
                        </a>
                    </div>
                    <div class="xoo-wsc-sum-col">
                        <div class="xoo-wsc-sm-info">
                            <div class="xoo-wsc-sm-left">
                                <span class="xoo-wsc-pname">
                                    <a href="' . ($product_data->get_permalink()) . '">
                                        ' . ($product_data->get_name());
                    if ($cart_item["variant"] != "0") {
                        $productHtml .= " (" . strtoupper(str_replace("-", " ", $cart_item["variant"])) . ")";
                    }
                    $productHtml .= '</a></span>';

    // Show product availability warning if not available
    if (count($this->checkProduct($cart_item)) == 0) {
        $productHtml .= '<div class="err">This product is not available to your store</div>';
    }

    $productHtml .= '<div class="xoo-wsc-qty-price">
                        <span>' . (int)$cart_item["quantity"] . '</span>
                        <span>X</span>
                                    <span><span class="woocommerce-Price-amount amount">
                                        <bdi><span class="woocommerce-Price-currencySymbol">&#8377;</span>' . (number_format((float)$price, 2)) . '</bdi></span>
                                    </span>
                                    <span>=</span>
                                    <span>
                                        <span class="woocommerce-Price-amount amount">
                                            <bdi><span class="woocommerce-Price-currencySymbol">&#8377;</span>' . (number_format($total, 2)) . '</bdi>
                                        </span>
                                    </span>
                                </div>
                            </div>
                            <div class="xoo-wsc-sm-right">
                                <span class="xoo-wsc-smr-del xoo-wsc-icon-trash"></span>
                            </div>
                        </div>
                    </div>
                </div>';
                }
                $fragment = '<div class="xoo-wsc-container">
                <div class="xoo-wsc-basket">
                    <span class="xoo-wsc-items-count">' . (count($cart_items)) . '</span>
                    <span class="xoo-wsc-bki xoo-wsc-icon-basket1"></span>
                </div>
                <div class="xoo-wsc-header">
                    <div class="xoo-wsch-top">
                        <div class="xoo-wsch-basket">
                            <span class="xoo-wscb-icon xoo-wsc-icon-bag2"></span>
                            <span class="xoo-wscb-count">' . (count($cart_items)) . '</span>
                        </div>
                        <span class="xoo-wsch-text">Your Cart</span>
                        <span class="xoo-wsch-close xoo-wsc-icon-cross"></span>
                    </div>
                </div>';
                if ($showErr == true) {
                    $fragment .= '<div class="xoo-warn">
                        There is a mismatch between your cart items and the selected delivery type.
                        Please review your cart or delivery choice.
                    </div>';
                }
                $fragment .= '<div class="xoo-wsc-body">
                    <div class="xoo-wsc-products xoo-wsc-pattern-row">' . $productHtml . '</div>
                </div>
                <div class="xoo-wsc-footer">
                    <div class="xoo-wsc-ft-totals">
                        <div class="xoo-wsc-ft-amt xoo-wsc-ft-amt-subtotal ">
                            <span class="xoo-wsc-ft-amt-label">Subtotal</span>
                            <span class="xoo-wsc-ft-amt-value">
                                <span class="woocommerce-Price-amount amount">
                                    <bdi><span class="woocommerce-Price-currencySymbol">&#8377;</span>' . (number_format($sub_total, 2)) . '</bdi>
                                </span>
                            </span>
                        </div>
                    </div>
                    <div class="xoo-wsc-ft-buttons-cont">
                        <a href="#" class="xoo-wsc-ft-btn xoo-wsc-btn button btn xoo-wsc-cart-close xoo-wsc-ft-btn-continue">Continue Shopping</a>
                        <a href="' . (home_url()) . '/shopping-cart/" class="xoo-wsc-ft-btn xoo-wsc-btn button btn xoo-wsc-ft-btn-cart">View Cart</a>
                        <a href="' . (home_url()) . '/checkout/" class="xoo-wsc-ft-btn xoo-wsc-btn button btn xoo-wsc-ft-btn-checkout">Checkout</a>
                    </div>
                </div>
                <span class="xoo-wsc-loader"></span>
                <span class="xoo-wsc-icon-spinner8 xoo-wsc-loader-icon"></span>
            </div>
            <script>
                // Set a flag in localStorage to indicate we want to open the cart after reload
                localStorage.setItem("openSideCart", "true");
                location.reload(true);
            </script>';
                echo json_encode([
                    "status" => true,
                    "message" => $_POST["success_message"],
                    "cart_hash" => $action,
                    "fragments" => ["div.xoo-wsc-container" => $fragment]
                ]);
            } else {
                echo json_encode([
                    "status" => false,
                    "messgae" => "out of stock",
                    "data" => (int)$data["stock"],
                ]);
            }
        } else {
            echo json_encode([
                "status" => false,
                "messgae" => "product not exist"
            ]);
        }
        exit;
    }

    public function changeCartPrice($cart)
    {
        if (is_admin() && !defined('DOING_AJAX')) return;
        foreach ($cart->get_cart() as $cart_item) {
            // $cart_item['data']->set_price($_SESSION["store"]["id"]);
            if ($cart_item["storeId"] == $_SESSION["store"]["id"]) {
                $cart_item['data']->set_price($cart_item["price"]);
            } else if (isset($_SESSION["store"]["id"])) {
                require_once STORE_PLUGIN_DIR . '/admin/helpers/product.php';
                $ins = new productHelper();
                $data = $ins->productData($cart_item["product_id"], $_SESSION["store"]["id"], $cart_item["variant"]);
                $cart_item['data']->set_price($data["price"]);
            }
        }
    }

    // Add this function to your class to handle the cart opening on page load
    public function enqueue_cart_open_script()
    {
        // Only add this script on the frontend
        if (!is_admin()) {
            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    // Check if we need to open the side cart
                    if (localStorage.getItem("openSideCart") === "true") {
                        // Clear the flag
                        localStorage.removeItem("openSideCart");

                        // Open the side cart - this assumes the cart uses a class to toggle visibility
                        // You may need to adjust this based on how your cart plugin works
                        setTimeout(function() {
                            // Find the cart trigger element and click it
                            var cartTrigger = document.querySelector(".xoo-wsc-basket");
                            if (cartTrigger) {
                                cartTrigger.click();
                            } else {
                                // Alternative: directly add active class to the cart container
                                var cartContainer = document.querySelector(".xoo-wsc-container");
                                if (cartContainer) {
                                    cartContainer.classList.add("xoo-wsc-cart-active");
                                }
                            }
                        }, 500); // Small delay to ensure everything is loaded
                    }
                });
            </script>';
        }
    }
    public function cartItemPriceHandler($price, $cart_item, $cart_item_key)
    {
        if (isset($cart_item["price"])) {
            return $cart_item["price"];
        }
        return 0;
    }
    public function cartItemTotalHandler($subtotal, $cart_item, $cart_item_key)
    {
        if (isset($cart_item["price"]) && isset($cart_item["quantity"])) {
            return number_format((float)$cart_item["price"] * (int)$cart_item["quantity"], 2);
        }
        return 0;
    }
    public function cartSubTotalHandler($cart_subtotal, $compound, $cart)
    {
        $total = 0;
        if ((isset($cart->cart_contents)) && (count($cart->cart_contents) > 0)) {
            foreach ($cart->cart_contents as $key => $value) {
                $total += (float)$value["price"] * (int)$value["quantity"];
            }
        }
        return number_format($total, 2);
    }
    public function cartTotalHandler()
    {
        $cart_items = WC()->cart->get_cart();
        $total = 0;
        foreach ($cart_items as $cart_item_key => $cart_item) {
            $price = (float)$cart_item["price"];
            $total += (int)$cart_item["quantity"] * $price;
        }
        $total += $this->shippingFee();
        return number_format($total, 2);
    }
    public function shippingHandler()
    {
        $fare = $this->shippingFee();
        $price = $fare > 0 ? (number_format($fare, 2)) : "Provide delivery address for calculating shipping charges";
        echo '<tr class="custom-extra-fee">
            <th>Shipping Fare</th>
            <td data-price="' . $fare . '" class="shipping-fee" data-title="Shipping Fare">' . $price . '</td>
        </tr>';
    }
    public function shippingFee()
    {
        $customer = WC()->customer;
        $fare = 0;

        if (isset($customer) && isset($_SESSION["store"])) {
            require_once STORE_PLUGIN_DIR . '/app/helper.php';

            // Prefer shipping postcode if it's filled, otherwise use billing
            $shipping_postcode = trim($customer->get_shipping_postcode());
            $billing_postcode = trim($customer->get_billing_postcode());

            $postcode = !empty($shipping_postcode) ? $shipping_postcode : $billing_postcode;

            if (!empty($postcode)) {
                $data = getPlaceInfo($postcode);

                if (count($data) > 0) {
                    $location = $data[0]->geometry->location;
                    $store = getStoreInfo($_SESSION["store"]["id"]);
                    $fare = calculateFare(
                        $_SESSION["store"]["id"],
                        getDistanceInKm($store->lat, $store->lng, $location->lat, $location->lng)
                    );
                }
            }
        }

        return $fare;
    }

    public function checkoutHandler($button)
    {
        if (is_checkout() && !is_wc_endpoint_url('order-received')) {
            $customer = WC()->customer;
            $cart_items = WC()->cart->get_cart();
            $count = 0;
            // $options = [];
            foreach ($cart_items as $cart_item) {
                if (count($this->checkProduct($cart_item)) == 0) {
                    $count++;
                    break;
                }
                // array_push($options, $cart_item["deliveryOption"]);
            }
            require_once STORE_PLUGIN_DIR . '/app/helper.php';
            $can = false;
            $pincode = $customer->get_shipping_postcode() ?? $customer->get_billing_postcode();
            $data = getPlaceInfo($pincode);
            if (count($data) > 0) {
                $location = $data[0]->geometry->location;
                // $nearestStore = getNearestLocation($location->lat, $location->lng);
                $can = can_store_allow_order($location->lat, $location->lng);
            }


            // $options = array_unique($options);
            // if (($count > 0) || ($can == false) || (count($options) > 1)) {
            require_once STORE_PLUGIN_DIR . '/admin/helpers/config.php';
            $config = new configHelper();
            $minPrice = (int)base64_decode($config->getConfig("minPrice"));
            $cart_sub_total = (int)WC()->cart->get_subtotal();
            
            if (($count > 0) || ($can == false) || ($cart_sub_total < $minPrice)) {
                if ($pincode !== null && $pincode !== '' && $can == false) {
                    echo "<script>
                        toaster({
                            type: 'danger',
                            text: 'Pincode Not Serviced',
                            delay: 2000
                        });
                    </script>";
                    wc_print_notice(__('Delivery is currently unavailable to your pincode (' . ($pincode) . ') as it falls outside our serviceable area. Please try a different address or check back soon as we expand our delivery zones.', 'multi-store-manager'), 'notice');
                }
                // if (count($options) > 1) {
                //     wc_print_notice(__('There is a mismatch between your cart items and the product delivery type. Please review your cart or delivery choice.', 'multi-store-manager'), 'notice');
                // }

                if (($cart_sub_total < $minPrice)) {
                    wc_print_notice(__(" 🚫💵 Your current cart total is ₹$cart_sub_total. A minimum purchase of ₹$minPrice is required to proceed. Please add more items to your cart.", 'multi-store-manager'), 'notice');
                }
                $button = str_replace('type="submit"', 'data-store="' . $_SESSION["store"]["id"] . '" data-count="' . $count . '" data-can-disable="' . ($can == false ? "no" : "yes") . '" type="submit" disabled', $button);
            }
        }
        return $button;
    }
    public function cartItemHandler($name, $cart_item, $cart_item_key)
    {
        if (isset($_SESSION["store"])) {
            if ($cart_item["variant"] != "0") {
                $name .= ' (' . str_replace("-", " ", $cart_item["variant"]) . ')';
            }
            if (count($this->checkProduct($cart_item)) == 0) {
                return  ucwords($name) . '<div class="err">This product is not available to your store</div>';
            }
            return ucwords($name);
        }
    }
    public function cartFee($cart)
    {
        if (is_admin() && !defined('DOING_AJAX')) return;
        $cart->add_fee('Shipping Fare', $this->shippingFee(), false);
    }
    public function checkProduct($cart_item)
    {
        global $wpdb;
        require_once STORE_PLUGIN_DIR . '/admin/helpers/api.php';
        $table = tableName(("product"));
        $storeId = $_SESSION["store"]["id"] ?? 0;
        $productId = $cart_item["product_id"];
        $variant = $cart_item["variant"];
        $query = "SELECT * FROM `$table` WHERE `store_id` = '$storeId' AND `product_id` = '$productId' AND `quantity` = '$variant' AND `stock` > 0 AND `status` = '1'";
        return $wpdb->get_results($wpdb->prepare($query));
    }
    public function handleStockCount($orderId)
    {
        if (isset($_SESSION["store"]["id"])) {
            require_once STORE_PLUGIN_DIR . "admin/helpers/orders.php";
            $ins = new orderHelper();
            $orderStatus = $ins->fetchOrderStatus($orderId, $_SESSION["store"]["id"]);
            if ($orderStatus->status == "0") {
                $ins->udpateOrderStatus($orderId, $_SESSION["store"]["id"]);
                global $wpdb;
                require_once STORE_PLUGIN_DIR . "admin/helpers/product.php";
                $product = new productHelper();
                $order = wc_get_order($orderId);
                foreach ($order->get_items() as $item) {
                    $product_id = $item->get_product_id();
                    $quantity = (int)$item->get_quantity();
                    $variation = $item->get_meta("variant_value");
                    $store_id = $item->get_meta("store_id");
                    $wpdb->query(
                        $wpdb->prepare(
                            "UPDATE {$product->tableName()} 
                         SET `stock` = `stock` - %d 
                         WHERE `store_id` = %d AND `product_id` = %d AND `quantity` = %s",
                            $quantity,
                            $store_id,
                            $product_id,
                            $variation
                        )
                    );
                }
            }
            $order = wc_get_order($orderId);

            require_once STORE_PLUGIN_DIR . "admin/helpers/store.php";
            $storeManager = new storeHelper();
            $info = $storeManager->getStoreList($_SESSION["store"]["id"]);

            $mail = !empty($info->mail) && is_email($info->mail) ? $info->mail : get_option('admin_email');
            $storeName = !empty($info->name) ? $info->name : 'Unknown Store';

            $subject = 'New order from ' . $storeName . ' - Order#' . $orderId;
            $headers = array('Content-Type: text/html; charset=UTF-8');
            $body = '<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#fff;border:1px solid #dedede;border-radius:3px" bgcolor="#fff">
                <tbody>
                    <tr>
                        <td align="center" valign="top">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#e2841f;color:#fff;font-weight:bold;line-height:100%;font-family:Helvetica,Arial,sans-serif;border-radius:3px 3px 0 0" bgcolor="#e2841f">
                                <tr>
                                    <td style="padding:36px 48px;">
                                        <h1 style="font-size:24px;font-weight:300;line-height:150%;margin:0;color:#fff;">
                                            New Order Received - Order #' . $orderId . '
                                        </h1>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" valign="top">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#ffffff">
                                <tr>
                                    <td valign="top" style="padding:48px;">
                                        <p style="font-size:16px;margin:0 0 16px;">Hello,</p>
                                        <p style="font-size:14px;margin:0 0 16px;">You have received a new order. Below are the details:</p>

                                        <h2 style="color:#e2841f;font-size:18px;margin-bottom:16px;">Order Summary [#' . $orderId . '] - ' . date('M d, Y') . '</h2>
                                        
                                        <table cellspacing="0" cellpadding="6" border="1" style="width:100%;border:1px solid #e5e5e5;border-collapse:collapse;">
                                            <thead>
                                                <tr>
                                                    <th align="left" style="padding:12px;border:1px solid #e5e5e5;">Product</th>
                                                    <th align="left" style="padding:12px;border:1px solid #e5e5e5;">Quantity</th>
                                                    <th align="left" style="padding:12px;border:1px solid #e5e5e5;">Rate</th>
                                                    <th align="left" style="padding:12px;border:1px solid #e5e5e5;">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>';
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                $rate = $item->get_total() / $item->get_quantity();
                $body .= '<tr>
                                                    <td style="padding:12px;border:1px solid #e5e5e5;">' . $item->get_name() . '</td>
                                                    <td style="padding:12px;border:1px solid #e5e5e5;">' . $item->get_quantity() . '</td>
                                                    <td style="padding:12px;border:1px solid #e5e5e5;">₹' . number_format($rate, 2) . '</td>
                                                    <td style="padding:12px;border:1px solid #e5e5e5;">₹' . number_format($item->get_total(), 2) . '</td>
                                                </tr>';
            }
            $body .= '</tbody>
                                            <tfoot>
                                                <tr>
                                                    <th colspan="3" align="right" style="padding:12px;border:1px solid #e5e5e5;">Subtotal:</th>
                                                    <td style="padding:12px;border:1px solid #e5e5e5;">₹' . number_format($order->get_subtotal(), 2) . '</td>
                                                </tr>';

            foreach ($order->get_fees() as $fee) {
                $body .= '<tr>
                                                        <th colspan="3" align="right" style="padding:12px;border:1px solid #e5e5e5;">' . $fee->get_name() . ':</th>
                                                        <td style="padding:12px;border:1px solid #e5e5e5;">₹' . number_format($fee->get_total(), 2) . '</td>
                                                    </tr>';
            }

            $body .= '<tr>
                                                    <th colspan="3" align="right" style="padding:12px;border:1px solid #e5e5e5;font-weight:bold;">Total:</th>
                                                    <td style="padding:12px;border:1px solid #e5e5e5;font-weight:bold;">₹' . number_format($order->get_total(), 2) . '</td>
                                                </tr>
                                            </tfoot>
                                        </table>

                                        <br><br>
                                        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                                            <tr>
                                                <td width="50%" valign="top" style="padding-right:20px;">
                                                    <h3 style="color:#e2841f;">Billing Address</h3>
                                                    <p style="margin:0;">' . $order->get_formatted_billing_full_name() . '<br>' .
                $order->get_billing_address_1() . ' ' . $order->get_billing_address_2() . '<br>' .
                $order->get_billing_city() . ' - ' . $order->get_billing_postcode() . '<br>' .
                $order->get_billing_state() . '<br>' .
                '<a href="tel:' . $order->get_billing_phone() . '" style="color:#e2841f;">' . $order->get_billing_phone() . '</a><br>' .
                '<a href="mailto:' . $order->get_billing_email() . '" style="color:#e2841f;">' . $order->get_billing_email() . '</a></p>
                                                </td>
                                                <td width="50%" valign="top" style="padding-left:20px;">
                                                    <h3 style="color:#e2841f;">Shipping Address</h3>
                                                    <p style="margin:0;">' . $order->get_formatted_shipping_full_name() . '<br>' .
                $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2() . '<br>' .
                $order->get_shipping_city() . ' - ' . $order->get_shipping_postcode() . '<br>' .
                $order->get_shipping_state() . '<br>' .
                ($order->get_shipping_phone() ? '<a href="tel:' . $order->get_shipping_phone() . '" style="color:#e2841f;">' . $order->get_shipping_phone() . '</a>' : '') .
                '</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>';
            wp_mail($mail, $subject, $body, $headers);
        }
    }
    public function manageOrder($order)
    {
        if (isset($_SESSION["store"]["id"])) {
            require_once STORE_PLUGIN_DIR . "admin/helpers/orders.php";
            $ins = new orderHelper();
            $ins->insertOrder($order->get_id(), $_SESSION["store"]["id"]);
        }
    }
    public function orderItem($item, $cart_item_key, $values, $order)
    {
        $item->add_meta_data('store_id', $values['storeId'], true);
        $item->add_meta_data('variant_value', $values['variant'], true);
        if ($values['variant'] != "0") {
            $item->set_name($item->get_name() . " (" . str_replace("-", " ", $values['variant']) . ")");
        }
    }
    public function cartQuantityHandler($args, $product)
    {
        if (is_cart()) {
            $productId = $product->get_id();
            $storeId = $_SESSION["store"]["id"];
            $cart_items = WC()->cart->get_cart();
            foreach ($cart_items as $cart_item) {
                if (($storeId == $cart_item["storeId"]) && (($cart_item["product_id"] == $productId) || ($cart_item["variation_id"] == $productId))) {
                    require_once STORE_PLUGIN_DIR . '/admin/helpers/api.php';
                    global $wpdb;
                    $table = tableName("product");
                    $variant = $cart_item["variant"];
                    $query = "SELECT * FROM `$table` WHERE `store_id` = '$storeId' AND `product_id` = '" . $cart_item["product_id"] . "' AND `quantity` = '$variant' ";
                    $result = $wpdb->get_row($wpdb->prepare($query), ARRAY_A);
                    $args["max_value"] = (int)$result["stock"];
                    break;
                }
            }
        }
        return $args;
    }
    public function remove_shipping($totals, $order, $tax_display)
    {
        if (isset($totals['shipping'])) {
            unset($totals['shipping']);
        }
        return $totals;
    }
}