document.addEventListener("DOMContentLoaded", function () {
    window.scrollTo({ top: 0, behavior: 'smooth' });
    jQuery(".msm-wrap").each(function () {
        jQuery(this).find(".add-btn").on("click", (e) => {
            e.preventDefault();
            jQuery(this).find('input[name="id"]').remove()
            jQuery(this).find('form .title').html(`Add New ${jQuery(this).find('form').data('title')} | Form`);
            jQuery(this).find('form').slideToggle()
        });
        jQuery(this).find('form').submit(function (e) {
            e.preventDefault();
            fn.form(this);
        });
    });
});

const fn = {
    form: (form) => {
        let data = {
            data: new FormData(form),
            processData: false,
            contentType: false
        }
        if (jQuery(form).hasClass('config')) {
            const formData = jQuery(form).serializeArray();
            const value = {};
            formData.forEach(e => {
                value[e.name] = e.value
            });
            data = {
                data: {
                    manage: "config",
                    data: value
                }
            }
        }
        jQuery.ajax({
            ...data,
            url: `${window.location.origin}/wp-json/multi-store-manager/v1/api/form-submit/`,
            type: "POST",
            beforeSend: () => {
                jQuery(form).find('.submit').attr('disabled', true).text('Submitting Form...');
            },
            success: ({ status = false, message }) => {
                jQuery(form).find('.error').removeClass('hidden').text(message);
                if (status) {
                    setTimeout(() => {
                        location.reload(true);
                    }, 1000);
                }
                else {
                    jQuery(form).find('.submit').attr('disabled', false).text('Submit');
                }
            },
            error: ({ message = "Something, went wrong!" }) => {
                jQuery(form).find('.submit').attr('disabled', false).text('Submit');
                jQuery(form).find('.error').removeClass('hidden').text(message);
            },
        });
    }
};
