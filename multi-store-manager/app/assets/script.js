jQuery('document').ready(function () {
    const { name = null } = init.fetchCoords()
    jQuery("ul.hfe-nav-menu").prepend(`<li class="location-pin desktop-menu-item menu-item menu-item-type-post_type menu-item-object-page parent hfe-creative-menu">
        <button class="location-pin-wrapper">
            <div class="location-pin-button" title="${name}">
                <i class="fa fa-map-marked-alt"></i>
            </div>
            <h6 class="label">${name ? name : "Search Location"}</h6>
        </button>
    </li>`);

    jQuery(".hfe-nav-menu.hfe-layout-horizontal").prepend(`<li class="menu-wrap location-pin menu-item menu-item-type-post_type menu-item-object-page parent hfe-creative-menu">
        <button class="location-pin-wrapper">
            <div class="location-pin-button" title="${name}">
                <i class="fa fa-map-marked-alt"></i>
            </div>
            <h6 class="label">${name ? name : "Search Location"}</h6>
        </button>
    </li>`);

    if (jQuery('form.checkout').length) {
        const { name = "", country = "", district = "", state = "", pincode = "" } = JSON.parse(localStorage.getItem('coords')) || {};
        jQuery('form.checkout [name="billing_city"]').val(`${name}${name.toLowerCase() !== district.toLowerCase() ? `, ${district}` : ""}`);
        jQuery('form.checkout [name="billing_country"]').val(country);
        jQuery('form.checkout [name="billing_state"]').val(state);
        jQuery('form.checkout [name="billing_postcode"]').val(pincode);

        jQuery('form.checkout [name="shipping_city"]').val(`${name}${name.toLowerCase() !== district.toLowerCase() ? `, ${district}` : ""}`);
        jQuery('form.checkout [name="shipping_country"]').val(country);
        jQuery('form.checkout [name="shipping_state"]').val(state);
        jQuery('form.checkout [name="shipping_postcode"]').val(pincode);
    }

    // if (jQuery('.woocommerce-table.order_details').length) {
    //     jQuery('.woocommerce-table.order_details tfoot tr').each(function () {
    //         if (jQuery(this).find('td').html() === "Free shipping") {
    //             jQuery(this).remove();
    //         }
    //     });
    // }

    if (localStorage.getItem("enableShipping") ?? false) {
        const info = JSON.parse(localStorage.getItem("shippingInfo")) || {};
        const billingInfo = JSON.parse(localStorage.getItem("billingInfo")) || {};

        jQuery('form.checkout [name="shipping_first_name"]').val(info.fname);
        jQuery('form.checkout [name="shipping_last_name"]').val(info.lname);
        jQuery('form.checkout [name="shipping_country"]').val(info.country);
        jQuery('form.checkout [name="shipping_address_1"]').val(info.address1);
        jQuery('form.checkout [name="shipping_address_2"]').val(info.address2);
        jQuery('form.checkout [name="shipping_city"]').val(info.city);
        jQuery('form.checkout [name="shipping_state"]').val(info.state);
        jQuery('form.checkout [name="shipping_postcode"]').val(info.pincode);

        jQuery('form.checkout [name="billing_first_name"]').val(billingInfo.fname);
        jQuery('form.checkout [name="billing_last_name"]').val(billingInfo.lname);
        jQuery('form.checkout [name="billing_country"]').val(billingInfo.country);
        jQuery('form.checkout [name="billing_address_1"]').val(billingInfo.address1);
        jQuery('form.checkout [name="billing_address_2"]').val(billingInfo.address2);
        jQuery('form.checkout [name="billing_city"]').val(billingInfo.city);
        jQuery('form.checkout [name="billing_state"]').val(billingInfo.state);
        jQuery('form.checkout [name="billing_postcode"]').val(billingInfo.pincode);
        jQuery('form.checkout [name="billing_phone"]').val(billingInfo.phone);
        jQuery('form.checkout [name="billing_email"]').val(billingInfo.email);

        setTimeout(() => {
            if ((localStorage.getItem("showShippingAddress") ?? "false") === "true") {
                jQuery('[name="ship_to_different_address"]').click();
            }
        }, 100);
    }

    setTimeout(() => {
        if (jQuery('.lbpl').length > 0) {
            init.retiveProducts();
            jQuery('.lbpl').each(function () {
                jQuery(this).find('.lbpl-filters input').click((e) => {
                    jQuery(this).data('options', e.target.value);
                    setTimeout(() => {
                        filterProducts(productDatas, this);
                    }, 100);
                });
            });
        }
        jQuery('.location-pin-wrapper').on('click', addSearchBox);
        handleAddress();
    }, 100);
});

