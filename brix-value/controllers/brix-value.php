<?php

class brixValue
{

    public function __construct()
    {
        add_action('woocommerce_product_options_general_product_data', [$this, 'add_brix_field']);
        add_action('woocommerce_process_product_meta', [$this, 'save_brix_value']);
        add_action('woocommerce_after_single_product', [$this, 'display_brix_scale'], 20);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_css']);
    }

    // Add a custom field to the product general options tab
    function add_brix_field()
    {
        woocommerce_wp_text_input(array(
            'id'          => '_brix_value', // Custom field ID
            'label'       => __('Brix Value', 'woocommerce'),
            'description' => __('Enter product brix value here.', 'woocommerce'),
            'desc_tip'    => true,
        ));
    }

    // Save the custom field value
    function save_brix_value($post_id)
    {
        $custom_field_value = isset($_POST['_brix_value']) ? sanitize_text_field($_POST['_brix_value']) : '';
        update_post_meta($post_id, '_brix_value', $custom_field_value);
    }

    // Display the custom field value on the product page
    function display_brix_scale()
    {
        global $post;
        $brixValue = get_post_meta($post->ID, '_brix_value', true);

        if (! empty($brixValue)) {
            global $wpdb;

            $query = $wpdb->prepare("SELECT * FROM `brix_config` WHERE id = 1");
            $result = $wpdb->get_row($query);

            $minVal = (isset($result->minValue) ? $result->minValue : 0);
            $maxVal = (isset($result->maxValue) ? $result->maxValue : 0);

            if (($minVal < $maxVal) && (($minVal <= $brixValue) && ($maxVal >= $brixValue))) {

                $html = '<div class="brix-scale-container">
                    <div class="brix-scale">';
                for ($i = $minVal; $i <= $maxVal; $i++) {
                    $position = ($i - $minVal) * (100 / ($maxVal - $minVal));
                    $issmall = $i % 5 == 0 ? '' : ' small';
                    $html .= '<div class="tick' . ($issmall) .($brixValue == $i ? " active" : ""). '" style="left: ' . $position . '%;"></div>';
                    $html .= '<div class="tick-label" style="left: ' . $position . '%;">' . $i . '</div>';
                    if ($brixValue == $i) {
                        $html .= '<div class="indicator" id="indicator" style="left: ' . $position . '%;"></div>';
                    }
                }
                $html .= '<div class="indicator-value" id="indicator-value">Brix Value: ' . $brixValue . '</div>
                    </div>
                </div>';

                echo '<script>
                    jQuery(`' . $html . '`).insertAfter(".wl-wishlist-compare-txt");
                </script>';
            }
        }
    }

    function enqueue_css()
    {
        if (is_product()) {
            wp_enqueue_style(
                'style',
                plugin_dir_url(__FILE__) . '../modules/brix-scale/style.css',
                array(),
                '1.0',
                'all'
            );
        }
    }
}
