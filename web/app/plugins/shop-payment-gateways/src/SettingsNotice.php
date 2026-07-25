<?php

namespace App\ShopPaymentGateways;

defined('ABSPATH') || exit;

/**
 * WC_Settings_API-based gateways (both Stripe and PayPal Payments here) will
 * write whatever is currently displayed back to wp_options if an admin
 * saves their settings page — including our .env-injected values, since
 * from the form's point of view they're just the current field values.
 * That's a limitation of overriding legacy options-based settings via
 * filters rather than forking the plugins; this notice exists so nobody is
 * surprised what looks like a "real" secret is sitting in the database.
 */
class SettingsNotice
{
    public function boot(): void
    {
        add_action('admin_notices', [$this, 'maybeRender']);
    }

    public function maybeRender(): void
    {
        if (($_GET['page'] ?? '') !== 'wc-settings' || ($_GET['tab'] ?? '') !== 'checkout') {
            return;
        }

        printf(
            '<div class="notice notice-info"><p>%s</p></div>',
            esc_html__(
                'Payment gateway API keys on this page come from .env (WP_ENV controls sandbox vs. live). Saving this page will write the currently-displayed values back into the database as a side effect of how WooCommerce settings work — that\'s expected, .env remains the source of truth on the next deploy.',
                'shop-payment-gateways'
            )
        );
    }
}
