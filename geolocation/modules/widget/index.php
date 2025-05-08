<?php

// Define the widget class
class Location_Based_Products extends WP_Widget
{

    // Constructor: Set up the widget options
    public function __construct()
    {
        if (function_exists('is_plugin_active')) {
            parent::__construct(
                'location_based_products', // Base ID
                'Location based Products List', // Name
                array('description' => __('A custom widget to list products based on location.', 'text_domain'))
            );

            add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        }
    }

    public function widget($args, $instance)
    {
        $html = "";
        $limit = (int)apply_filters('widget_limit', $instance['limit']) ?? -1;
        $categories = apply_filters('widget_categories', $instance['categories']) ?? [];
        $sort = apply_filters('widget_categories', $instance['sort']) ?? [];

        for ($i = 0; $i < ($limit > 0 ? $limit : 12); $i++) {
            $html .= '<div class="skeleton-card-item">
                <div class="skeleton-card">
                    <div class="skeleton-image"></div>
                    <div class="skeleton-content">
                        <div class="skeleton-title"></div>
                        <div class="skeleton-price"></div>
                        <div class="skeleton-btn"></div>
                    </div>
                </div>
            </div>';
        }

        echo '<div class="lbpl" data-limit="' . $limit . '" data-category="' . json_encode($categories) . '" data-sort="' . $sort . '">
            <h2 class="elementor-heading-title elementor-size-default">' . apply_filters('widget_title', $instance['title']) . '</h2>
            <div class="woocommerce result">
                <ul class="products" data-layout-mode="grid">
                    <div class="skeleton-wrapper">
                        ' . $html . '
                    </div>
                </ul>
            </div>
        </div>';
    }

    public function form($instance)
    {

        $title = !empty($instance['title']) ? $instance['title'] : __('Section Title', 'text_domain');
        $limit = !empty($instance['limit']) ? $instance['limit'] : __('Product Limit', 'text_domain');
        $cats = !empty($instance['categories']) ? $instance['categories'] : [];
        $sort = !empty($instance['sort']) ? $instance['sort'] : 'menu_order';

        $categories = get_terms(array(
            'taxonomy'   => 'product_cat',
            'orderby'    => 'name',
            'order'      => 'ASC',
            'hide_empty' => true,
        ));;

        echo '<p>
            <label for="' . esc_attr($this->get_field_id('title')) . '">Section Title</label>
            <input class="widefat" id="' . esc_attr($this->get_field_id('title')) . '" name="' . esc_attr($this->get_field_name('title')) . '" type="text" value="' . esc_attr($title) . '">
        </p>
        <p style="padding-top: 1rem">
            <label for="' . esc_attr($this->get_field_id('limit')) . '">No. of product to display</label>
            <input class="widefat" id="' . esc_attr($this->get_field_id('limit')) . '" name="' . esc_attr($this->get_field_name('limit')) . '" type="number" value="' . esc_attr($limit) . '">
            <small>Set the limit value to -1 to display all products.</small>
        </p>
        <p style="padding-top: 1rem">
            <label for="' . esc_attr($this->get_field_id('categories')) . '">Category</label>
            <select multiple="multiple" class="widefat" id="' . esc_attr($this->get_field_id('categories')) . '" name="' . esc_attr($this->get_field_name('categories')) . '[]" style="height: ' . (18 * count($categories)) . 'px">';
        foreach ($categories as $key => $cat) {
            echo '<option value="' . esc_attr($cat->term_id) . '"' . (in_array($cat->term_id, $cats) ? " selected" : "") . '>
                         ' . esc_html($cat->name) . '
                    </option>';
        }
        echo '</select>
            <small>Leave this field empty to display all categories.</small>
        </p>
        <p style="padding-top: 1rem">
            <label for="' . esc_attr($this->get_field_id('sort')) . '">Sort By</label>
            <select class="widefat" id="' . esc_attr($this->get_field_id('sort')) . '" name="' . esc_attr($this->get_field_name('sort')) . '">
                <option ' . ($sort == "menu-order" ? "selected " : "") . 'value="menu-order">Menu Order</option>
                <option ' . ($sort == "best-selling" ? "selected " : "") . 'value="best-selling">Best Selling Product</option>
            </select>
        </p>';
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['limit'] = (!empty($new_instance['limit'])) ? strip_tags($new_instance['limit']) : -1;
        $instance['sort'] = (!empty($new_instance['sort'])) ? strip_tags($new_instance['sort']) : 'menu_order';
        $instance['categories'] = (! empty($new_instance['categories'])) ? array_map('intval', $new_instance['categories']) : array();
        return $instance;
    }

    function enqueue_assets()
    {
        $path = plugin_dir_url(__FILE__);
        wp_enqueue_style('style', "{$path}style.css", [], '1.0', 'all');
        wp_enqueue_style('popup', "{$path}popup.css", [], '1.0', 'all');
        wp_enqueue_script('script', "{$path}script.js", ["jquery"], '1.0', true);

        // adding map script
        global $wpdb;
        $query = $wpdb->prepare("SELECT * FROM `geo_location` WHERE id = 1");
        $result = $wpdb->get_row($query);
        if (isset($result->api)) {
            $api = base64_decode($result->api);
            $google_maps_url = "https://maps.googleapis.com/maps/api/js?key={$api}&libraries=places&callback=initMap";
            wp_enqueue_script('google-maps', $google_maps_url, [], null, true);
        }
    }
}
