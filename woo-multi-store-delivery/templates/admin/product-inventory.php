<div class="wrap">
    <h1><?php _e('Product Inventory', 'wc-multi-store'); ?></h1>

    <div class="wc-multi-store-admin-notice notice notice-success" style="display: none;"></div>

    <div class="wc-multi-store-store-selector">
        <form method="get">
            <input type="hidden" name="page" value="wc-multi-store-inventory">

            <select name="store_id" id="wc-multi-store-store-select">
                <option value=""><?php _e('Select a store', 'wc-multi-store'); ?></option>
                <?php foreach ($stores as $store) : ?>
                    <option value="<?php echo esc_attr($store->id); ?>" <?php selected($selected_store_id, $store->id); ?>><?php echo esc_html($store->name); ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="button"><?php _e('Select', 'wc-multi-store'); ?></button>
        </form>
    </div>

    <?php if ($selected_store_id > 0 && !empty($products)) : ?>
        <div class="wc-multi-store-product-inventory">
            <div class="wc-multi-store-save-all-container">
                <button type="button" class="button button-primary wc-multi-store-save-all-products">
                    <?php _e('Save All Changes', 'wc-multi-store'); ?>
                </button>
            </div>

            <h2><?php _e('Products', 'wc-multi-store'); ?></h2>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Product', 'wc-multi-store'); ?></th>
                        <th><?php _e('Price', 'wc-multi-store'); ?></th>
                        <th><?php _e('Stock', 'wc-multi-store'); ?></th>
                        <th><?php _e('Delivery Estimate (Days)', 'wc-multi-store'); ?></th>
                        <th><?php _e('Best Selling', 'wc-multi-store'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product) : ?>
                        <tr data-id="<?php echo esc_attr($product['id']); ?>" data-quantity="<?= isset($product["quantity"]) ? esc_attr($product['quantity']) : "-" ?>">
                            <td>
                                <?php echo esc_html($product['name']); ?>
                                <?php
                                if (isset($product["quantity"])) {
                                    echo '<strong>| Quantity : ' . $product["quantity"] . '</strong>';
                                }
                                ?>
                            </td>
                            <td>
                                <input type="number" class="product-price" value="<?php echo esc_attr($product['price']); ?>" step="0.01" min="0">
                            </td>
                            <td>
                                <input type="number" class="product-stock" value="<?php echo esc_attr($product['stock']); ?>" min="-1">
                                <p class="description"><?php _e('-1 = Exclude, 0 = Out of Stock', 'wc-multi-store'); ?></p>
                            </td>
                            <td>
                                <input type="number" class="product-delivery-estimate" value="<?php echo esc_attr($product['delivery_estimate']); ?>" min="1">
                                <p class="description"><?php _e('1 = Same Day', 'wc-multi-store'); ?></p>
                            </td>
                            <td>
                                <select class="product-best-selling">
                                    <option value="0" <?php selected($product['best_selling'], false); ?>><?php _e('No', 'wc-multi-store'); ?></option>
                                    <option value="1" <?php selected($product['best_selling'], true); ?>><?php _e('Yes', 'wc-multi-store'); ?></option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($selected_store_id > 0) : ?>
        <p><?php _e('No products found.', 'wc-multi-store'); ?></p>
    <?php endif; ?>
</div>

<script>
    jQuery(document).ready(function($) {
        // Store selection
        $('#wc-multi-store-store-select').on('change', function() {
            if ($(this).val()) {
                $(this).closest('form').submit();
            }
        });

        // Save all products
        $('.wc-multi-store-save-all-products').on('click', function() {
            var products = [];

            // Collect data from all product rows
            $('tr[data-id]').each(function() {

                products.push({
                    id: $(this).data('id'),
                    quantity: $(this).data('quantity'),
                    price: $(this).find('.product-price').val(),
                    stock: $(this).find('.product-stock').val(),
                    delivery_estimate: $(this).find('.product-delivery-estimate').val(),
                    best_selling: ($(this).find('.product-best-selling').val()) === '1'
                });
            });

            // Save all products
            $.ajax({
                url: wcMultiStoreAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_multi_store_update_all_products',
                    nonce: wcMultiStoreAdmin.nonce,
                    store_id: <?php echo intval($selected_store_id); ?>,
                    products: products
                },
                beforeSend: () => {
                    jQuery(this).attr('disabled', true).html('updating product...')
                },
                success: (response) => {
                    if (response.success) {
                        $('.wc-multi-store-admin-notice').html(response.data.message).show();
                        setTimeout(function() {
                            $('.wc-multi-store-admin-notice').hide();
                        }, 3000);
                    } else {
                        alert(response.data.message);
                    }
                    jQuery(this).removeAttr('disabled', true).html('Update Product')
                }
            });
        });
    });
</script>