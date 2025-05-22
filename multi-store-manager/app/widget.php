<?php

class Multi_Store_Product_list extends WP_Widget
{

    public function __construct()
    {
        parent::__construct(
            'multi_store_product_list',
            'Multi Store Product list',
            ['description' => __('A Custom Widget to list product list', 'text_domain')]
        );
    }

    public function widget($args, $instance)
    {
        $html = "";
        $limit = (int)apply_filters('widget_limit', $instance['limit']) ?? -1;
        $sort = apply_filters('widget_categories', $instance['sort']) ?? [];
        $categories = apply_filters('widget_categories', $instance['categories']) ?? [];
        $pagination = apply_filters('widget_categories', $instance['pagination']) ?? 0;
        $tabs = apply_filters('widget_categories', $instance['tabs']) ?? 0;
        $options = apply_filters('widget_categories', $instance['options']) ?? 'same-day-delivery';

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

        echo '<div class="lbpl" data-limit="' . $limit . '" data-category="' . json_encode($categories) . '" data-sort="' . $sort . '" data-pagination="' . $pagination . '" data-tabs="' . $tabs . '" data-options="' . $options . '">
            <h2 class="elementor-heading-title elementor-size-default">' . apply_filters('widget_title', $instance['title']) . '</h2>';
        if ($tabs) {
            $id = "filter_".(strtolower(str_replace(" ","_",$instance['title'])));
            echo '<div class="lbpl-filters">
                <div class="lbpl-filter">
                    <label for="'.$id.'_same_day_delivery">
                        <input type="radio" id="'.$id.'_same_day_delivery" name="'.$id.'" value="same-day-delivery" checked> Same Day Delivery
                    </label>
                    <label for="'.$id.'_scheduled_delivery">
                        <input type="radio" id="'.$id.'_scheduled_delivery" name="'.$id.'" value="scheduled-delivery"> Scheduled Delivery
                    </label>
                </div>
            </div>';
        }
        echo '<div class="woocommerce result">
                <ul class="products" data-layout-mode="grid">
                    <div class="skeleton-wrapper">
                        ' . $html . '
                    </div>
                </ul>
            </div>';
        if ($pagination) {
            echo '<div class="paginator"></div>';
        }
        echo '</div>';
    }

    public function form($instance)
    {

        $title = !empty($instance['title']) ? $instance['title'] : __('Section Title', 'text_domain');
        $limit = !empty($instance['limit']) ? $instance['limit'] : __(12, 'text_domain');
        $sort = !empty($instance['sort']) ? $instance['sort'] : 'menu_order';
        $pagination = !empty($instance['pagination']) ? $instance['pagination'] : 0;
        $tabs = !empty($instance['tabs']) ? $instance['tabs'] : 0;
        $options = !empty($instance['options']) ? $instance['options'] : 'same-day-delivery';
        $cats = !empty($instance['categories']) ? $instance['categories'] : [];

        $categories = get_terms(array(
            'taxonomy'   => 'product_cat',
            'orderby'    => 'name',
            'order'      => 'ASC',
            'hide_empty' => true,
        ));

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
        </p>
        <p style="padding-top: 1rem">
            <label for="' . esc_attr($this->get_field_id('pagination')) . '">Enable Pagination</label>
            <select class="widefat" id="' . esc_attr($this->get_field_id('pagination')) . '" name="' . esc_attr($this->get_field_name('pagination')) . '">
                <option ' . ($pagination == "0" ? "selected " : "") . 'value="0">No</option>
                <option ' . ($pagination == "1" ? "selected " : "") . 'value="1">Yes</option>
            </select>
        </p>
        <p style="padding-top: 1rem">
            <label for="' . esc_attr($this->get_field_id('tabs')) . '">Show Filter tabs</label>
            <select class="widefat" id="' . esc_attr($this->get_field_id('tabs')) . '" name="' . esc_attr($this->get_field_name('tabs')) . '">
                <option ' . ($tabs == "0" ? "selected " : "") . 'value="0">No</option>
                <option ' . ($tabs == "1" ? "selected " : "") . 'value="1">Yes</option>
            </select>
        </p>
        <p style="padding-top: 1rem">
            <label for="' . esc_attr($this->get_field_id('options')) . '">By Default</label>
            <select class="widefat" id="' . esc_attr($this->get_field_id('options')) . '" name="' . esc_attr($this->get_field_name('options')) . '">
                <option ' . ($options == "0" ? "same-day-delivery " : "") . 'value="same-day-delivery">Same Day Delivery</option>
                <option ' . ($options == "1" ? "same-day-delivery " : "") . 'value="scheduled-delivery">Scheduled Delivery</option>
            </select>
        </p>';
    }

    public function update($new_instance, $old_instance)
    {
        $instance = array();

        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['limit'] = (!empty($new_instance['limit'])) ? strip_tags($new_instance['limit']) : -1;
        $instance['sort'] = (!empty($new_instance['sort'])) ? strip_tags($new_instance['sort']) : 'menu_order';
        $instance['pagination'] = (! empty($new_instance['pagination'])) ? strip_tags($new_instance['pagination']) : 0;
        $instance['tabs'] = (! empty($new_instance['tabs'])) ? strip_tags($new_instance['tabs']) : 0;
        $instance['options'] = (! empty($new_instance['options'])) ? strip_tags($new_instance['options']) : 'same-day-delivery';
        $instance['categories'] = (! empty($new_instance['categories'])) ? array_map('intval', $new_instance['categories']) : array();
        return $instance;
    }
}

// Register the widget
function register_my_custom_widget()
{
    register_widget('Multi_Store_Product_list');
}
add_action('widgets_init', 'register_my_custom_widget');
