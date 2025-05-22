<div class="msm-wrap shop-manager">
    <div class="title-bar">
        <h1><?= $displayInfo && isset($storeInfo->name) ? $storeInfo->name : "Shop List" ?> | Multi Store Manager</h1>
        <?php
            if ($displayInfo && isset($storeInfo->id)) {
                echo '<div>
                    <a class="button button-secondary" href="' . (admin_url()) . 'admin.php?page=multi-store-manager/product-inventory&store_info=true&store_id=' . $storeInfo->id . '">
                        Products
                    </a>
                    <a class="button button-secondary" href="' . (admin_url()) . 'admin.php?page=multi-store-manager/shipping-fare&store_info=true&store_id=' . $storeInfo->id . '">
                        Shipping Fare
                    </a>
                    <a class="button button-secondary" href="' . (admin_url()) . 'admin.php?page=multi-store-manager/orders-list&store_info=true&store_id=' . $storeInfo->id . '">
                        Orders
                    </a>
                </div>';
            } else {
                echo '<button class="add-btn button button-primary">Add Store</button>';
            }
            
        ?>
    </div>
    <form data-title="store" class="store-form form<?= $displayInfo ? " show" : "" ?>">
        <h3 class="title">Add New Store | Form</h3>
        <input type="hidden" name="manage" value="store">
        <input type="hidden" name="lat" value="<?= $displayInfo && isset($storeInfo->lat) ? $storeInfo->lat : "" ?>">
        <input type="hidden" name="lng" value="<?= $displayInfo && isset($storeInfo->lng) ? $storeInfo->lng : "" ?>">
        <?php
        if ($displayInfo && isset($storeInfo->id)) {
            echo '<input type="hidden" name="id" value="' . $storeInfo->id . '">';
        }
        ?>
        <table class="field-wrapper">
            <tbody>
                <tr class="field-group">
                    <td class="label">
                        <label for="name">Store Name</label>
                    </td>
                    <td>
                        <input type="text" name="name" id="name" placeholder="Enter Store Name" required
                            value="<?= $displayInfo && isset($storeInfo->name) ? $storeInfo->name : "" ?>">
                        <small class="hint">Enter a unique name for store</small>
                    </td>
                </tr>
                <tr class="field-group">
                    <td class="label">
                        <label for="city">City Name</label>
                    </td>
                    <td>
                        <input type="text" name="city" id="city" placeholder="Search for city" required
                            value="<?= $displayInfo && isset($storeInfo->city) ? $storeInfo->city : "" ?>">
                        <small class="hint">Search and select a city from the dropdown</small>
                    </td>
                </tr>
                <tr class="field-group">
                    <td class="label">
                        <label for="mail">E-Mail Id</label>
                    </td>
                    <td>
                        <input type="text" name="mail" id="mail" required
                            value="<?= $displayInfo && isset($storeInfo->mail) ? $storeInfo->mail : "" ?>">
                    </td>
                </tr>
                <tr class="field-group">
                    <td class="label">
                        <label for="radius">Radius(km)</label>
                    </td>
                    <td>
                        <input type="number" name="radius" id="radius" placeholder="Radius in KM" required
                            value="<?= $displayInfo && isset($storeInfo->radius) ? $storeInfo->radius : "" ?>">
                    </td>
                </tr>
                <tr class="field-group">
                    <td class="label">
                        <label for="status">Store status</label>
                    </td>
                    <td>
                        <select name="status" id="status" required>
                            <option value="">--Select Status Option--</option>
                            <option <?= $displayInfo && isset($storeInfo->status) && $storeInfo->status == "1" ? "selected " : "" ?>value="1">Mark as Active</option>
                            <option <?= $displayInfo && isset($storeInfo->status) && $storeInfo->status == "0" ? "selected " : "" ?>value="0">Mark as In-active</option>
                        </select>
                        <small class="hint">Choose <b>yes</b> to make this the default store</small>
                    </td>
                </tr>
                <tr class="field-group">
                    <td class="label">
                        <label for="default">Whether Default Store</label>
                    </td>
                    <td>
                        <select name="default" id="default" required>
                            <option value="">--Select Default Option--</option>
                            <option <?= $displayInfo && isset($storeInfo->is_default) && $storeInfo->is_default == "1" ? "selected " : "" ?>value="1">Yes</option>
                            <option <?= $displayInfo && isset($storeInfo->is_default) && $storeInfo->is_default == "0" ? "selected " : "" ?>value="0">No</option>
                        </select>
                        <small class="hint">Choose <b>yes</b> to make this the default store</small>
                    </td>
                </tr>
                <!-- <tr class="field-group">
                    <td class="label">
                        <label for="restrict">Whether resticted Store</label>
                    </td>
                    <td>
                        <select name="restrict" id="restrict" required>
                            <option value="">--Choose whether to apply restrictions--</option>
                            <option <?= $displayInfo && isset($storeInfo->is_restrict) && $storeInfo->is_restrict == "1" ? "selected " : "" ?>value="1">Yes</option>
                            <option <?= $displayInfo && isset($storeInfo->is_restrict) && $storeInfo->is_restrict == 0 ? "selected " : "" ?>value="0">No</option>
                        </select>
                        <small class="hint">Choose <b>yes</b> to restrict this city's access to other cities.</small>
                    </td>
                </tr> -->
            </tbody>
        </table>
        <button class="submit button button-primary">Save Store</button>
        <div class="error hidden"></div>
    </form>

    <?php if ($isStorePage == false) { ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th width="50">#</th>
                    <th width="20"></th>
                    <th width="300">Store Name</th>
                    <th width="150">City</th>
                    <th width="280">Mail</th>
                    <th width="120">Radius(km)</th>
                    <th class="action"></th>
                </tr>
            </thead>
            <tbody class="wc-multi-store-stores-list">
                <?php
                if (count($list) == 0) {
                    echo '<tr class="no-data">
                        <td colspan="7">No stores found.</td>
                    </tr>';
                } else {
                    foreach ($list as $key => $value) {

                        $defaultHtml = '';
                        $restrictHtml = '';

                        if ($value->is_default == 1) {
                            $defaultHtml = '<span class="chip default-store">Default Store</span>';
                        }

                        if ($value->is_restrict == 1) {
                            $restrictHtml = '<span class="chip restrict-store">Restricted Store</span>';
                        }

                        echo '<tr class="store-item" data-id="' . $value->id . '" data-index="' . $key . '">
                            <td>' . ($key + 1) . '</td>
                            <td>
                                <button class="status-toggler' . ($value->status == '1' ? ' active' : '') . '" title="Mark as active">
                                    <i class="dashicons dashicons-yes"></i>
                                </button>
                            </td>
                            <td> ' . $value->name . $defaultHtml . $restrictHtml . ' </td>
                            <td>' . $value->city . '</td>
                            <td>' . $value->mail . '</td>
                            <td>' . $value->radius . '</td>
                            <td class="action">
                            <a class="button button-secondary" href="' . (admin_url()) . 'admin.php?page=multi-store-manager/product-inventory&store_id=' . $value->id . '">
                                Products
                            </a>
                            <a class="button button-secondary" href="' . (admin_url()) . 'admin.php?page=multi-store-manager/shipping-fare&store_id=' . $value->id . '">
                                Shipping Fare
                            </a>
                            <a class="button button-secondary" href="' . (admin_url()) . 'admin.php?page=multi-store-manager/orders-list&store_id=' . $value->id . '">
                                Orders
                            </a>
                            <button class="edit-store button button-secondary">Edit</button>
                            <button class="delete-store button button-secondary">Delete</button>
                            </td>
                        </tr>';
                    }
                }
                ?>
            </tbody>
        </table>
    <?php } ?>
