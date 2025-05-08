jQuery('document').ready(function () {
    jQuery("ul.hfe-nav-menu").append(`<li class="location-pin menu-item menu-item-type-post_type menu-item-object-page parent hfe-creative-menu">
        <button class="location-pin-wrapper">
            <div class="location-pin-button" title="Select Location">
                <i class="fa fa-map-marked-alt"></i>
            </div>
            <h6 class="label">Select Location</h6>
        </button>
    </li>`);

    setTimeout(() => {
        if (jQuery('.lbpl').length > 0) {
            init.retiveProducts();
        }
        jQuery('.location-pin-wrapper').on('click', addSearchBox);
        validateCheckout();
    }, 100);
});

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
                localStorage.setItem('coords', JSON.stringify({ latitude, longitude }));
                if (typeof callBack === "function") {
                    callBack();
                }
            }), () => {
                localStorage.setItem('coords', JSON.stringify({ latitude: 0, longitude: 0 }));
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

const addSearchBox = () => {

    const { top, left } = jQuery('.location-pin-button').offset();
    const screenWidth = (window.innerWidth);

    jQuery('body').append(`<div class="location-popup-wrapper">
        <div class="location-popup" style="right: ${screenWidth - left - 25}px; top: ${top + 40}px;">
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
        initMap().handleKeyup();
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
            url: `${window.location.origin}/wp-json/api/products/`,
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
            success: ({ status, data }) => {
                if (status && data.length > 0) {

                    if (typeof callBack === "function") {
                        callBack(data);
                    }

                    jQuery(`.lbpl`).each(function () {
                        const { limit = -1, category = [], sort = "menu-order" } = jQuery(this).data();
                        let html = "";
                        let products = data;

                        if (sort === "best-selling") {
                            products = products.sort((a, b) => b.sale - a.sale);
                        }

                        if (category.length > 0) {
                            products = products.filter(item => ((item.cat) && (typeof item.cat === "object") && (item.cat.filter(e => category.includes(e)).length > 0)));
                        }

                        if (limit > 0) {
                            products = products.slice(0, limit);
                        }

                        products.forEach(item => {

                            let btntxt = `<a class="btn" href="${item.slug}">View Products</a>`;

                            if ((item.status !== "outofstock") && (item.type === "simple")) {
                                btntxt = `<a href="?add-to-cart=${item.id}" aria-describedby="woocommerce_loop_add_to_cart_link_describedby_${item.id}" data-quantity="1" class="btn add_to_cart_button ajax_add_to_cart" data-product_id="${item.id}" data-product_sku="" aria-label="Add to cart: “${item.title}”" rel="nofollow" data-success_message="“${item.title}” has been added to your cart">Add to cart</a>`;
                            }

                            html += `<li class="product-item">
                                <div class="product-wrap">
                                    <div class="product">
                                        <div>
                                           <a href="${item.slug}" class="product-img">
                                                <img src="${item.img}" alt="${item.title}" loading="eager" decoding="async" width="300" height="300" />
                                                 ${item.status === "outofstock" ? `<p class="no-stock">Coming Soon</p>` : ""}
                                            </a>
                                            <a class="title" href="${item.slug}">
                                                <h2>${item.title}</h2>
                                            </a>
                                        </div>
                                        <div class="info">
                                            ${item.rating}
                                            <p class="price">
                                                ${!["₹0.00", 0, "", "0"].includes(item.rprice) ? `<del>${item.rprice}</del>` : ""}
                                                ${item.price}
                                            </p>
                                            ${btntxt}
                                        </div>
                                    </div>
                                </div>
                            </li>`;
                        });

                        jQuery(this).find(`.result.woocommerce`).html(`<ul class="product-list">
                            ${html}
                        </ul>`);
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
                            localStorage.setItem('coords', JSON.stringify({
                                latitude: jQuery(this).data("lat"),
                                longitude: jQuery(this).data("lng"),
                                name: jQuery(this).find('.city').html(),
                                address: jQuery(this).find('.address').html(),
                            }));
                            jQuery('.location-pin-button').attr('title', jQuery(this).find('.city').html());
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

function initMap() {
    let map, service;

    map = new google.maps.Map(document.createElement("div"), {
        center: { lat: 0, lng: 0 },
        zoom: 2,
    });
    service = new google.maps.places.PlacesService(map);

    // initMap().handleLocationChange();

    return {
        init: () => {
            
        },
        handleKeyup: (e) => {
            document.querySelector('.location-popup-wrapper .search-box input').addEventListener("input", function () {
                const query = this.value;
                if (query.length > 0) {
                    initMap().performSearch(query);
                } else {
                    initMap().handleEmpty();
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
                query: `${query} India`,
                fields: ["name", "formatted_address", "place_id"],
                types: ["locality"]
            };

            service.textSearch(request, function (results, status) {
                if (status === google.maps.places.PlacesServiceStatus.OK) {
                    initMap().addPlace(results);
                } else {
                    initMap().handleEmpty();
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
                        localStorage.setItem('coords', JSON.stringify({
                            latitude: jQuery(this).data("lat"),
                            longitude: jQuery(this).data("lng"),
                            name: jQuery(this).find('.city').html(),
                            address: jQuery(this).find('.address').html(),
                        }));
                        jQuery('.location-pin-button').attr('title', jQuery(this).find('.city').html());
                        removeSearchBox();
                        setTimeout(() => {
                            restCall();
                        }, 100);
                    });
                });
            }, 0);
        },
        handleLocationChange: () => {
            const latField = document.querySelector('input[name="wcfmmp_user_location_lat"]');
            const lngField = document.querySelector('input[name="wcfmmp_user_location_lng"]');

            if (latField && lngField) {
                [latField, lngField].each(function () {
                    jQuery(this).on('change', function (e) {
                        console.log(this, e.target.value);
                    });
                });
            }
        }
    }
}
