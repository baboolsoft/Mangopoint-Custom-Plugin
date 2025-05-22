<div class="msm-wrap shop-manager">
    <div class="title-bar">
        <h1>Orders List | Multi Store Manager (<?= $count ?>)</h1>
        <div>
            <?php
            if (isset($isStorePage) && $isStorePage == false) {
                echo '<a href="' . admin_url() . 'admin.php?page=multi-store-manager/manage-store" class="button button-primary">Back to Store</a>';
            }
            ?>
            <a href="<?= admin_url() ?>admin.php?page=multi-store-manager/product-inventory&store_info=<?= (isset($isStorePage) && $isStorePage == true) ? 'true' : 'false' ?>&store_id=<?= $storeId ?>" class="button button-primary">Manage Products</a>
            <a href="<?= admin_url() ?>admin.php?page=multi-store-manager/shipping-fare&store_info=<?= (isset($isStorePage) && $isStorePage == true) ? 'true' : 'false' ?>&store_id=<?= $storeId ?>" class="button button-primary">Manage Shipping Fare</a>
        </div>
    </div>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th width="100">#</th>
                <th>Customer Name</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody class="wc-multi-store-stores-list">
            <?php
            if (count($list) == 0) {
                echo '<tr class="no-data">
                        <td colspan="6">No Orders found.</td>
                    </tr>';
            } else {
                foreach ($list as $key => $value) {
                    echo '<tr class="multi_store_order_item">
                        <td>
                            <a target="blank" title="Edit order: #' . $value["id"] . '" href="' . (admin_url()) . 'post.php?post=' . $value["id"] . '&action=edit">
                                <strong>#' . $value["id"] . '</strong>
                            </a>
                        </td>
                        <td><strong>' . $value["name"] . '</strong></td>
                        <td>' . $value["total"] . '</td>
                        <td>
                            <span class="chip ' . $value["status"] . '">' . ucwords($value["status"]) . '</span>
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
    });
</script>