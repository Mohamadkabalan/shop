<?php

namespace App\ShopPaymentGateways;

defined('ABSPATH') || exit;

/**
 * WooCommerce Stripe Gateway stores everything in a single option as a flat
 * array (WC_Stripe_Helper::SETTINGS_OPTION); testmode/keys are just array
 * keys within it, so overriding it here is enough for both the gateway
 * itself and WC_Stripe_Mode::is_test()/is_live().
 */
class StripeCredentials extends CredentialInjector
{
    protected function optionName(): string
    {
        return 'woocommerce_stripe_settings';
    }

    protected function inject(array $settings): array
    {
        $settings['testmode'] = $this->isTestMode() ? 'yes' : 'no';

        $settings['publishable_key'] = $this->envOrExisting($settings, 'publishable_key', 'STRIPE_PUBLISHABLE_KEY');
        $settings['secret_key'] = $this->envOrExisting($settings, 'secret_key', 'STRIPE_SECRET_KEY');
        $settings['webhook_secret'] = $this->envOrExisting($settings, 'webhook_secret', 'STRIPE_WEBHOOK_SECRET');

        $settings['test_publishable_key'] = $this->envOrExisting($settings, 'test_publishable_key', 'STRIPE_TEST_PUBLISHABLE_KEY');
        $settings['test_secret_key'] = $this->envOrExisting($settings, 'test_secret_key', 'STRIPE_TEST_SECRET_KEY');
        $settings['test_webhook_secret'] = $this->envOrExisting($settings, 'test_webhook_secret', 'STRIPE_TEST_WEBHOOK_SECRET');

        return $settings;
    }
}
