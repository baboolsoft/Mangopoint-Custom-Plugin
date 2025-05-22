<div class="msm-wrap shop-manager">
    <div class="title-bar">
        <h1>Shopping Fare | <?= ucwords($store->getStoreName($storeId)) ?></h1>
        <div>
            <?php
            if (isset($isStorePage) && $isStorePage == false) {
                echo '<a href="' . admin_url() . 'admin.php?page=multi-store-manager/manage-store" class="button button-primary">Back to Store</a>';
            }
            ?>
            <button class="add-btn button button-primary">Add Shipping Fare</button>
            <a href="<?= admin_url() ?>admin.php?page=multi-store-manager/product-inventory&store_info=<?= (isset($isStorePage) && $isStorePage == true) ? 'true' : 'false' ?>&store_id=<?= $storeId ?>" class="button button-primary">Manage Products</a>
            <a href="<?= admin_url() ?>admin.php?page=multi-store-manager/orders-list&store_info=<?= (isset($isStorePage) && $isStorePage == true) ? 'true' : 'false' ?>&store_id=<?= $storeId ?>" class="button button-primary">View Orders</a>
        </div>
    </div>
    <form data-title="Shipping Fare" class="fare-form form">
        <input type="hidden" name="manage" value="ship">
        <input type="hidden" name="storeId" value="<?= $storeId ?>">
        <table class="field-wrapper">
            <tbody>
                <tr class="field-group">
                    <td class="label">
                        <label for="km">Distance</label>
                    </td>
                    <td>
                        <select name="type" id="type">
                            <option value="with-in">With In</option>
                            <option value="more-than">More Than</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="km" id="km" placeholder="Enter Kilometer to cover" required>
                    </td>
                </tr>
                <tr class="field-group">
                    <td class="label">
                        <label for="fare">Shipping Fare(₹)</label>
                    </td>
                    <td>
                        <input type="text" name="fare" id="fare" placeholder="Enter shipping fare" required>
                    </td>
                </tr>
            </tbody>
        </table>
        <button class="submit button button-primary">Save Fare</button>
        <div class="error hidden"></div>
    </form>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th width="50">#</th>
                <th>Label</th>
                <th width="120">Fare(₹)</th>
                <th class="action"></th>
            </tr>
        </thead>
        <tbody class="wc-multi-store-fare-list">
            <?php
            if (count($fare_list) == 0) {
                echo '<tr class="no-data">
                        <td colspan="4">No Shipping Fare found.</td>
                    </tr>';
            } else {
                foreach ($fare_list as $key => $value) {

                    echo '<tr class="fare-item" data-id="' . $value->id . '" data-index="' . $key . '" data-km="' . $value->km . '" data-type="' . $value->type . '" data-fare="' . $value->fare . '">
                        <td>' . ($key + 1) . '</td>
                        <td> ' . ucwords(str_replace("-", " ", $value->type)) . ' ' . $value->km . ' km </td>
                        <td>' . number_format($value->fare, 2) . '</td>
                        <td class="action">
                            <button class="edit-fare button button-secondary">Edit</button>
                            <button class="delete-fare button button-secondary">Delete</button>
                        </td>
                    </tr>';
                }
            }
            ?>
        </tbody>
    </table>
</div>

<script>
    jQuery(document).ready(function() {
        jQuery('.wp-submenu li:has(a[href="admin.php?page=multi-store-manager/manage-store"])').addClass('current');
        jQuery('.fare-item').each(function() {
            const id = jQuery(this).data('id');
            jQuery(this).find('.edit-fare').on('click', () => {
                const {
                    id,
                    km,
                    fare,
                    type
                } = jQuery(this).data();

                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'id';
                hiddenInput.value = id;
                jQuery('.fare-form').append(hiddenInput);

                jQuery('.fare-form input[name="type"]').val(type);
                jQuery('.fare-form input[name="km"]').val(km);
                jQuery('.fare-form input[name="fare"]').val(fare);

                if (jQuery('.fare-form').css('display') === "none") {
                    jQuery('.fare-form').slideToggle();
                }
            });
            jQuery(this).find('.delete-fare').on('click', () => {
                const delEle = jQuery(this).find('.delete-fare');
                if (confirm("Are you sure you want to delete this shipping fare?")) {
                    jQuery.ajax({
                        url: `${window.location.origin}/wp-json/multi-store-manager/v1/api/delete/`,
                        type: 'POST',
                        data: {
                            id,
                            delete: true,
                            table: 'fare',
                        },
                        beforeSend: function() {
                            jQuery(delEle).attr('disabled', true).text('Deleting...');
                        },
                        success: ({
                            status = false,
                            message
                        }) => {
                            if (status) {
                                setTimeout(() => {
                                    location.reload(true);
                                }, 1000);
                            } else {
                                jQuery(delEle).attr('disabled', false).text('Delete');
                            }
                        },
                        error: function() {
                            alert('Error deleting fare.');
                        }
                    });
                }
            });
        });
    });
</script>