var productDatas = null;
var map, service, marker;

const init = {
    fetchCoords: (callBack = null, getLocation = true) => {
        if (localStorage.getItem('coords')) {
            const { name } = JSON.parse(localStorage.getItem('coords'));
            jQuery('.location-pin-button').attr('title', name);
            if (typeof callBack === "function") {
                callBack();
                return false
            }
            return JSON.parse(localStorage.getItem('coords'));
        } else if (getLocation) {
            init.retriveLocation(callBack);
        }
        return false;
    },
    retriveLocation: (callBack) => {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition((({ coords: { latitude, longitude } }) => {
                sessionPlace({ latitude, longitude });
                if (typeof callBack === "function") {
                    callBack();
                }
            }), () => {
                sessionPlace({ latitude: 0, longitude: 0 });
                if (typeof callBack === "function") {
                    callBack();
                }
            });
        }
    },
    retiveProducts: () => {
        init.fetchCoords(restCall);
    },
    deniedCase: (error) => {

        let err;

        switch (error.code) {
            case error.PERMISSION_DENIED:
                err = "Please enable location permissions to list the product.";
                break;
            case error.POSITION_UNAVAILABLE:
                err = "Sorry, we are unable to retrieve your location.";
                break;
            case error.TIMEOUT:
                err = "Sorry, the request to fetch your location timed out.";
                break;
            default:
                err = "Please try again. Something went wrong.";
                break;
        }

        jQuery(`.lbpl .result.woocommerce`).html(`<div class="no-product-log">
            <i class="fa fa-exclamation"></i>
            <h5 class="title">${err}</h5>
        </div>`);

        setTimeout(() => {
            jQuery('.no-product-log .try-again button').click(() => {
                location.reload(true);
            });
        }, 100);
    }
}

const addSearchBox = (e) => {

    const { top } = jQuery('.location-pin.desktop-menu-item .location-pin-button').offset();

    jQuery('body').append(`<div class="location-popup-wrapper">
        <div class="location-popup" style="top: ${top + 40}px;">
            <div class="header">
                <h5 class="head-title">Select a location for delivery</h5>
                <p class="head-caption">Choose your address location to see product availability and delivery options</p>
            </div>
            <div class="content">
                <div class="search-box">
                    <i class="fa fa-search icon"></i>
                    <input type="text" placeholder="Search for area or street name" name="lc-search-box"/>
                    <button class="search-btn">
                        <i class="fa fa-angle-right search-btn-icon"></i>
                    </button>
                </div>
                <ul class="search-result"></ul>
            </div>
        </div>
    </div>`);

    setTimeout(() => {
        jQuery('.location-popup-wrapper').addClass('show');
        addClickEvent();
    }, 200);
}

const removeSearchBox = () => {
    removeClickEvent();
    jQuery('.location-popup-wrapper').removeClass('show');
    setTimeout(() => {
        jQuery('.location-popup-wrapper').remove();
    }, 800);
}

