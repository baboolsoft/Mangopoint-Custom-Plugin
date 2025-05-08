<div class="wrap">
    <h1><?php _e('Manage Stores', 'wc-multi-store'); ?></h1>

    <div class="wc-multi-store-admin-notice notice notice-success" style="display: none;"></div>

    <div class="wc-multi-store-admin-form">
        <h2><?php _e('Add/Edit Store', 'wc-multi-store'); ?></h2>

        <form id="wc-multi-store-store-form">
            <input type="hidden" id="store-id" value="0">

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="store-name"><?php _e('Store Name', 'wc-multi-store'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="store-name" class="regular-text" required>
                        <p class="description"><?php _e('Enter a unique name for the store', 'wc-multi-store'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="store-city"><?php _e('City', 'wc-multi-store'); ?></label>
                    </th>
                    <td>
                        <!-- <select id="store-city" class="regular-text" required>
                            <option value=""><?php _e('Select a city', 'wc-multi-store'); ?></option>
                        </select> -->
                        <input type="text" id="store-city" class="regular-text" placeholder="<?php _e('Select a city', 'wc-multi-store'); ?>">
                        <p class="description"><?php _e('Search and select a city from the dropdown', 'wc-multi-store'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="store-lat"><?php _e('Latitude', 'wc-multi-store'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="store-lat" class="regular-text" required>
                        <p class="description"><?php _e('Hub\'s Latitude', 'wc-multi-store'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="store-lng"><?php _e('Longitude', 'wc-multi-store'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="store-lng" class="regular-text" required>
                        <p class="description"><?php _e('Hub\'s Longitude', 'wc-multi-store'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="store-default"><?php _e('Default Store', 'wc-multi-store'); ?></label>
                    </th>
                    <td>
                        <div class="check-box-wrapper">
                            <input type="checkbox" id="store-default">
                            <label for="store-default" class="description"><?php _e('Set as default store', 'wc-multi-store'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="isRestrict"><?php _e('Restrict city', 'wc-multi-store'); ?></label>
                    </th>
                    <td>
                        <div class="check-box-wrapper">
                            <input type="checkbox" id="isRestrict">
                            <label for="isRestrict" class="description"><?php _e('Restrict this city for other locations', 'wc-multi-store'); ?></label>
                        </div>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Save Store', 'wc-multi-store'); ?></button>
                <button type="button" id="wc-multi-store-cancel-edit" class="button" style="display: none;"><?php _e('Cancel', 'wc-multi-store'); ?></button>
            </p>
        </form>
    </div>

    <hr>

    <h2><?php _e('Stores', 'wc-multi-store'); ?></h2>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('ID', 'wc-multi-store'); ?></th>
                <th><?php _e('Name', 'wc-multi-store'); ?></th>
                <th><?php _e('City', 'wc-multi-store'); ?></th>
                <th><?php _e('Default', 'wc-multi-store'); ?></th>
                <th><?php _e('Restricted City', 'wc-multi-store'); ?></th>
                <th><?php _e('Actions', 'wc-multi-store'); ?></th>
            </tr>
        </thead>
        <tbody id="wc-multi-store-stores-list">
            <?php if (empty($stores)) : ?>
                <tr>
                    <td colspan="5"><?php _e('No stores found', 'wc-multi-store'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($stores as $store) : ?>
                    <tr data-id="<?php echo esc_attr($store->id); ?>">
                        <td><?php echo esc_html($store->id); ?></td>
                        <td><?php echo esc_html($store->name); ?></td>
                        <td><?php echo esc_html($store->city); ?></td>
                        <td><?php echo $store->is_default ? '✓' : ''; ?></td>
                        <td><?php echo $store->isRestrict ? '✓' : ''; ?></td>
                        <td>
                            <button type="button" class="button wc-multi-store-edit-store" data-id="<?php echo esc_attr($store->id); ?>" data-name="<?php echo esc_attr($store->name); ?>" data-city="<?php echo esc_attr($store->city); ?>" data-is-restrict="<?= esc_attr($store->isRestrict) ?>" data-default="<?php echo esc_attr($store->is_default); ?>"><?php _e('Edit', 'wc-multi-store'); ?></button>
                            <button type="button" class="button wc-multi-store-delete-store" data-id="<?php echo esc_attr($store->id); ?>"><?php _e('Delete', 'wc-multi-store'); ?></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    jQuery(document).ready(function($) {
        // Store form submission
        $('#wc-multi-store-store-form').on('submit', function(e) {
            e.preventDefault();

            var storeId = $('#store-id').val();
            var lat = $('#store-lat').val();
            var lng = $('#store-lng').val();
            var storeName = $('#store-name').val();
            var storeCity = $('#store-city').val();
            var isDefault = $('#store-default').is(':checked');
            var isRestrict = $('#isRestrict').is(':checked');

            $.ajax({
                url: wcMultiStoreAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_multi_store_save_store',
                    nonce: wcMultiStoreAdmin.nonce,
                    store_id: storeId,
                    store_name: storeName,
                    city: storeCity,
                    is_default: isDefault,
                    isRestrict,
                    lat,
                    lng
                },
                success: function(response) {
                    if (response.success) {
                        $('.wc-multi-store-admin-notice').html(response.data.message).show();
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        });

        // Edit store
        $('.wc-multi-store-edit-store').on('click', function() {
            var storeId = $(this).data('id');
            var lat = $(this).data('lat');
            var lng = $(this).data('lng');
            var storeName = $(this).data('name');
            var storeCity = $(this).data('city');
            var isDefault = $(this).data('default') == 1;
            var isRestrict = $(this).data('isRestrict') == 1;

            $('#store-id').val(storeId);
            $('#store-name').val(storeName);
            $('#store-city').val(storeCity);
            $('#store-default').prop('checked', isDefault);
            $('#isRestrict').prop('checked', isRestrict);
            $('#store-lat').prop('checked', isRestrict);
            $('#store-lng').prop('checked', isRestrict);

            $('#wc-multi-store-cancel-edit').show();
        });

        // Cancel edit
        $('#wc-multi-store-cancel-edit').on('click', function() {
            $('#store-id').val(0);
            $('#store-name').val('');
            $('#store-city').val('');
            $('#store-default').prop('checked', false);
            $('#isRestrict').prop('checked', false);

            $(this).hide();
        });

        // Delete store
        $('.wc-multi-store-delete-store').on('click', function() {
            if (!confirm('<?php _e('Are you sure you want to delete this store?', 'wc-multi-store'); ?>')) {
                return;
            }

            var storeId = $(this).data('id');

            $.ajax({
                url: wcMultiStoreAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_multi_store_delete_store',
                    nonce: wcMultiStoreAdmin.nonce,
                    store_id: storeId
                },
                success: function(response) {
                    if (response.success) {
                        $('.wc-multi-store-admin-notice').html(response.data.message).show();
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        });
    });

    // Initialize Google Places Autocomplete
    var autocomplete;

    function initAutocomplete() {
        const input = document.getElementById('store-city');
        const autocomplete = new google.maps.places.Autocomplete(input, {
            // types: ['(cities)'],
            componentRestrictions: {
                country: 'in'
            } // Restrict to India if needed
        });

        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            let city = '';
            let lat = '-';
            let lng = '-';

            // Go through address_components and find the one with type 'locality'
            if (place.address_components) {
                for (const component of place.address_components) {
                    if (component.types.includes('locality')) {
                        lat = place.geometry.location.lat();
                        lng = place.geometry.location.lng();
                        city = component.long_name;
                        break;
                    }
                }

                // Fallback: try administrative_area_level_2 (for towns or smaller cities)
                if (!city) {
                    for (const component of place.address_components) {
                        if (component.types.includes('administrative_area_level_2')) {
                            lat = place.geometry.location.lat();
                            lng = place.geometry.location.lng();
                            city = component.long_name;
                            break;
                        }
                    }
                }
            }

            // Set only the city name in the input field
            if (city) {
                input.value = city;
                jQuery('#store-lat').val(lat);
                jQuery('#store-lng').val(lng);
            }
        });
    }


    // Load Google Maps API with Places library
    if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
        var apiKey = '<?php echo esc_js(get_option('wc_multi_store_google_maps_api_key', '')); ?>';
        if (apiKey) {
            var script = document.createElement('script');
            script.src = 'https://maps.googleapis.com/maps/api/js?key=' + apiKey + '&libraries=places&callback=initAutocomplete';
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        } else {
            alert('<?php _e('Google Maps API key is not configured. Please set it in the Google Map API Config page.', 'wc-multi-store'); ?>');
        }
    } else {
        initAutocomplete();
    }
</script>