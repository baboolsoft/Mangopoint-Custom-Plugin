<?php
class cartManager
{
    public function __construct()
    {
        add_filter('woocommerce_add_to_cart_validation', [$this, 'cartHandler'], 10, 5); // adding products to cart
        add_action('woocommerce_before_calculate_totals', [$this, 'changeCartPrice'], 20, 1); // modifying price of cart item

        // shipping hooks begin
        // add_filter('woocommerce_cart_needs_shipping', '__return_false'); // disabling woo shipping fee
        // add_filter('woocommerce_package_rates', [$this, 'disable_default_shipping_fee'], 10, 2);
        add_filter('woocommerce_order_button_html', [$this, 'checkoutHandler']); // disabling "place order" button
        add_filter('woocommerce_cart_item_name', [$this, 'cartItemHandler'], 10, 3); // validating either product avil in store

        // add_action('woocommerce_cart_calculate_fees', [$this, 'cartFee']); // adding shipping fare
        add_action('woocommerce_checkout_process', [$this, 'handleCheckout']); // handling stock deduction
        // add_action( 'woocommerce_after_cart_item_quantity_update', [$this], 10, 4 );
        // shipping hooks end
    }

    public function cartHandler()
    {
        require_once STORE_PLUGIN_DIR . '/admin/helpers/product.php';

        $ins = new productHelper();
        $variant = ($_POST["variant"] ?? 0);
        $variantId = 0;
        $data = $ins->productData($_POST["product_id"], $_POST["storeId"], $variant);

        if (isset($data["id"])) {
            if ((int)$data["stock"] > 0) {

                $product = wc_get_product($_POST["product_id"]);
                if ($product->get_stock_status() != "instock") {
                    $product->set_stock_status('instock');
                    $product->save();
                }

                if ($product->is_type('variable') && $variant != 0) {
                    $variation_ids = $product->get_children();
                    foreach ($variation_ids as $variation_id) {
                        $variation = wc_get_product($variation_id);
                        if (isset($variation->get_attributes()["pa_size"]) && $quantity == $variant) {
                            $variantId = $variation_id;
                        }
                    }
                } else if ($variant == 0 && $product->is_type('variable')) {
                    echo json_encode([
                        "status" => false,
                        "messgae" => "product not exist"
                    ]);
                    exit;
                }

                $action = WC()->cart->add_to_cart((int)$_POST["product_id"], (int)$_POST["quantity"], $variantId, [], [
                    "variant" => $variant,
                    "storeId" => $_POST["storeId"],
                    "price" => $data["price"]
                ]);

                $cart_items = WC()->cart->get_cart();
                $sub_total = 0;
                $productHtml = '';
                $d_type = 0;

                foreach ($cart_items as $cart_item_key => $cart_item) {
                    $product_data = wc_get_product($cart_item["product_id"]);
                    $image = wp_get_attachment_image_url($product_data->get_image_id(), 'woocommerce_thumbnail') ?: wc_placeholder_img_src('woocommerce_thumbnail');
                    $price = (float)$cart_item["price"];
                    $total = (int)$cart_item["quantity"] * $price;
                    $sub_total = $sub_total + $total;
                    $deliveryType = $ins->checkDelivery($cart_item["product_id"], $cart_item["storeId"], $cart_item["variant"]);
                    if ($d_type == 0) {
                        $d_type = $deliveryType;
                    }
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
                                        <a href="' . ($product_data->get_permalink()) . '">' . ($product_data->get_name()) . '</a>
                                    </span>
                                    <div class="xoo-wsc-qty-price">
                                        <span>' . ($cart_item["quantity"]) . '</span>
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
                    </div>
                    <div class="xoo-wsc-body">
                        <div class="xoo-wsc-products xoo-wsc-pattern-row">' . $productHtml . '</div>
                    </div>
                    <div class="xoo-wsc-footer">
                        <div class="xoo-wsc-ft-totals">
                            <div class="xoo-wsc-ft-amt xoo-wsc-ft-amt-subtotal ">
                                <span class="xoo-wsc-ft-amt-label">Sub-total</span>
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
                </div>';
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
                    "data" => (int)$data["stock"]
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
            $cart_item['data']->set_price($cart_item["price"]);
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
            $data = getPlaceInfo($customer->get_billing_postcode() ?? $customer->get_shipping_postcode());
            if (count($data) > 0) {
                $location = $data[0]->geometry->location;

                $store = getStoreInfo($_SESSION["store"]["id"]);
                $fare = calculateFare($_SESSION["store"]["id"], getDistanceInKm($store->lat, $store->lng, $location->lat, $location->lng));
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

            foreach ($cart_items as $cart_item) {
                if (count($this->checkProduct($cart_item)) == 0) {
                    $count++;
                    break;
                }
            }

            require_once STORE_PLUGIN_DIR . '/app/helper.php';
            $can = false;
            $pincode = $customer->get_shipping_postcode()??null;
            $data = getPlaceInfo($pincode);
            if (count($data) > 0) {
                $location = $data[0]->geometry->location;

                $can = can_store_allow_order($location->lat, $location->lng, $_SESSION["store"]["id"]);
            }

            if (($count > 0) || ($can == false)) {
                if ($pincode !== null && $pincode !== '') {
                    echo "<script>
                        toaster({
                            type: 'danger',
                            text: 'Pincode Not Serviced',
                            delay: 2000
                        });
                    </script>";
                    wc_print_notice(__('Delivery is currently unavailable to your pincode (' . ($pincode) . ') as it falls outside our serviceable area. Please try a different address or check back soon as we expand our delivery zones.', 'multi-store-manager'), 'notice');
                }
                $button = str_replace('type="submit"', 'data-count="'.$count.'" data-can-disable="'.($can ? "no" : "yes").'" type="submit" disabled', $button);
            }
        }
        return $button;
    }

    public function cartItemHandler($name, $cart_item, $cart_item_key)
    {
        if (isset($_SESSION["store"])) {
            if (count($this->checkProduct($cart_item)) == 0) {
                return ucwords($name) . '<small class="err">This product is not available to your store</small>';
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
        $query = "SELECT * FROM `$table` WHERE `store_id` = '$storeId' AND `product_id` = '$productId' AND `quantity` = '$variant' AND `status` = '1'";

        return $wpdb->get_results($wpdb->prepare($query));
    }

    public function disable_default_shipping_fee($rates, $package)
    {
        return [];
    }

    public function handleCheckout($orderId)
    {
        echo "order us " . $orderId;
        exit;
    }
}