const addClickEvent = () => {
    document.body.addEventListener("click", handleClose);
    setTimeout(() => {
        // jQuery('.location-popup-wrapper .search-box input').focus();
        // jQuery('.location-popup-wrapper .search-box input').keyup(handleSearch);
        // jQuery('.location-popup-wrapper .search-box .search-btn').click(retrivePlaces);
        initMapAction().handleKeyup();
    }, 100);
}

const removeClickEvent = () => {
    document.body.removeEventListener("click", handleClose);
}

const handleClose = (e) => {
    if (
        (e.target.className !== ("location-popup")) &&
        (!e.target.className.includes('search-btn-icon')) &&
        (!e.target.className.includes('spinner-border')) &&
        (jQuery(`.location-popup-wrapper .location-popup`).length > 0) &&
        (jQuery(`.location-popup-wrapper .location-popup`).find(e.target).length === 0)
    ) {
        removeSearchBox();
    }
}

const handleSearch = (e) => {
    if (e.target.value.length > 2) {
        // jQuery('.search-box .search-btn').addClass('show');
        retrivePlaces();
    }
    // else if (e.target.value.length === 0) {
    //     jQuery('.search-box .search-btn').removeClass('show');
    // }
}

const restCall = (callBack) => {
    const coords = init.fetchCoords(null, false);
    if (coords) {
        jQuery.ajax({
            url: `${window.location.origin}/wp-json/multi-store-manager/v1/app/api/fetch-products/`,
            type: "POST",
            data: {
                ...coords
            },
            beforeSend: () => {
                const skeletonCard = `<div class="skeleton-card-item">
                    <div class="skeleton-card">
                        <div class="skeleton-image"></div>
                        <div class="skeleton-content">
                            <div class="skeleton-title"></div>
                            <div class="skeleton-price"></div>
                            <div class="skeleton-btn"></div>
                        </div>
                    </div>
                </div>`;

                jQuery(`form.woocommerce-checkout button[name="woocommerce_checkout_place_order"]`).html(`<i class="spinner-border"></i> Validating...`);

                jQuery(`.lbpl`).each(function () {
                    const { limit = -1 } = jQuery(this).data();
                    jQuery(this).find('.result.woocommerce').html(`<div class="products">
                    <div class="skeleton-wrapper">
                        ${skeletonCard.repeat(limit > 0 ? limit : 12)}
                    </div>
                </div>`);
                });
            },
            success: ({ status, products }) => {
                if (status && products.length > 0) {

                    productDatas = products;

                    if (typeof callBack === "function") {
                        callBack(products);
                    }

                    jQuery(`.lbpl`).each(function () {
                        filterProducts(products, this);
                    });

                } else {
                    jQuery(`.lbpl .result.woocommerce`).html(`<div class="no-product-log">
                        <i class="fa fa-exclamation"></i>
                        <h5 class="title">No products were found matching your selection.</h5>
                    </div>`);
                }
            },
            error: () => {
                jQuery(`.lbpl .result.woocommerce`).html(`<div class="no-product-log">
                    <i class="fa fa-exclamation"></i>
                    <h5 class="title">No products were found matching your selection.</h5>
                </div>`);
            },
        })
    }
}

