<?php

namespace App\ShopPaymentGateways;

defined('ABSPATH') || exit;

/**
 * Overrides a gateway's stored settings option at read time with values
 * sourced from .env, without ever writing secrets to wp_options ourselves.
 *
 * Hooks both `option_{name}` and `default_option_{name}`: WordPress only
 * fires `option_{name}` once a row exists for that option, so a plugin that
 * has never been opened in wp-admin (no saved settings row yet) would
 * otherwise skip our override entirely.
 */
abstract class CredentialInjector
{
    abstract protected function optionName(): string;

    /**
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    abstract protected function inject(array $settings): array;

    public function boot(): void
    {
        add_filter('option_' . $this->optionName(), [$this, 'handle']);
        add_filter('default_option_' . $this->optionName(), [$this, 'handle']);
    }

    /**
     * @param mixed $settings
     * @return array<string, mixed>
     */
    public function handle($settings): array
    {
        return $this->inject(is_array($settings) ? $settings : []);
    }

    protected function isTestMode(): bool
    {
        return ! defined('WP_ENV') || WP_ENV !== 'production';
    }

    /**
     * @param array<string, mixed> $settings
     */
    protected function envOrExisting(array $settings, string $key, string $envVar): string
    {
        $value = getenv($envVar);

        return ($value !== false && $value !== '') ? $value : ($settings[$key] ?? '');
    }
}
