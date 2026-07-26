<?php

/**
 * Plugin Name: Shop REST API
 * Description: Custom REST API (myshop/v1) exposing WooCommerce products, cart, and checkout for a headless frontend. Authenticated via WordPress Application Passwords.
 * Version: 1.0.0
 * Author: Custom
 * Text Domain: shop-api
 */

namespace App\ShopApi;

defined('ABSPATH') || exit;

add_action('plugins_loaded', function () {
    if (! class_exists('WooCommerce')) {
        return;
    }

    (new Cors())->boot();
    (new RestApi())->boot();
});