const filterProducts = (products = productDatas, ele) => {
    let html = `<div class="no-product-log">
        <i class="fa fa-exclamation"></i>
        <h5 class="title">No products were found matching your selection.</h5>
    </div>`;
    if (ele && jQuery(ele).length > 0) {
        const { limit = 12, category = [], sort = 'menu-order', options = 'same-day-delivery', offset = 0 } = jQuery(ele).data();

        if (category.length > 0) {
            products = products.filter(item => ((item.categories) && (typeof item.categories === "object") && (item.categories.filter(e => category.includes(e.id)).length > 0)));
        }

        if (sort === "best-selling") {
            products = products.filter(({ type, best_selling = 0, variants }) => {
                if (type === "simple" && best_selling) {
                    return true;
                } else if (type === "variable" && (variants.filter(d => d.best_selling === "1").length)) {
                    return true;
                }
                return false;
            });
        }

        // products = products.filter(item =>
        //     (item.type === "simple" && item.option === options) ||
        //     (item.type === "variable" && item.variants.filter(d => d.delivery_estimate === "1" && options === "same-day-delivery").length) ||
        //     (item.type === "variable" && item.variants.filter(d => d.delivery_estimate !== "1" && options === "scheduled-delivery").length)
        // );

        if (limit > 0) {
            products = products.slice(offset, (offset > 0 ? (offset * limit) : limit));
        }

        if (products.length > 0) {
            html = '';

            (products.sort((a, b) => b.stock - a.stock)).forEach(item => {

                let btntxt = `<a class="btn" href="${item.slug}">View Products</a>`;

                if ((item.stock > 0) && (item.type === "simple")) {
                    btntxt = `<a href="?add-to-cart=${item.id}" data-delivery-option="${item.option}" data-store-id="${item.storeId}" aria-describedby="woocommerce_loop_add_to_cart_link_describedby_${item.id}" data-quantity="1" class="btn add_to_cart_button ajax_add_to_cart" data-product_id="${item.id}" data-product_sku="" aria-label="Add to cart: “${item.name}”" rel="nofollow" data-success_message="“${item.name}” has been added to your cart">Add to cart</a>`;
                }

                html += `<li class="product-item">
                    <div class="product-wrap">
                        <div class="product">
                            <div>
                               <a href="${item.slug}" class="product-img">
                                    <img src="${item.image}" alt="${item.name}" loading="eager" decoding="async" width="300" height="300" />
                                     ${item.stock < 1 ? `<p class="no-stock">Coming Soon</p>` : ""}
                                </a>
                                <a class="title" href="${item.slug}">
                                    <h2>${item.name}</h2>
                                </a>
                            </div>
                            <div class="info">
                                ${item.rating}
                                <p class="price">${item.price_html}</p>
                                ${btntxt}
                            </div>
                        </div>
                    </div>
                </li>`;
            });
            html = `<ul class="product-list">${html}</ul>`;
        }
    }
    jQuery(ele).find(`.result.woocommerce`).html(html);
}

const retrivePlaces = () => {
    jQuery.ajax({
        url: `${window.location.origin}/wp-json/api/search-place/`,
        type: "POST",
        data: {
            place: jQuery('input[name="lc-search-box"]').val()
        },
        beforeSend: () => {
            jQuery('.search-box .search-btn').html(`<i class="spinner-border"></i>`);
        },
        success: ({ data: { results = [] } = {} }) => {

            jQuery('.search-box .search-btn').html(`<i class="fa fa-angle-right search-btn-icon"></i>`);

            if (results.length) {
                let html = ``;

                results.forEach((item) => {
                    html += `<li class="result-item-wrapper">
                        <button class="result-item" data-lat="${item.geometry.location.lat}" data-lng="${item.geometry.location.lng}">
                            <h5 class="city">${item.name}</h5>
                            <p class="address">${item.formatted_address}</p>
                        </button>
                    </li>`;
                });

                jQuery(`.location-popup-wrapper .search-result`).html(html);

                setTimeout(() => {
                    jQuery(`.location-popup-wrapper .search-result .result-item`).each(function () {
                        jQuery(this).click(() => {
                            sessionPlace({
                                latitude: jQuery(this).data("lat"),
                                longitude: jQuery(this).data("lng"),
                                name: jQuery(this).find('.city').html(),
                                address: jQuery(this).find('.address').html(),
                            });
                            jQuery('.location-pin-button').attr('title', jQuery(this).find('.city').html());
                            jQuery('.location-pin-wrapper .label').html(jQuery(this).find('.city').html());
                            removeSearchBox();
                            setTimeout(() => {
                                restCall();
                            }, 100);
                        });
                    });
                }, 0);
            } else {
                jQuery(`.location-popup-wrapper .search-result`).html(`<div class="no-city-found">
                    <p>No places were found matching your search</p>
                </div>`);
            }
        },

    });
}

