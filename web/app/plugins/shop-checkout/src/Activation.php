<?php

namespace App\ShopCheckout;

defined('ABSPATH') || exit;

/**
 * One-time setup applied when the plugin is activated. Deliberately not
 * re-applied on every request so a store owner's later changes in
 * WooCommerce settings aren't silently overwritten.
 */
class Activation
{
    public static function activate(): void
    {
        update_option('woocommerce_enable_guest_checkout', 'yes');
        update_option('woocommerce_checkout_company_field', 'optional');
    }
}
