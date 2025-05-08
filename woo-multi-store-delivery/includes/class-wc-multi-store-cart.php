<?php

/**
 * Cart validation functionality
 */
class WC_Multi_Store_Cart
{
    /**
     * Initialize cart validation
     */
    public function init()
    {
        // Validate cart when adding product
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_delivery_type'), 10, 3);
        add_action('woocommerce_before_single_product_summary', [$this, 'productInfo'], 15);

        // Validate cart on checkout
        add_action('woocommerce_checkout_process', array($this, 'validate_checkout'));

        // Add cart notices
        add_action('woocommerce_before_checkout_form', array($this, 'add_checkout_notices'), 10);
    }

    /**
     * Validate delivery type when adding product to cart
     */
    public function validate_delivery_type($passed, $product_id, $quantity)
    {

        // Skip validation if product is not valid
        if (!$passed) {
            return $passed;
        }

        // Get user's selected city from localStorage (via AJAX)
        $selected_city = isset($_POST['selected_city']) ? sanitize_text_field($_POST['selected_city']) : '';

        if (empty($selected_city)) {
            wc_add_notice(__('Please select a city first.', 'wc-multi-store'), 'error');
            return false;
        }

        // Get store for the selected city
        $db = new WC_Multi_Store_DB();
        $store = $db->get_store_by_city($selected_city);

        if (!$store) {
            wc_add_notice(__('No store available for your selected city.', 'wc-multi-store'), 'error');
            return false;
        }

        // Get product data for the store
        $product_data = $db->get_product_data($store->id, $product_id);

        // Check if product is in stock
        if (isset($product_data['stock']) && $product_data['stock'] <= 0) {
            wc_add_notice(__('This product is out of stock in your selected city.', 'wc-multi-store'), 'error');
            return false;
        }

        // Get delivery estimate for the product
        $delivery_estimate = isset($product_data['delivery_estimate']) ? $product_data['delivery_estimate'] : 1;
        $is_same_day = $delivery_estimate == 1;

        // Check if cart is empty
        if (WC()->cart->is_empty()) {
            return $passed;
        }

        // Check if there are products with different delivery types in the cart

        foreach (WC()->cart->get_cart() as $cart_item) {
            $cart_product_id = $cart_item['product_id'];
            $cart_product_data = $db->get_product_data($store->id, $cart_product_id);
            $cart_delivery_estimate = isset($cart_product_data['delivery_estimate']) ? $cart_product_data['delivery_estimate'] : 1;
            $cart_is_same_day = $cart_delivery_estimate == 1 ? true : false;

            if ($is_same_day !== $cart_is_same_day) {
                wc_add_notice(
                    __('You cannot mix Same Day Delivery and Estimated Delivery products in the same cart.', 'wc-multi-store'),
                    'error'
                );
                wc_add_notice(__('You cannot mix Same Day Delivery and Estimated Delivery products in the same cart.', 'wc-multi-store'), 'error');
                return false;
            }
        }

        return $passed;
    }

