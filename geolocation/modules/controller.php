<?php

class controller
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('template_redirect', [$this, 'validateCheckout']);
    }

    // adding menu
    public function addMenu()
    {
        add_menu_page(
            'Geo Location',
            'Geo Location',
            'manage_options',
            'geo-location',
            [$this, 'pluginContext'],
            'dashicons-megaphone',
            7
        );

        add_submenu_page(
            'geo-location',
            'Config &lsaquo; Geo Location',
            'Config',
            'manage_options',
            'geo-location/config',
            [$this, 'configPage']
        );
    }

    // plugin context
    function pluginContext()
    {
        echo '<div class="wrap">
            <h1>Geo Location Settings</h1> <hr/>
        </div>';
    }

    // config page content
    function configPage()
    {
        global $wpdb;
        $query = $wpdb->prepare("SELECT * FROM `geo_location` WHERE id = 1");
        $result = $wpdb->get_row($query);

        $api = isset($_POST['api']) ? $_POST['api'] : (isset($result->api) ? base64_decode($result->api) : "-");
        $slug = isset($_POST['slug']) ? $_POST['slug'] : (isset($result->slug) ? base64_decode($result->slug) : "-");

        echo '<form method="post" action="" class="wrap">
            ' . (wp_nonce_field('submit_geo_config', 'geo_config')) . '
            <h1>Geo Location configuration</h1>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="api">Google Map API</label></th>
                        <td><input name="api" value="' . $api . '" class="regular-text" id="api"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="slug">Default store slug</label></th>
                        <td><input name="slug" value="' . $slug . '" class="regular-text" id="slug"></td>
                    </tr>
                    <tr>
                        <th scope="row" colspan="2">
                            <input type="submit" name="submit_config" id="update" class="button button-primary button-large" value="Update Value">
                        </th>
                    </tr>
                </tbody>
            </table>
        </form>';

        if (isset($_POST['submit_config']) && isset($_POST['geo_config']) && wp_verify_nonce($_POST['geo_config'], 'submit_geo_config')) {
            $val = [
                'api' => base64_encode(sanitize_text_field($_POST['api'])),
                'slug' => base64_encode(sanitize_text_field($_POST['slug']))
            ];
            if (empty($result)) {
                $wpdb->insert('geo_location', $val);
            } else {
                $wpdb->update('geo_location', $val, ['id' => 1]);
            }
        }
    }

    function validateCheckout()
    {
        if (is_checkout()) {
            $ids = [];
            if (WC()->cart) {
                echo '<script>
                document.addEventListener("DOMContentLoaded", function () {
                    document.body.classList.add("lbpl-checkout-page", "lbpl-loading", "lbpl-disable");
                });
                </script>';
                foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                    array_push($ids, $cart_item['product_id']);
                }
            }
            echo '<script>
                const lbpl_validateCheckoutVal = true;
                const cartIds = ' . json_encode($ids) . ';
            </script>';
        }
    }
}