const validateCheckout = () => {
    if (typeof lbpl_validateCheckoutVal === "boolean" && lbpl_validateCheckoutVal) {
        jQuery('body').addClass('lbpl-checkout-page lbpl-loading lbpl-disable');

        const validate = (products) => {
            jQuery(`form.woocommerce-checkout button[name="woocommerce_checkout_place_order"]`).html(`Place Order`);
            const productIds = products.map(d => parseInt(d.id));
            let isExist = false;
            cartIds.forEach((id, index) => {
                if (!productIds.includes(id)) {
                    isExist = true;
                    jQuery(`.shop_table .cart_item:eq(${index})`).addClass('lbpl-product-exist');
                    jQuery(`.shop_table .cart_item:eq(${index})`).find('.product-name').append(`<span class="lbpl-product-exist-text">Product not available for delivery</span>`);
                }
            });
            jQuery('body').removeClass('lbpl-loading');
            if (isExist) {
                jQuery('body').addClass('lbpl-disable');
            } else {
                jQuery('body').removeClass('lbpl-disable');
            }
        }

        restCall(validate);
    }
}

function initMap(ele = false) {

    map = new google.maps.Map(document.createElement("div"), {
        center: { lat: 0, lng: 0 },
        zoom: 2,
    });
    service = new google.maps.places.PlacesService(map);

    if (jQuery('#map-container').length) {
        const { latitude, longitude, name } = JSON.parse(localStorage.getItem('coords')) || {};
        map = new google.maps.Map(document.getElementById("map-container"), {
            center: { lat: latitude, lng: longitude },
            zoom: 15,
        });
        const input = document.getElementById("search-place");
        const searchBox = new google.maps.places.SearchBox(input);
        map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

        map.addListener("bounds_changed", () => {
            searchBox.setBounds(map.getBounds());
        });

        marker = new google.maps.Marker({
            map,
            anchorPoint: new google.maps.Point(0, -29),
            position: { lat: latitude, lng: longitude },
            title: name
        });

        searchBox.addListener("places_changed", () => {
            const places = searchBox.getPlaces();

            if (places.length === 0) return;

            marker.setVisible(false);

            const place = places[0];
            if (!place.geometry) return;

            handleAdressChange(place);
        });

        jQuery('.current-location').click((e) => {
            e.preventDefault();
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition((({ coords: { latitude, longitude } }) => {
                    const geocoder = new google.maps.Geocoder();

                    geocoder.geocode({ location: { lat: latitude, lng: longitude } }, (results, status) => {
                        if (status === google.maps.GeocoderStatus.OK && results.length) {
                            let address = results.find(t => t.address_components[0].types.includes("postal_code"));
                            if (typeof address === "undefined") {
                                address = results.find(t => t.address_components.map(d => d.types.includes('postal_code')))
                            } else {
                                address = results[0];
                            }
                            handleAdressChange(address);
                        } else {
                            console.log("Geocoder failed due to: " + status);
                        }
                    });
                }));
            }
        }
        );
    }

}

