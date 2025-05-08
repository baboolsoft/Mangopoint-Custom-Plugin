document.addEventListener("DOMContentLoaded", function () {

    const fn = {
        init: function () {
            this.addSearchBox();
        },
        addSearchBox: function () {
            const headerEle = jQuery('header .elementor-container:eq(0)');
            const logoEle = jQuery(headerEle).find('.elementor-element:has(hfe-site-logo-img)');
        },
    }

    fn.init();
});