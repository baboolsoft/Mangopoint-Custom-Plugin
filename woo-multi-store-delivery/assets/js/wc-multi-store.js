/**
 * Frontend JavaScript for WooCommerce Multi-Store Delivery
 */
;(($) => {
  // Initialize when document is ready
  $(document).ready(() => {
    // Check if localStorage is available
    if (typeof Storage === "undefined") {
      console.error("localStorage is not available. Some features may not work correctly.")
      return
    }

    // Add selected city to cart form
    $(document.body).on("adding_to_cart", (event, $button, data) => {
      data.selected_city = localStorage.getItem("wc_multi_store_city")
    })

    // Add selected city to checkout form
    $(document.body).on("updated_checkout", () => {
      var selectedCity = localStorage.getItem("wc_multi_store_city")

      if (selectedCity) {
        // Try to set shipping city
        $("#shipping_city").val(selectedCity)

        // If billing is same as shipping, set billing city too
        if (
          $("#ship-to-different-address-checkbox").length &&
          !$("#ship-to-different-address-checkbox").is(":checked")
        ) {
          $("#billing_city").val(selectedCity)
        }
      }
    })
  })
})(jQuery)
