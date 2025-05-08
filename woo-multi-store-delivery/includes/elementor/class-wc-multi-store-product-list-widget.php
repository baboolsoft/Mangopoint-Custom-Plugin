<?php

/**
 * Product List Widget for Elementor
 */
class WC_Multi_Store_Product_List_Widget extends \Elementor\Widget_Base
{
    /**
     * Get widget name
     */
    public function get_name()
    {
        return 'wc_multi_store_product_list';
    }

    /**
     * Get widget title
     */
    public function get_title()
    {
        return __('Multi Store Product List', 'wc-multi-store');
    }

    /**
     * Get widget icon
     */
    public function get_icon()
    {
        return 'eicon-products';
    }

    /**
     * Get widget categories
     */
    public function get_categories()
    {
        return ['wc-multi-store'];
    }

    /**
     * Register widget controls
     */
    protected function _register_controls()
    {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('Content', 'wc-multi-store'),
            ]
        );

        $this->add_control(
            'title',
            [
                'label' => __('Title', 'wc-multi-store'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Products', 'wc-multi-store'),
            ]
        );

        $this->add_control(
            'out_of_stock_message',
            [
                'label' => __('Out of Stock Message', 'wc-multi-store'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Out of Stock', 'wc-multi-store'),
            ]
        );

        $this->add_control(
            'products_per_page',
            [
                'label' => __('Products Per Page', 'wc-multi-store'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 12,
                'min' => 1,
                'max' => 100,
            ]
        );

        $this->add_control(
            'show_best_selling',
            [
                'label' => __('Show Best Selling Products Only', 'wc-multi-store'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'wc-multi-store'),
                'label_off' => __('No', 'wc-multi-store'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->add_control(
            'show_tabs',
            [
                'label' => __('Show Feature tabs', 'wc-multi-store'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'wc-multi-store'),
                'label_off' => __('No', 'wc-multi-store'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->add_control(
            'show_pagination',
            [
                'label' => __('Show Pagination', 'wc-multi-store'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'wc-multi-store'),
                'label_off' => __('No', 'wc-multi-store'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style',
            [
                'label' => __('Style', 'wc-multi-store'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __('Title Color', 'wc-multi-store'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wc-multi-store-product-list-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'filter_color',
            [
                'label' => __('Filter Color', 'wc-multi-store'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wc-multi-store-product-filter' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output
     */
    protected function render()
    {
        $settings = $this->get_settings_for_display();

        $title = $settings['title'];
        $out_of_stock_message = $settings['out_of_stock_message'];
        $products_per_page = $settings['products_per_page'] ?? 12;
        $show_best_selling = $settings['show_best_selling'] === 'yes';
        $show_tabs = $settings['show_tabs'] === 'yes';
        $show_pagination = $settings['show_pagination'] === 'yes';

        $skeleton = '';
        for ($i = 0; $i < ($products_per_page > 0 ? $products_per_page : 12); $i++) {
            $skeleton .= '<div class="skeleton-card-item">
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

        echo '<div class="wc-multi-store-product-list" data-products-per-page="' . esc_attr($products_per_page) . '" data-show-best-selling="' . ($show_best_selling ? 'yes' : 'no') . '" data-show-pagination="' . ($show_pagination ? 'yes' : 'no') . '">
            <h2 class="wc-multi-store-product-list-title">' . esc_html($title) . '</h2>';
        if ($show_tabs) {
            echo '<div class="wc-multi-store-product-filters">
                <div class="wc-multi-store-product-filter">
                    <label>
                        <input type="radio" name="delivery_type" value="same_day" checked> Same Delivery
                    </label>
                    <label>
                        <input type="radio" name="delivery_type" value="estimated"> Estimated Delivery
                    </label>
                </div>
            </div>';
        }
        echo '<div id="wc-multi-store-product-container" class="wmsp wc-multi-store-product-container">
            <div class="skeleton-wrapper">
                ' . $skeleton . '
            </div>
        </div>';
        if ($show_pagination) {
            echo '<div id="wc-multi-store-pagination" class="wc-multi-store-pagination"></div>';
        }
        echo '</div>';
    }
}
