<?php

/**
 * Location Selection Widget for Elementor
 */
class WC_Multi_Store_Location_Widget extends \Elementor\Widget_Base
{
    /**
     * Get widget name
     */
    public function get_name()
    {
        return 'wc_multi_store_location';
    }

    /**
     * Get widget title
     */
    public function get_title()
    {
        return __('Multi Store Location', 'wc-multi-store');
    }

    /**
     * Get widget icon
     */
    public function get_icon()
    {
        return 'eicon-map-pin';
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
                'default' => __('Select Your Location', 'wc-multi-store'),
            ]
        );

        $this->add_control(
            'description',
            [
                'label' => __('Description', 'wc-multi-store'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => __('Choose your city to see available products', 'wc-multi-store'),
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
                    '{{WRAPPER}} .wc-multi-store-location-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'description_color',
            [
                'label' => __('Description Color', 'wc-multi-store'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wc-multi-store-location-description' => 'color: {{VALUE}};',
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
        $description = $settings['description'];

        // Get cities from database
        $db = new WC_Multi_Store_DB();
        $cities = $db->get_store_cities();
        $default_city = $db->get_default_store_city();
        $default_city = count($default_city) > 0 ? $default_city[0] : "null";
        $restricted_cities = $db->get_restricted_cities();

        // Render widget
?>
        <div class="wc-multi-store-location">
            <h3 class="wc-multi-store-location-title"><?php echo esc_html($title); ?></h3>
            <p class="wc-multi-store-location-description"><?php echo esc_html($description); ?></p>

            <div class="wc-multi-store-location-selector">
                <select id="wc-multi-store-city-select" class="wc-multi-store-city-select" data-default="<?= $default_city ?>">
                    <option value=""><?php _e('Select a city', 'wc-multi-store'); ?></option>
                    <?php foreach ($cities as $city) : ?>
                        <option value="<?php echo esc_attr($city); ?>"><?php echo esc_html($city); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="wc-multi-store-map" class="wc-multi-store-map" style="display: none; height: 300px; margin-top: 20px;"></div>

            <div id="wc-multi-store-no-store-message" class="wc-multi-store-no-store-message" style="display: none; margin-top: 20px;">
                <p><?php _e('There is no store nearby. We are changing the store to a nearby location.', 'wc-multi-store'); ?></p>
                <button id="wc-multi-store-select-nearby" class="wc-multi-store-select-nearby button"><?php _e('Select Nearby Location', 'wc-multi-store'); ?></button>
            </div>

            <div id="wc-multi-store-restrict-city-message" class="wc-multi-store-no-store-message" style="display: none; margin-top: 20px;">
                <p><?php _e('We\'ve noticed that the selected city is currently restricted. As a result, we\'re automatically redirecting your our main hub to ensure uninterrupted service.', 'wc-multi-store'); ?></p>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                const restrictedCities = <?= json_encode($restricted_cities) ?>;
                // Initialize location selector
                var wcMultiStoreLocation = {
                    init: function() {
                        this.initLocationDetection();
                        this.bindEvents();
                    },

                    initLocationDetection: function() {
                        // Check if location is already stored
                        var storedCity = localStorage.getItem('wc_multi_store_city');

                        if (storedCity) {
                            wcMultiStoreLocation.assignValue(storedCity);
                        } else {
                            // Auto-detect location
                            this.detectLocation();
                        }
                    },

                    detectLocation: function() {
                        if (navigator.geolocation) {
                            navigator.geolocation.getCurrentPosition(function(position) {
                                var lat = position.coords.latitude;
                                var lng = position.coords.longitude;

                                // Use Google Maps Geocoding API to get city
                                var geocoder = new google.maps.Geocoder();
                                var latlng = new google.maps.LatLng(lat, lng);

                                geocoder.geocode({
                                    'latLng': latlng
                                }, function(results, status) {
                                    if (status === google.maps.GeocoderStatus.OK) {
                                        if (results.length) {
                                            let address = results.find(t => t.address_components[0].types.includes("postal_code"));
                                            if (typeof address === "undefined") {
                                                address = results.find(t => t.address_components.map(d => d.types.includes('postal_code')))
                                            } else {
                                                address = results[0];
                                            }

                                            wcMultiStoreLocation.setConfig(address);
                                            const cityComponent = address.address_components.find(d => d.types.indexOf('locality') > -1);
                                            const city = cityComponent ? cityComponent.long_name : null;

                                            if (cityComponent && cityComponent.long_name) {
                                                localStorage.setItem('wc_multi_store_city', city);
                                                wcMultiStoreLocation.assignValue(city);
                                            } else {
                                                $('#wc-multi-store-no-store-message').show();
                                                wcMultiStoreLocation.assignValue("no-value");
                                                localStorage.setItem('wc_multi_store_city', null);
                                            }
                                        } else {
                                            $('#wc-multi-store-no-store-message').show();
                                            wcMultiStoreLocation.assignValue("no-value");
                                            localStorage.setItem('wc_multi_store_city', null);
                                        }
                                    }
                                });
                            });
                        }
                    },

                    bindEvents: function() {
                        // City selection change
                        $('#wc-multi-store-city-select').on('change', function() {
                            var city = $(this).val();

                            if (city === "rest-of-city") {
                                navigator.geolocation.getCurrentPosition(function(position) {
                                    var lat = position.coords.latitude;
                                    var lng = position.coords.longitude;

                                    var geocoder = new google.maps.Geocoder();
                                    var latlng = new google.maps.LatLng(lat, lng);

                                    geocoder.geocode({
                                        'latLng': latlng
                                    }, function(results, status) {
                                        if (status === google.maps.GeocoderStatus.OK) {
                                            if (results.length) {
                                                let address = results.find(t => t.address_components[0].types.includes("postal_code"));
                                                if (typeof address === "undefined") {
                                                    address = results.find(t => t.address_components.map(d => d.types.includes('postal_code')))
                                                } else {
                                                    address = results[0];
                                                }
                                                wcMultiStoreLocation.setConfig(address);
                                                localStorage.setItem('wc_multi_store_city', city);
                                                location.reload();
                                            }
                                        }
                                    });
                                });
                            } else if (city) {
                                let geocoder = new google.maps.Geocoder();
                                let bounds = new google.maps.LatLngBounds();
                                geocoder.geocode({
                                    'address': city
                                }, function(results, status) {
                                    if (status === google.maps.GeocoderStatus.OK) {
                                        wcMultiStoreLocation.setConfig(results[0]);
                                        localStorage.setItem('wc_multi_store_city', city);
                                        location.reload(); // Reload to update product list
                                    }
                                });
                            }
                        });

                        // Select nearby location
                        $('#wc-multi-store-select-nearby').on('click', function() {
                            $('#wc-multi-store-map').show();
                            wcMultiStoreLocation.initMap();
                        });
                    },

                    initMap: function() {
                        var map = new google.maps.Map(document.getElementById('wc-multi-store-map'), {
                            center: {
                                lat: 0,
                                lng: 0
                            },
                            zoom: 2
                        });

                        // Get cities from select
                        var cities = [];
                        $('#wc-multi-store-city-select option').each(function() {
                            var city = $(this).val();
                            if (city) {
                                cities.push(city);
                            }
                        });

                        // Add markers for each city
                        var geocoder = new google.maps.Geocoder();
                        var bounds = new google.maps.LatLngBounds();

                        cities.forEach(function(city) {
                            geocoder.geocode({
                                'address': city
                            }, function(results, status) {
                                if (status === google.maps.GeocoderStatus.OK) {
                                    var marker = new google.maps.Marker({
                                        map: map,
                                        position: results[0].geometry.location,
                                        title: city
                                    });

                                    bounds.extend(results[0].geometry.location);
                                    map.fitBounds(bounds);

                                    // Add click event to marker
                                    google.maps.event.addListener(marker, 'click', function() {
                                        localStorage.setItem('wc_multi_store_city', city);
                                        wcMultiStoreLocation.assignValue(city);
                                        location.reload(); // Reload to update product list
                                    });
                                }
                            });
                        });
                    },

                    setConfig: function({
                        address_components: address,
                        geometry: {
                            location: {
                                lat,
                                lng
                            }
                        }
                    }) {
                        const cityComponent = address.find(d => d.types.indexOf('locality') > -1);
                        const stateComponent = address.find(d => d.types.indexOf('administrative_area_level_1') > -1);
                        const countryComponent = address.find(d => d.types.indexOf('country') > -1);
                        const districtComponent = address.find(d => (
                            d.types.indexOf('administrative_area_level_2') > -1 ||
                            d.types.indexOf('administrative_area_level_3') > -1
                        ));
                        const pincode = address.find(d => d.types.indexOf('postal_code') > -1);

                        localStorage.setItem('wc_multi_store', JSON.stringify({
                            city: cityComponent ? cityComponent.long_name : null,
                            state: stateComponent ? stateComponent.short_name : null,
                            country: countryComponent ? countryComponent.short_name : null,
                            district: districtComponent ? districtComponent.long_name : null,
                            pincode: pincode ? pincode.long_name : null,
                            latitude: lat(),
                            longitude: lng()
                        }));
                    },

                    handleRestrict: function() {
                        const city = jQuery('.wc-multi-store-city-select').val().toLowerCase();
                        const cities = restrictedCities.map(c => c.toLowerCase());
                        const isRestricted = cities.includes(city);

                        if (isRestricted) {
                            jQuery('#wc-multi-store-restrict-city-message').show();
                            if (jQuery('.wc-multi-store-city-select').data('default') !== "null") {
                                localStorage.setItem('wc_multi_store_city', jQuery('.wc-multi-store-city-select').data('default'));
                            }
                        } else {
                            jQuery('#wc-multi-store-restrict-city-message').hide();
                        }
                    },

                    assignValue: (city) => {
                        const selectEle = document.querySelector('select.wc-multi-store-city-select');
                        const options = Array.from(selectEle.options);
                        const index = options.findIndex(option => option.value.toLowerCase() === city.toLowerCase());

                        if (index > -1) {
                            options[index].setAttribute('selected', true);
                        } else if (jQuery('.wc-multi-store-city-select').data('default') !== "null") {
                            localStorage.setItem('wc_multi_store_city', jQuery('.wc-multi-store-city-select').data('default'));
                            const index = options.findIndex(option => option.value.toLowerCase() === jQuery('.wc-multi-store-city-select').data('default').toLowerCase());
                            options[index].setAttribute('selected', true);
                        } else {
                            options[0].setAttribute('selected', true);
                        }
                        wcMultiStoreLocation.handleRestrict();
                    },
                };

                // Initialize
                wcMultiStoreLocation.init();
            });
        </script>
<?php
    }

    function enqueue_assets()
    {
        $path = plugin_dir_url(__FILE__);
        wp_enqueue_script('script', "{$path}../../assets/js/script.js", ["jquery"], '1.0', true);
    }

    
}