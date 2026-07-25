<?php

namespace App\ShopPaymentGateways;

defined('ABSPATH') || exit;

/**
 * WooCommerce PayPal Payments normally gets client_id/client_secret through
 * an OAuth-style "Connect with PayPal" flow (GeneralSettings::set_merchant_data()),
 * not manual entry. Its data model supports manual credentials too
 * (`use_manual_connection`), which is what we lean on here — same flat
 * option-array mechanism as Stripe (AbstractDataModel::load()/save() against
 * a single wp_options row), just under GeneralSettings::OPTION_KEY.
 *
 * PayPal issues separate sandbox and live app credentials (unlike Stripe,
 * which just prefixes test keys in the same settings row), so which .env
 * pair we inject depends on which mode is active.
 */
class PayPalCredentials extends CredentialInjector
{
    protected function optionName(): string
    {
        return 'woocommerce-ppcp-data-common';
    }

    protected function inject(array $settings): array
    {
        $isSandbox = $this->isTestMode();

        $settings['use_sandbox'] = $isSandbox;
        $settings['sandbox_merchant'] = $isSandbox;
        $settings['use_manual_connection'] = true;

        if ($isSandbox) {
            $settings['client_id'] = $this->envOrExisting($settings, 'client_id', 'PAYPAL_SANDBOX_CLIENT_ID');
            $settings['client_secret'] = $this->envOrExisting($settings, 'client_secret', 'PAYPAL_SANDBOX_CLIENT_SECRET');
        } else {
            $settings['client_id'] = $this->envOrExisting($settings, 'client_id', 'PAYPAL_CLIENT_ID');
            $settings['client_secret'] = $this->envOrExisting($settings, 'client_secret', 'PAYPAL_CLIENT_SECRET');
        }

        $settings['merchant_connected'] = (bool) ($settings['client_id'] && $settings['client_secret']);

        return $settings;
    }
}