function initMapAction(ele = false) {
    return {
        init: () => {

        },
        handleKeyup: (e) => {
            document.querySelector('.location-popup-wrapper .search-box input').addEventListener("input", function () {
                const query = this.value;
                if (query.length > 0) {
                    initMapAction().performSearch(query);
                } else {
                    initMapAction().handleEmpty();
                }
            });
        },
        handleEmpty: () => {
            jQuery(`.location-popup-wrapper .search-result`).html(`<div class="no-city-found">
                <p>No places were found matching your search</p>
            </div>`);
        },
        performSearch: (query) => {
            const request = {
                query: `${query}, India`,
                fields: ["name", "formatted_address", "place_id"]
            };

            service.textSearch(request, function (results, status) {
                if (status === google.maps.places.PlacesServiceStatus.OK) {
                    initMapAction().addPlace(results);
                } else {
                    initMapAction().handleEmpty();
                }
            });
        },
        addPlace: (places) => {
            let html = ``;
            places.forEach(({ name, formatted_address, geometry: { location } }) => {
                html += `<li class="result-item-wrapper">
                    <button class="result-item" data-lat="${location.lat()}" data-lng="${location.lng()}">
                        <h5 class="city">${name}</h5>
                        <p class="address">${formatted_address}</p>
                    </button>
                </li>`;
            });
            jQuery(`.location-popup-wrapper .search-result`).html(html);
            setTimeout(() => {
                jQuery(`.location-popup-wrapper .search-result .result-item`).each(function () {
                    jQuery(this).click(() => {
                        sessionPlace({
                            latitude: jQuery(this).data("lat"),
                            longitude: jQuery(this).data("lng"),
                            name: jQuery(this).find('.city').html(),
                            address: jQuery(this).find('.address').html(),
                        });
                        jQuery('.location-pin-button').attr('title', jQuery(this).find('.city').html());
                        jQuery('.location-pin-wrapper .label').html(jQuery(this).find('.city').html());
                        removeSearchBox();
                        setTimeout(() => {
                            if (jQuery('.custom-cart-form').length > 0) {
                                location.reload(true);
                            } else if (jQuery('.lbpl').length > 0) {
                                restCall();
                            }
                        }, 100);
                    });
                });
            }, 0);
        }
    }
}

const sessionPlace = ({
    latitude = 0,
    longitude = 0,
    name = null,
    address = null,
    pincode = null,
    state = "TN",
    country = "IN",
    district = null,
    callBack = null,
}) => {

    localStorage.setItem('coords', JSON.stringify({ latitude, longitude, name, address, pincode, state, country, district }));

    if ((!name || !address) && (latitude !== 0 && longitude !== 0)) {

        var geocoder = new google.maps.Geocoder();
        var latlng = new google.maps.LatLng(latitude, longitude);

        geocoder.geocode({
            'latLng': latlng
        }, function (results, status) {
            if (status === google.maps.GeocoderStatus.OK) {
                if (results.length) {
                    let address = results.find(t => t.address_components[0].types.includes("postal_code"));
                    if (typeof address === "undefined") {
                        address = results.find(t => t.address_components.map(d => d.types.includes('postal_code')))
                    } else {
                        address = results[0].address_components;
                    }

                    const cityComponent = address.find(d => d.types.indexOf('locality') > -1);
                    const stateComponent = address.find(d => d.types.indexOf('administrative_area_level_1') > -1);
                    const countryComponent = address.find(d => d.types.indexOf('country') > -1);
                    const districtComponent = address.find(d => (
                        d.types.indexOf('administrative_area_level_2') > -1 ||
                        d.types.indexOf('administrative_area_level_3') > -1
                    ));
                    const pincodeComponent = address.find(d => d.types.indexOf('postal_code') > -1);

                    name = cityComponent ? cityComponent.long_name : null;
                    pincode = pincodeComponent ? pincodeComponent.long_name : null;
                    state = stateComponent ? stateComponent.short_name : null;
                    country = countryComponent ? countryComponent.short_name : null;
                    district = districtComponent ? districtComponent.long_name : null;
                }

                localStorage.setItem('coords', JSON.stringify({ latitude, longitude, name, address, pincode, state, country, district }));
                jQuery('header .location-pin-wrapper .label').html(name ? name : "Search Location");
                jQuery.ajax({
                    url: `${window.location.origin}/wp-json/multi-store-manager/v1/app/api/store-place/`,
                    type: "POST",
                    data: { latitude, longitude, name, address },
                    beforeSend: () => {
                        jQuery("body").addClass('msm__loading');
                    },
                    success: (response) => {
                        jQuery("body").removeClass('msm__loading');
                        if (typeof callBack === "function") {
                            callBack(response);
                        }
                    }
                });
            }
        });
    } else {
        localStorage.setItem('coords', JSON.stringify({ latitude, longitude, name, address, pincode, state, country, district }));
        jQuery('header .location-pin-wrapper .label').html(name ? name : "Search Location");
        jQuery.ajax({
            url: `${window.location.origin}/wp-json/multi-store-manager/v1/app/api/store-place/`,
            type: "POST",
            data: { latitude, longitude, name, address },
            beforeSend: () => {
                jQuery("body").addClass('msm__loading');
            },
            success: (response) => {
                jQuery("body").removeClass('msm__loading');
                if (typeof callBack === "function") {
                    callBack(response);
                }
            }
        });
    }
}

