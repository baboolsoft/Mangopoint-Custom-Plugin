<div class="msm-wrap shop-manager">
    <div class="title-bar">
        <h1>Orders List | Multi Store Manager</h1>
        <a href="<?= admin_url() ?>admin.php?page=multi-store-manager/manage-store" class="button button-primary">Back to Store</a>
    </div>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th width="100">#</th>
                <th>Customer Name</th>
                <th>Total</th>
                <th>Status</th>
                <th>Shippin Zone</th>
                <th class="action">Date</th>
            </tr>
        </thead>
        <tbody class="wc-multi-store-stores-list">
            <?php
            $args = array(
                'post_type'      => 'shop_order',
                'posts_per_page' => -1, // Get all orders (you can limit the number here)
                'post_status'    => array('wc-completed', 'wc-pending', 'wc-processing'), // Filter by specific status (optional)
                'orderby'        => 'date',
                'order'          => 'DESC',
                'limit'          => 5
            );
            $orders = get_posts($args);
            foreach ($orders as $order_post) {
                $order = wc_get_order($order_post->ID);
                echo '<tr class="multi_store_order_item">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>';
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