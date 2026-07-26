<?php

/**
 * Plugin Name: Shop Account Customizations
 * Description: My Account order-history enhancements (reorder from the orders list) and a My Account menu link into the YITH Wishlist page.
 * Version: 1.0.0
 * Author: Custom
 * Text Domain: shop-account
 */

namespace App\ShopAccount;

defined('ABSPATH') || exit;

add_action('plugins_loaded', function () {
    if (! class_exists('WooCommerce')) {
        return;
    }

    (new ReorderOrders())->boot();

    // YITH_WCWL is defined by yith-woocommerce-wishlist/init.php; all active
    // plugins have run by the time plugins_loaded fires, regardless of load
    // order, so this check is safe even though "shop-account" sorts before
    // "yith-woocommerce-wishlist" alphabetically.
    if (defined('YITH_WCWL')) {
        (new WishlistMenu())->boot();
    }
});