const handleAddress = () => {
    ["billing_postcode", "shipping_postcode"].forEach(name => {
        // ["shipping_postcode"].forEach(name => {
        jQuery(`[name="${name}"]`).blur(function (e) {
            const fieldName = jQuery("[name='ship_to_different_address']").is(':checked') ? "shipping_postcode" : "billing_postcode";
            const { pincode: shippingPincode = "" } = JSON.parse(localStorage.getItem("shippingInfo")) || {};
            const { pincode: billingPincode = "" } = JSON.parse(localStorage.getItem("billingInfo")) || {};
            const value = fieldName === "billing_postcode" ? billingPincode : shippingPincode;
            if (e.target.value && name === fieldName && parseInt(e.target.value) !== parseInt(value)) {
                const { latitude, longitude } = JSON.parse(localStorage.getItem('coords')) || {};
                jQuery.ajax({
                    url: `${window.location.origin}/wp-json/multi-store-manager/v1/app/api/shipping-fare/`,
                    type: "POST",
                    data: {
                        pincode: e.target.value,
                        // pincode: jQuery("[name='ship_to_different_address']").is(':checked') ? jQuery('[name="shipping_postcode"]').val() : jQuery('[name="billing_postcode"]').val(),
                        latitude, longitude
                    },
                    beforeSend: () => {
                        jQuery('.shop_table tfoot .fee').each(function () {
                            if (jQuery(this).find('th').html() == "Shipping Fare") {
                                jQuery(this).find('td').html(`calculating fee...`);
                            }
                        });
                        jQuery('button[name="woocommerce_checkout_place_order"]').attr('disabled', true);
                        jQuery("body").addClass('msm__loading');
                    },
                    success: ({ status, fare_html, enable_order, locationInfo }) => {
                        if (status) {
                            jQuery('.shop_table tfoot .fee').each(function () {
                                if (jQuery(this).find('th').html() == "Shipping Fare") {
                                    jQuery(this).find('td').html(fare_html);
                                }
                            });
                            const { latitude, longitude } = init.fetchCoords();
                            // if (latitude !== locationInfo.latitude && longitude !== locationInfo.longitude) {
                            sessionPlace({
                                latitude: locationInfo.latitude,
                                longitude: locationInfo.longitude,
                                callBack: () => {
                                    location.reload(true);
                                }
                            });
                            localStorage.setItem("shippingInfo", JSON.stringify({
                                fname: jQuery('form.checkout [name="shipping_first_name"]').val(),
                                lname: jQuery('form.checkout [name="shipping_last_name"]').val(),
                                country: jQuery('form.checkout [name="shipping_country"]').val(),
                                address1: jQuery('form.checkout [name="shipping_address_1"]').val(),
                                address2: jQuery('form.checkout [name="shipping_address_2"]').val(),
                                city: jQuery('form.checkout [name="shipping_city"]').val(),
                                state: jQuery('form.checkout [name="shipping_state"]').val(),
                                pincode: jQuery('form.checkout [name="shipping_postcode"]').val()
                            }));
                            localStorage.setItem("billingInfo", JSON.stringify({
                                fname: jQuery('form.checkout [name="billing_first_name"]').val(),
                                lname: jQuery('form.checkout [name="billing_last_name"]').val(),
                                country: jQuery('form.checkout [name="billing_country"]').val(),
                                address1: jQuery('form.checkout [name="billing_address_1"]').val(),
                                address2: jQuery('form.checkout [name="billing_address_2"]').val(),
                                city: jQuery('form.checkout [name="billing_city"]').val(),
                                state: jQuery('form.checkout [name="billing_state"]').val(),
                                pincode: jQuery('form.checkout [name="billing_postcode"]').val(),
                                phone: jQuery('form.checkout [name="billing_phone"]').val(),
                                email: jQuery('form.checkout [name="billing_email"]').val()
                            }));
                            localStorage.setItem('enableShipping', true);
                            localStorage.setItem('showShippingAddress', jQuery("[name='ship_to_different_address']").is(':checked'));
                            // }
                            // jQuery('button[name="woocommerce_checkout_place_order"]').removeAttr('disabled', true);
                            // jQuery('.checkout').removeClass('disable_place_order');
                        }
                        if (!enable_order) {
                            // jQuery('button[name="woocommerce_checkout_place_order"]').attr('disabled', true);    
                            // jQuery('.checkout').addClass('disable_place_order');
                        }
                    }
                })
            }
        })
    });
}

