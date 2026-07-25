<?php

/**
 * Plugin Name: Shop Checkout Customizations
 * Description: Guest checkout, custom checkout fields (company name, delivery notes), and a custom "Shipped" order status for the physical-goods storefront.
 * Version: 1.0.0
 * Author: Custom
 * Text Domain: shop-checkout
 */

namespace App\ShopCheckout;

defined('ABSPATH') || exit;

register_activation_hook(__FILE__, [Activation::class, 'activate']);

add_action('plugins_loaded', function () {
    if (! class_exists('WooCommerce')) {
        return;
    }

    (new CheckoutFields())->boot();
    (new OrderStatus())->boot();
});
