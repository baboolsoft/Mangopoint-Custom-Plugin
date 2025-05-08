<?php
/**
 * Elementor widgets
 */
class WC_Multi_Store_Elementor {
    /**
     * Initialize Elementor widgets
     */
    public function init() {
        // Register widgets
        add_action('elementor/widgets/widgets_registered', array($this, 'register_widgets'));
        
        // Register widget categories
        add_action('elementor/elements/categories_registered', array($this, 'register_widget_categories'));
        
        // Register scripts
        add_action('elementor/frontend/after_register_scripts', array($this, 'register_frontend_scripts'));
        
        // Register styles
        add_action('elementor/frontend/after_register_styles', array($this, 'register_frontend_styles'));
    }
    
    /**
     * Register Elementor widgets
     */
    public function register_widgets($widgets_manager) {
        // Include widget files
        require_once WC_MULTI_STORE_PLUGIN_DIR . 'includes/elementor/class-wc-multi-store-location-widget.php';
        require_once WC_MULTI_STORE_PLUGIN_DIR . 'includes/elementor/class-wc-multi-store-product-list-widget.php';
        
        // Register widgets
        $widgets_manager->register_widget_type(new WC_Multi_Store_Location_Widget());
        $widgets_manager->register_widget_type(new WC_Multi_Store_Product_List_Widget());
    }
    
    /**
     * Register widget categories
     */
    public function register_widget_categories($elements_manager) {
        $elements_manager->add_category(
            'wc-multi-store',
            array(
                'title' => __('Multi Store', 'wc-multi-store'),
                'icon' => 'fa fa-plug',
            )
        );
    }
    
    /**
     * Register frontend scripts
     */
    public function register_frontend_scripts() {
        // Enqueue main script
        wp_enqueue_script('wc-multi-store');
        
        // Enqueue Google Maps API
        $api_key = get_option('wc_multi_store_google_maps_api_key');
        if (!empty($api_key)) {
            wp_enqueue_script('google-maps');
        }
        if (is_checkout()) {
            wp_enqueue_script('custom-checkout-script',plugin_dir_url(__FILE__)."../assets/js/wc-checkout.js", ["jquery"], '1.0', true);
        }
    }
    
    /**
     * Register frontend styles
     */
    public function register_frontend_styles() {
        wp_enqueue_style('wc-multi-store');
    }
}