const handleAdressChange = (place) => {
    const address = place.address_components || [];
    const cityComponent = address.find(d => d.types.indexOf('locality') > -1);
    const stateComponent = address.find(d => d.types.indexOf('administrative_area_level_1') > -1);
    const countryComponent = address.find(d => d.types.indexOf('country') > -1);
    const districtComponent = address.find(d => (
        d.types.indexOf('administrative_area_level_2') > -1 ||
        d.types.indexOf('administrative_area_level_3') > -1
    ));
    const pincodeComponent = address.find(d => d.types.indexOf('postal_code') > -1);
    const pincode = pincodeComponent ? pincodeComponent.long_name : null;

    const name = cityComponent ? cityComponent.long_name : null;
    const country = countryComponent ? countryComponent.short_name : null;
    const district = districtComponent ? districtComponent.long_name : null;
    const state = stateComponent ? stateComponent.short_name : null;

    sessionPlace({
        name,
        latitude: place.geometry.location.lat(),
        longitude: place.geometry.location.lng(),
        address: place.formatted_address,
        pincode,
        state,
        country,
        district,
        callBack: (response) => {
            jQuery('form.checkout [name="billing_city"]').val(`${name}${name.toLowerCase() !== district.toLowerCase() ? `, ${district}` : ""}`);
            jQuery('form.checkout [name="billing_country"]').val(country);
            jQuery('form.checkout [name="billing_state"]').val(state);
            jQuery('form.checkout [name="billing_address_1"]').val(place.formatted_address || "");
            jQuery('form.checkout [name="billing_address_2"]').val('');

            jQuery('form.checkout [name="shipping_city"]').val(`${name}${name.toLowerCase() !== district.toLowerCase() ? `, ${district}` : ""}`);
            jQuery('form.checkout [name="shipping_country"]').val(country);
            jQuery('form.checkout [name="shipping_state"]').val(state);
            jQuery('form.checkout [name="shipping_address_1"]').val(place.formatted_address || "");
            jQuery('form.checkout [name="shipping_address_2"]').val('');

            document.getElementById("billing_postcode").value = pincode;
            document.getElementById("shipping_postcode").value = pincode;

            setTimeout(() => {
                document.getElementById("shipping_postcode").dispatchEvent(new Event("keydown", { bubbles: true }));
                document.getElementById("billing_postcode").dispatchEvent(new Event("keydown", { bubbles: true }));
                location.reload(true);
            }, 100);
        }
    });
    marker.setPosition(place.geometry.location);
    marker.setVisible(true);
    map.setCenter(place.geometry.location);
    map.setZoom(15);
}
