<div class="wrap">
    <h1><?php _e('Google Map API Config', 'wc-multi-store'); ?></h1>
    
    <div class="wc-multi-store-admin-notice notice notice-success" style="display: none;"></div>
    
    <form id="wc-multi-store-api-form">
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="api-key"><?php _e('Google Maps API Key', 'wc-multi-store'); ?></label>
                </th>
                <td>
                    <input type="text" id="api-key" class="regular-text" value="<?php echo esc_attr($api_key); ?>">
                    <p class="description"><?php _e('Enter your Google Maps API key', 'wc-multi-store'); ?></p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" class="button button-primary"><?php _e('Save API Key', 'wc-multi-store'); ?></button>
        </p>
    </form>
    
    <div class="wc-multi-store-api-instructions">
        <h2><?php _e('Instructions', 'wc-multi-store'); ?></h2>
        
        <ol>
            <li><?php _e('Go to the <a href="https://console.cloud.google.com/google/maps-apis/overview" target="_blank">Google Cloud Console</a>', 'wc-multi-store'); ?></li>
            <li><?php _e('Create a new project or select an existing one', 'wc-multi-store'); ?></li>
            <li><?php _e('Enable the following APIs:', 'wc-multi-store'); ?>
                <ul>
                    <li><?php _e('Maps JavaScript API', 'wc-multi-store'); ?></li>
                    <li><?php _e('Geocoding API', 'wc-multi-store'); ?></li>
                    <li><?php _e('Places API', 'wc-multi-store'); ?></li>
                </ul>
            </li>
            <li><?php _e('Create an API key and restrict it to your domain for security', 'wc-multi-store'); ?></li>
            <li><?php _e('Copy the API key and paste it in the field above', 'wc-multi-store'); ?></li>
        </ol>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // API key form submission
        $('#wc-multi-store-api-form').on('submit', function(e) {
            e.preventDefault();
            
            var apiKey = $('#api-key').val();
            
            $.ajax({
                url: wcMultiStoreAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_multi_store_save_api_key',
                    nonce: wcMultiStoreAdmin.nonce,
                    api_key: apiKey
                },
                success: function(response) {
                    if (response.success) {
                        $('.wc-multi-store-admin-notice').html(response.data.message).show();
                        setTimeout(function() {
                            $('.wc-multi-store-admin-notice').hide();
                        }, 3000);
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        });
    });
</script>
