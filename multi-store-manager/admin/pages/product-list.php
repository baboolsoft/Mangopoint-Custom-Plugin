<div class="msm-wrap shop-manager">
    <div class="title-bar">
        <h1>Product List | <?= ucwords($store->getStoreName($storeId)) ?></h1>
        <div>
            <?php
            if (isset($isStorePage) && $isStorePage == false) {
                echo '<a href="' . admin_url() . 'admin.php?page=multi-store-manager/manage-store" class="button button-primary">Back to Store</a>';
            }
            ?>
            <button disabled class="update-product button button-primary">Update Products</button>
            <button class="sync-product button button-primary">Sync All Products</button>
            <a href="<?= admin_url() ?>admin.php?page=multi-store-manager/shipping-fare&store_info=<?= (isset($isStorePage) && $isStorePage == true) ? 'true' : 'false' ?>&store_id=<?= $storeId ?>" class="button button-primary">Manage Shipping Fare</a>
            <a href="<?= admin_url() ?>admin.php?page=multi-store-manager/orders-list&store_info=<?= (isset($isStorePage) && $isStorePage == true) ? 'true' : 'false' ?>&store_id=<?= $storeId ?>" class="button button-primary">View Orders</a>
        </div>
    </div>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th width="50">#</th>
                <th width="10"></th>
                <th>Product Name</th>
                <th width="150">Price</th>
                <th width="150">Available Stock</th>
                <th>Expected Delivery (in Days)</th>
                <th>Product Order</th>
                <th width="150">Set as Best</th>
            </tr>
        </thead>
        <tbody class="wc-multi-store-products-list">
            <?php
            if (count($productList) == 0) {
                echo '<tr class="no-data">
                        <td colspan="8">No stores found.</td>
                    </tr>';
            } else {
                foreach ($productList as $key => $item) {
                    echo '<tr class="wc-product-item" data-id="' . $item["id"] . '" data-index="' . $key . '">
                        <td>' . ($key + 1) . '</td>
                        <td>
                            <button class="status-toggler' . ($item["status"] ? ' active' : '') . '" title="Mark as active">
                                <i class="dashicons dashicons-yes"></i>
                            </button>
                        </td>
                        <td>' . $item["name"] . (($item["variant"] > 0) ? " <br/> <small>Quantity: " . $item["variant"] . "</small>" : "") . '</td>
                        <td>
                            <input type="number" name="price" class="field" value="' . ($item["price"]) . '" min="0">
                        </td>
                        <td>
                            <input type="number" name="stock" class="field" value="' . ($item["stock"]) . '" min="-1">
                        </td>
                        <td>
                            <input type="number" name="delivery_estimate" class="field" value="' . ($item["delivery_estimate"]) . '" min="0">
                        </td>
                        <td>
                            <input type="number" name="order" class="field" value="' . ($item["order"]) . '" min="0">
                        </td>
                        <td>
                            <select name="best_selling" class="field">
                                <option value="0" ' . ($item["best_selling"] == 0 ? 'selected' : '') . '>No</option>
                                <option value="1" ' . ($item["best_selling"] == 1 ? 'selected' : '') . '>Yes</option>
                            </select>
                        </td>
                    </tr>';
                }
            }
            ?>
        </tbody>
    </table>
</div>

<script>
    jQuery(document).ready(function($) {
        jQuery('.wp-submenu li:has(a[href="admin.php?page=multi-store-manager/manage-store"])').addClass('current');

        const products = <?= json_encode($productList); ?>;
        const updateValue = [];

        jQuery('.wc-product-item').each(function() {
            const status_toggler = jQuery(this).find('.status-toggler');
            jQuery(this).find('.field').on('change', (e) => {
                setTimeout(() => {
                    updateChanges(this);
                }, 300);
            });
            jQuery(status_toggler).click(() => {
                if (jQuery(status_toggler).hasClass('active')) {
                    jQuery(status_toggler).removeClass('active');
                } else {
                    jQuery(status_toggler).addClass('active');
                }
                setTimeout(() => {
                    updateChanges(this);
                }, 300);
            });
        });

        const updateChanges = (ele) => {
            const productIndex = jQuery(ele).data("index");
            const item = products[productIndex];

            const value = {
                ...item,
                price: parseInt(jQuery(ele).find('[name="price"]').val()),
                stock: parseInt(jQuery(ele).find('[name="stock"]').val()),
                delivery_estimate: parseInt(jQuery(ele).find('[name="delivery_estimate"]').val()),
                order: parseInt(jQuery(ele).find('[name="order"]').val()),
                best_selling: parseInt(jQuery(ele).find('[name="best_selling"]').val()),
                status: parseInt(jQuery(ele).find('.status-toggler').hasClass('active') ? 1 : 0)
            }

            const index = updateValue.findIndex(i => i.id == value.id && i.variant == value.variant);

            if (index === -1) {
                updateValue.push(value);
            } else if (
                (value.price != item.price) ||
                (value.stock != item.stock) ||
                (value.delivery_estimate != item.delivery_estimate) ||
                (value.order != item.order) ||
                (value.best_selling != item.best_selling) ||
                (value.status != item.status)
            ) {
                updateValue[index] = value;
            }
            validateUpdate();
        };

        const validateUpdate = () => {
            if (updateValue.length > 0) {
                jQuery('.update-product').removeAttr('disabled');
            } else {
                jQuery('.update-product').attr('disabled', 'disabled');
            }
        }

        jQuery('.update-product').click(function() {
            jQuery.ajax({
                data: {
                    products: updateValue,
                    update: true
                },
                url: `${window.location.origin}/wp-json/multi-store-manager/v1/api/update-product-info/`,
                type: 'POST',
                beforeSend: () => {
                    jQuery(this).attr('disabled', 'disabled').text('Updating...');
                },
                success: ({
                    status
                }) => {
                    if (status) {
                        jQuery(this).removeAttr('disabled').text('Update Products');
                        updateValue.length = 0;
                        validateUpdate();
                        alert('Products updated successfully!');
                    } else {
                        alert('Error updating products!');
                    }
                },
                error: (error) => {
                    jQuery(this).removeAttr('disabled').text('Update Products');
                    alert('Error updating products!');
                }
            });
        });

        jQuery('.sync-product').click(function(e) {
            e.preventDefault();
            jQuery.ajax({
                url: `${window.location.origin}/wp-json/multi-store-manager/v1/api/sync-product/`,
                type: 'POST',
                data: {
                    products: products
                },
                beforeSend: () => {
                    jQuery(this).attr('disabled', 'disabled').text('Syncing...');
                },
                success: ({
                    status
                }) => {
                    if (status) {
                        jQuery(this).removeAttr('disabled').text('Sync All Products');
                        alert('Products synced successfully!');
                    } else {
                        alert('Error syncing products!');
                    }
                },
                error: (error) => {
                    jQuery(this).removeAttr('disabled').text('Sync All Products');
                    alert('Error syncing products!');
                }
            });
        });
    });
</script>