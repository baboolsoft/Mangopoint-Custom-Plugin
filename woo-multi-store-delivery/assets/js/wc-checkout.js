jQuery(document).ready(function ($) {
    // {}
    const { city = "", country = "", district = "", state = "", pincode = "" } = JSON.parse(localStorage.getItem('wc_multi_store')) || {};
    jQuery('form.checkout [name="billing_city"]').val(`${city}, ${district}`);
    jQuery('form.checkout [name="billing_country"]').val(country);
    jQuery('form.checkout [name="billing_state"]').val(state);
    jQuery('form.checkout [name="billing_postcode"]').val(pincode);
    $('#billing_city').blur(handleChange);
    $('#billing_postcode').blur(handleChange);

    setTimeout(() => {
        handleChange();
    }, 100);

    function handleChange(event) {
        jQuery.ajax({
            type: "POST",
            url: `${window.location.origin}/wp-json/api/validate-checkout/`,
            data: {
                place: $('#billing_city').val(),
                postcode: $('#billing_postcode').val(),
            },
            beforeSend: () => {
                jQuery('body').addClass('wc-ms-loading');
            },
            success: ({ data }) => {
                data.forEach(({ exist }, index) => {
                    if (!exist) {
                        jQuery(`.shop_table .cart_item:eq(${index})`).addClass('lbpl-product-exist');
                        jQuery(`.shop_table .cart_item:eq(${index})`).find('.product-name').append(`<span class="lbpl-product-exist-text">Product not available for delivery</span>`);
                    } else {
                        jQuery(`.shop_table .cart_item:eq(${index})`).removeClass('lbpl-product-exist');
                        jQuery(`.shop_table .cart_item:eq(${index})`).find('.product-name .lbpl-product-exist-text').remove();
                    }
                });
                jQuery('body').removeClass('wc-ms-loading');
            }
        });

    }

});