</div>

<script>
    function initAutocomplete() {
        const input = document.querySelector('input[name="city"]');
        const autocomplete = new google.maps.places.Autocomplete(input, {
            componentRestrictions: {
                country: 'in'
            }
        });

        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            let city = '';
            let lat = '-';
            let lng = '-';

            // Go through address_components and find the one with type 'sublocality'
            if (place.address_components) {
                console.log(place);
                for (const component of place.address_components) {
                    if (component.types.includes('sublocality')) {
                        lat = place.geometry.location.lat();
                        lng = place.geometry.location.lng();
                        city = component.long_name;
                        break;
                    }
                }

                // Fallback: try locality (cities)
                if (!city) {
                    for (const component of place.address_components) {
                        if (component.types.includes('locality')) {
                            lat = place.geometry.location.lat();
                            lng = place.geometry.location.lng();
                            city = component.long_name;
                            break;
                        }
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
                // Fallback: try with city name
                if (!city) {
                    for (const component of place.address_components) {
                        if (input.value.toLocaleLowerCase().includes(component.long_name.toLocaleLowerCase())) {
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

                input.value = place.name.split(',')[0]; // Get the first part of the city name
                jQuery('input[name="lat"]').val(lat);
                jQuery('input[name="lng"]').val(lng);
            } else {
                console.error('City not found in address components:', input.value.toLocaleLowerCase(), place.address_components);
            }
        });
    };
    jQuery(document).ready(function() {
        jQuery('.store-item').each(function() {
            const id = jQuery(this).data('id');
            jQuery(this).find('.edit-store').on('click', () => {

                const data = <?= json_encode($list) ?>.find(d => parseInt(d.id) == id);

                if (data) {

                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'id';
                    hiddenInput.value = id;
                    jQuery('.store-form').append(hiddenInput);

                    jQuery('.store-form input[name="name"]').val(data.name);
                    jQuery('.store-form input[name="city"]').val(data.city);
                    jQuery('.store-form input[name="lat"]').val(data.lat);
                    jQuery('.store-form input[name="lng"]').val(data.lng);
                    jQuery('.store-form input[name="radius"]').val(data.radius);
                    jQuery('.store-form input[name="mail"]').val(data.mail);
                    jQuery('.store-form select[name="default"]').val(data.is_default);
                    jQuery('.store-form select[name="status"]').val(data.status);
                    jQuery('.store-form select[name="restrict"]').val(data.is_restrict);


                    jQuery('.store-form').find('.title').html(`Update ${data.name} | Store Form`);
                    if (jQuery('.store-form').css('display') === "none") {
                        jQuery('.store-form').slideToggle();
                    }
                }
            });
            jQuery(this).find('.delete-store').on('click', () => {
                const delEle = jQuery(this).find('.delete-store');
                if (confirm("Are you sure you want to delete this product?")) {
                    jQuery.ajax({
                        url: `${window.location.origin}/wp-json/multi-store-manager/v1/api/delete/`,
                        type: 'POST',
                        data: {
                            id,
                            delete: true,
                            table: 'store',
                        },
                        beforeSend: function() {
                            jQuery(delEle).attr('disabled', true).text('Deleting...');
                        },
                        success: ({
                            status = false,
                            message
                        }) => {
                            if (status) {
                                setTimeout(() => {
                                    location.reload(true);
                                }, 1000);
                            } else {
                                jQuery(delEle).attr('disabled', false).text('Delete');
                            }
                        },
                        error: function() {
                            alert('Error deleting store.');
                        }
                    });
                }
            });
        });
    });
</script>

<script async="true" loading="async" defer="true" src="https://maps.googleapis.com/maps/api/js?key=<?= $api_key ?>&libraries=places&callback=initAutocomplete"></script>