    /**
     * Validate checkout
     */
    public function validate_checkout()
    {
        // Get shipping city
        $shipping_city = WC()->checkout->get_value('shipping_city');

        if (empty($shipping_city)) {
            wc_add_notice(__('Please provide a shipping city.', 'wc-multi-store'), 'error');
            return;
        }

        // Get store for the shipping city
        $db = new WC_Multi_Store_DB();
        $store = $db->get_store_by_city($shipping_city);

        if (!$store) {
            wc_add_notice(__('Select nearby store products.', 'wc-multi-store'), 'error');
            return;
        }

        // Check stock for each product in the cart
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            $quantity = $cart_item['quantity'];

            // Get product stock for the store
            $stock = $db->get_product_stock($store->id, $product_id);

            if ($stock !== false && $stock < $quantity) {
                wc_add_notice(__('There is no stock in your location for some products.', 'wc-multi-store'), 'error');
                return;
            }
        }
    }

    /**
     * Add checkout notices
     */
    public function add_checkout_notices()
    {
        // Get user's selected city from localStorage (via AJAX)
        $selected_city = isset($_POST['selected_city']) ? sanitize_text_field($_POST['selected_city']) : '';

        if (empty($selected_city)) {
            wc_print_notice(__('Please select a city first.', 'wc-multi-store'), 'notice');
            return;
        }

        // Get store for the selected city
        $db = new WC_Multi_Store_DB();
        $store = $db->get_store_by_city($selected_city);

        if (!$store) {
            wc_print_notice(__('No store available for your selected city. Please select a nearby city.', 'wc-multi-store'), 'notice');
        }
    }

    public function productInfo()
    {

        global $product;
        $db = new WC_Multi_Store_DB();
        $store = WC()->session->get('store_data');
        if ($store == null) {
            $store = [
                "id" => $db->get_default_store_city_info()[0]
            ];
        }

        $productId = $product->get_id();
        $product_url = get_permalink($productId);
        $info = $db->get_product_data($store["id"], $productId);
        $categories = wc_get_product_category_list($productId);

        echo '<script>
            document.addEventListener(`DOMContentLoaded`, function() {
                jQuery(`p.price:has(.woocommerce-Price-amount.amount)`).html(`<span class="woocommerce-Price-amount amount">
                    <bdi>
                        <span class="woocommerce-Price-currencySymbol">₹</span>' . ($info["price"]) . '
                    </bdi>
                </span>`);';
        if (((int)$info["stock"]) > 0) {
            $uniqId = uniqid();
            echo 'jQuery(`.wl-addto-cart`).html(`<form class="cart" action="' . esc_url($product_url) . '" method="post" enctype="multipart/form-data">
                        <div class="wl-quantity-wrap">
                            <span class="label">Quantity</span>
                            <div class="wl-quantity-cal">
                                <span class="wl-quantity wl-qunatity-minus">
                                    <i aria-hidden="true" class="fas fa-minus"></i>
                                </span>
                                <div class="quantity buttons_added">
                                    <label class="screen-reader-text" for="minus_qty">Minus Quantity</label>
                                    <a href="javascript:void(0)" id="minus_qty" class="minus">-</a>
                                    <label class="screen-reader-text" for="quantity_' . $uniqId . '">' . ($product->get_name()) . ' quantity</label>
                                    <input type="number" id="quantity_' . $uniqId . '" class="input-text qty text" name="quantity" value="1"
                                        aria-label="Product quantity" min="1" max="" step="1" placeholder="" inputmode="numeric"
                                        autocomplete="off">
                                    <label class="screen-reader-text" for="plus_qty"> Plus Quantity</label>
                                    <a href="javascript:void(0)" id="plus_qty" class="plus">+</a>
                                </div>
                                <span class="wl-quantity wl-qunatity-plus"><i aria-hidden="true" class="fas fa-plus"></i></span>
                            </div>
                        </div>
                        <div class="wl-cart-wrap both">
                            <button type="submit" name="add-to-cart" value="' . $info["product_id"] . '" class="single_add_to_cart_button button alt">
                                Add to cart
                            </button>
                            <input type="hidden" name="gtm4wp_product_data"
                                value=\'{"internal_id":' . $productId . ',"item_id":' . $productId . ',"item_name":"' . (str_replace(" ", "+", $product->get_name())) . '","sku":' . $productId . ',"price":' . $info["price"] . ',"stocklevel":' . $info["stock"] . ',"stockstatus":"instock","google_business_vertical":"retail","item_category":"' . (str_replace(" ", "+", $categories)) . '","id":' . $productId . '}\'>
                        </div>
                    </form>`)';
        } else {
            // echo 'jQuery(`.wl-addto-cart`).html(`<input type="submit" data-security="'.wp_create_nonce( 'cwg_trigger_popup_ajax_nonce' ).'" data-variation_id="" data-product_id="'.($productId).'" class="cwg_popup_submit " value="Notify Me">`)';
        }

        echo '});
        </script>';
    }
}
