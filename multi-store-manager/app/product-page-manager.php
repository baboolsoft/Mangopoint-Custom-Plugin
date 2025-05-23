<?php
class productPageManager
{
    public function __construct()
    {
        add_action('woocommerce_before_single_product_summary', [$this, 'productInfo'], 15);
    }

    public function productInfo()
    {
        global $product;
        global $wpdb;
        WC()->initialize_session();

        $productId = $product->get_id();
        $product = wc_get_product($productId);
        require_once STORE_PLUGIN_DIR . '/admin/helpers/api.php';
        require_once STORE_PLUGIN_DIR . '/app/helper.php';
        $store = $_SESSION["store"] ?? null;
        if ($store == null) {
            $store = defaultStore();
            $storeId = $store->id;
        } else {
            $storeId = $store["id"];
        }

        // retriving product data
        $product_table = tableName("product");
        $result = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$product_table} WHERE store_id = %d AND product_id = %d AND `status` = '1' ", $storeId, $productId)
        );

        $total_stock = 0;
        $minPrice = 0;
        $maxPrice = 0;
        $variant_datas = [];

        foreach ($result as $key => $value) {
            $value->quantity_title = strtoupper(str_replace("-", " ", $value->quantity));
            $total_stock += (int)$value->stock;
            $minPrice = (($minPrice == 0) || ($minPrice > (float)$value->price)) ? $value->price : $minPrice;
            $maxPrice = (($maxPrice == 0) || ($maxPrice < (float)$value->price)) ? $value->price : $maxPrice;

            // Get cart quantity for this product + variant
            $cart_quantity = 0;
            foreach (WC()->cart->get_cart() as $cart_item) {
                if ($cart_item['product_id'] == $productId && (!isset($cart_item['variant']) || $cart_item['variant'] == $value->quantity)) {
                    $cart_quantity += $cart_item['quantity'];
                }
            }

            $remaining_stock = max((int)$value->stock - $cart_quantity, 0);

            if ($remaining_stock > 0) {
                array_push($variant_datas, [
                    "id" => (int)$value->quantity,
                    "value" => $value->quantity,
                    "label" => $value->quantity_title,
                    "price" => (float)$value->price,
                    "stock" => $remaining_stock
                ]);
            }
        }

        usort($variant_datas, function ($a, $b) {
            return $a['id'] <=> $b['id'];
        });
        $isVariant = count($result) === 1 ? 0 : 1;
        $uniqId = uniqid();

        echo '<script id="product-manager-script">
            document.addEventListener(`DOMContentLoaded`, function() {

                jQuery(`p.price:has(.woocommerce-Price-amount.amount)`).html(`<span class="woocommerce-Price-amount amount">
                    <bdi>
                        <span class="woocommerce-Price-currencySymbol">₹</span> ' . number_format($minPrice, 2) . '
                    </bdi>';
        if ($isVariant) {
            echo  ' - <bdi>
                            <span class="woocommerce-Price-currencySymbol">₹</span> ' . number_format($maxPrice, 2) . '
                        </bdi>';
        }
        echo '</span>`);';

        if (count($variant_datas) > 0) {
            $cart_html = '';
            if ($isVariant) {
                $cart_html .= '<table class="variations" cellspacing="0" role="presentation">
                    <tbody>
                        <tr>
                            <th class="label"><label for="pa_size">Quantity</label></th>
                            <td class="value">
                                <select name="variant-option">';
                foreach ($variant_datas as $item) {
                    $cart_html .= '<option data-value="' . ($item["value"]) . '" data-stock="' . $item["stock"] . '" value="' . ($item["price"]) . '">' . ($item["label"]) . '</option>';
                }
                $cart_html .= '</select>
                                <button class="reset_variations" href="#" aria-label="Clear options" style="visibility: hidden;">Clear</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="woocommerce-variation-price">
                    <ins aria-hidden="true">
                        <span class="woocommerce-Price-amount amount price">
                            <bdi>
                                <span class="woocommerce-Price-currencySymbol">₹</span>
                                <span class="variant-price">' . number_format($variant_datas[0]["price"], 2) . '</span>
                            </bdi>
                        </span>
                    </ins>
                    <span class="screen-reader-text">Current price is: ₹200.00.</span></span>
                </div>';
            }

            $max_stock = isset($variant_datas[0]["stock"]) ? $variant_datas[0]["stock"] : 0;

            $cart_html .= '
            <div class="wl-quantity-wrap">
                <span class="label">Quantity</span>
                <div class="wl-quantity-cal">
                    <span class="wl-quantity wl-qunatity-minus">
                        <i aria-hidden="true" class="fas fa-minus"></i>
                    </span>
                    <div class="quantity buttons_added">
                        <label class="screen-reader-text" for="minus_qty">Minus Quantity</label>
                        <a href="javascript:void(0)" id="minus_qty" class="ctrl-btn minus">-</a>
                        <label class="screen-reader-text" for="quantity_' . $uniqId . '">' . ($product->get_name()) . ' quantity</label>
                        <input disabled type="number" id="quantity_' . $uniqId . '" class="input-text qty text" name="quantity" value="1"
                            aria-label="Product quantity" min="1" max="' . $max_stock . '" step="1" placeholder="" inputmode="numeric"
                            autocomplete="off">
                        <label class="screen-reader-text" for="plus_qty"> Plus Quantity</label>
                        <a href="javascript:void(0)" id="plus_qty" class="ctrl-btn plus">+</a>
                    </div>
                    <span class="wl-quantity wl-qunatity-plus"><i aria-hidden="true" class="fas fa-plus"></i></span>
                </div>
            </div>
            <a href="?add-to-cart=' . ($productId) . '" data-store-id="' . ($storeId) . '" aria-describedby="woocommerce_loop_add_to_cart_link_describedby_' . ($productId) . '" data-quantity="1" class="btn add_to_cart_button ajax_add_to_cart add_to_cart_product_btn" data-product_id="' . ($productId) . '" ' . ($isVariant ? 'data-variant="' . ($variant_datas[0]["value"]) . '" ' : "") . 'data-product_sku="" aria-label="Add to cart: “' . ($product->get_name()) . '”" rel="nofollow" data-success_message="“' . ($product->get_name()) . '” has been added to your cart">Add to cart</a>';

            echo 'jQuery(`.wl-addto-cart`).html(`<form class="custom-cart-form cart" enctype="multipart/form-data">' . ($cart_html) . '</form>`);';
            echo 'setTimeout(() => {
                jQuery(".wl-quantity").each(function(){
                    jQuery(this).click((e) => {
                        const total = parseInt(jQuery(`[name="quantity"]`).attr("max"));
                        let quantity = parseInt(jQuery(`[name="quantity"]`).val());
                        if(jQuery(this).hasClass("wl-qunatity-plus")){
                            quantity++;
                        }else if(quantity > 1){
                            quantity--;
                        }
                        document.querySelector(".add_to_cart_product_btn").dataset.quantity = (quantity < total ? quantity : total);
                    })
                });
                jQuery(`select[name="variant-option"]`).change(function (e){
                    document.querySelector(".add_to_cart_product_btn").dataset.variant = jQuery(this).find("option:selected").data("value");
                    jQuery(".woocommerce-variation-price .variant-price").html(parseInt(e.target.value).toLocaleString("en-IN", {minimumFractionDigits: 2,maximumFractionDigits: 2}))
                    jQuery(`[name="quantity"]`).attr("max",jQuery(this).find("option:selected").data("stock"));
                    jQuery(`[name="quantity"]`).val(1);
                    document.querySelector(".add_to_cart_product_btn").dataset.quantity = 1;
                })
            }, 200);';
        } else {
            echo 'jQuery(`.wl-addto-cart`).html(`<input type="submit" data-security="723de9da27" data-variation_id="" data-product_id="' . ($productId) . '" class="_cwg_popup_submit " value="Out of stock">`);';
        }
        echo '});</script>';
    }
}
