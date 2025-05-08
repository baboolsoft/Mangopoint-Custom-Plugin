<?php
class admin
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'addMenu']);
    }

    public function addMenu()
    {
        add_menu_page(
            'Brix Value',
            'Brix Value',
            'manage_options',
            'brix-value',
            [$this, 'pluginContext'],
            'dashicons-megaphone',
            6
        );

        add_submenu_page(
            'brix-value',
            'Config &lsaquo; Brix Value',
            'Config',
            'manage_options',
            'brix-value/config',
            [$this, 'configPage']
        );
    }

    function pluginContext()
    {
        echo '<div class="wrap">
            <h1>Brix Value Settings</h1> <hr/>
            <div id="push-notification-description">
                
            <h2>What is Brix Value Plugins?</h2>
                <p>
                    <strong>Brix value</strong> (also referred to as degrees Brix, written as °Bx) is a scale used to measure the
                    sugar content in an aqueous solution, commonly used in the food and beverage industry. It indicates the
                    percentage of sucrose (sugar) by weight in a liquid. The Brix scale is often used to measure the sweetness of
                    liquids such as fruit juices, wines, soft drinks, and other beverages, as well as in food processing.
                </p>
                
                <h2>How to Use a Brix Value Plugin on WordPress</h2>
                <p>
                    <strong>1. Install a Brix Value Plugin</strong>
                </p>
                <ul>
                    <li>step1: Go to your WordPress dashboard.</li>
                    <li>step2: Navigate to <a href="' . get_site_url() . '/wp-admin/plugin-install.php"><em>Plugins > Add New.</em></a></li>
                    <li>step3: choose <em>Upload Plugin</em> Option</li>
                    <li>step4: Drag and Drop or Upload Brix Value Plugin file.</li>
                    <li>step5: Click Install Now and then Activate.</li>
                </ul>
                <p><strong>2. Configure the Plugin</strong></p>
                <p>
                    Once installed and activated, you need to assign minimum and maximum value for brix scale inside plugin\'s <a href="' . get_site_url() . '/wp-admin/admin.php?page=brix-value/config"><em>config page.</em></a>.
                </p>
            </div>
        </div>';
    }

    function configPage()
    {
        global $wpdb;
        $query = $wpdb->prepare("SELECT * FROM `brix_config` WHERE id = 1");
        $result = $wpdb->get_row($query);

        $minVal = isset($_POST['minVal']) ? $_POST['minVal'] : (isset($result->minValue) ? $result->minValue : "");
        $maxVal = isset($_POST['maxVal']) ? $_POST['maxVal'] : (isset($result->maxValue) ? $result->maxValue : "");

        echo '<form method="post" action="" class="wrap">
            ' . (wp_nonce_field('submit_brix_value_nonce', 'brix_value_nonce')) . '
            <h1>Brix Scale configuration</h1>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="minVal">Minimum Value</label>
                        </th>
                        <td><input name="minVal" value="'.$minVal.'" type="number" class="regular-text" id="minVal"></td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="maxVal">Maximum Value</label>
                        </th>
                        <td><input name="maxVal" value="'.$maxVal.'" type="number" class="regular-text" id="maxVal"></td>
                    </tr>
                    <tr>
                        <th scope="row" colspan="2">
                            <input type="submit" name="submit_brix_value" id="update" class="button button-primary button-large" value="Update Value">
                        </th>
                    </tr>
                </tbody>
            </table>
        </form>';

        if (isset($_POST['submit_brix_value']) && isset($_POST['brix_value_nonce']) && wp_verify_nonce($_POST['brix_value_nonce'], 'submit_brix_value_nonce')) {
            $val = [
                'minValue' => sanitize_text_field($_POST['minVal']),
                'maxValue' => sanitize_text_field($_POST['maxVal'])
            ];
            if (empty($result)) {
                $wpdb->insert('brix_config', $val);
            } else {
                $wpdb->update('brix_config', $val, ['id' => 1]);
            }
        }
    }
}
