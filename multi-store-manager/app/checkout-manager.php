<?php
class CheckoutManager
{
    public function __construct()
    {
        add_action('woocommerce_before_checkout_billing_form', [$this, 'renderMap'], 15);
    }

    public function renderMap() {
        echo '<div class="map-box">
            <input id="search-place" class="controls" type="text" placeholder="Search for Places, Cities" />
            <button class="current-location"> <i class="fa fa-compass"></i> Fetch Location </button>
            <div id="map-container"></div>
        </div>';
    }
}
