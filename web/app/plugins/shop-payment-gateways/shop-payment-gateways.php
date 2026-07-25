<?php

/**
 * Plugin Name: Shop Payment Gateway Credentials
 * Description: Sources Stripe and PayPal Payments API credentials from .env instead of the database, and forces sandbox/test mode whenever WP_ENV isn't "production".
 * Version: 1.0.0
 * Author: Custom
 * Text Domain: shop-payment-gateways
 */

namespace App\ShopPaymentGateways;

defined('ABSPATH') || exit;

add_action('plugins_loaded', function () {
    (new StripeCredentials())->boot();
    (new PayPalCredentials())->boot();
    (new SettingsNotice())->boot();
});
