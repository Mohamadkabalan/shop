<?php

/**
 * Idempotent WooCommerce configuration, run via WP-CLI instead of the
 * setup wizard:
 *
 *   wp eval-file bin/woocommerce-configure.php
 *
 * Requires the woocommerce plugin to already be active (see
 * bin/woocommerce-setup.sh, which activates it first). All values come
 * from .env — see the WC_* placeholders there.
 */

if (! defined('WP_CLI') || ! class_exists('WooCommerce')) {
    WP_CLI::error('WooCommerce must be an active plugin before running this script (wp plugin activate woocommerce).');
}

function wc_configure_env(string $key, string $default): string
{
    $value = getenv($key);

    return $value === false || $value === '' ? $default : $value;
}

// --- General settings: currency + store address -----------------------

$general_settings = [
    'woocommerce_currency' => wc_configure_env('WC_CURRENCY', 'USD'),
    'woocommerce_store_address' => wc_configure_env('WC_STORE_ADDRESS', '123 Placeholder St'),
    'woocommerce_store_address_2' => wc_configure_env('WC_STORE_ADDRESS_2', ''),
    'woocommerce_store_city' => wc_configure_env('WC_STORE_CITY', 'Placeholder City'),
    'woocommerce_default_country' => wc_configure_env('WC_STORE_COUNTRY', 'US:CA'),
    'woocommerce_store_postcode' => wc_configure_env('WC_STORE_POSTCODE', '00000'),
];

foreach ($general_settings as $option => $value) {
    update_option($option, $value);
    WP_CLI::log("Set {$option} = {$value}");
}

// --- Product settings: physical goods -----------------------------------

update_option('woocommerce_weight_unit', wc_configure_env('WC_WEIGHT_UNIT', 'kg'));
update_option('woocommerce_dimension_unit', wc_configure_env('WC_DIMENSION_UNIT', 'cm'));
// Shipping is only relevant to physical products; make sure it's on.
update_option('woocommerce_ship_to_destination', 'billing_then_shipping');
update_option('woocommerce_shipping_cost_requires_address', 'no');
WP_CLI::log('Set weight/dimension units and enabled shipping-to-destination for physical products.');

// Record the same "what do you sell" answer the onboarding wizard would
// have collected, and mark onboarding as done so wc-admin doesn't try to
// redirect the next admin visit into the wizard.
$onboarding_profile = get_option('woocommerce_onboarding_profile', []);
$onboarding_profile['product_types'] = ['physical'];
$onboarding_profile['skipped'] = true;
$onboarding_profile['completed'] = true;
update_option('woocommerce_onboarding_profile', $onboarding_profile);
delete_transient('_wc_activation_redirect');
WP_CLI::log('Marked onboarding profile as physical-goods / completed and cleared the activation redirect.');

// --- Shipping zone: flat rate + free-shipping threshold ------------------
//
// Fully idempotent: locations and per-method settings are re-applied on
// every run so the zone always converges to match .env, rather than only
// being set up once at creation time.

$zone_name = wc_configure_env('WC_SHIPPING_ZONE_NAME', 'Default Zone');
$zone_country = wc_configure_env('WC_SHIPPING_ZONE_COUNTRY', 'US');
$flat_rate_cost = wc_configure_env('WC_SHIPPING_FLAT_RATE_COST', '0');
$free_shipping_min_amount = wc_configure_env('WC_SHIPPING_FREE_SHIPPING_MIN_AMOUNT', '100');
$free_shipping_requires = wc_configure_env('WC_SHIPPING_FREE_SHIPPING_REQUIRES', 'min_amount');

$zone = null;
foreach (WC_Shipping_Zones::get_zones() as $zone_data) {
    if ($zone_data['zone_name'] === $zone_name) {
        $zone = new WC_Shipping_Zone($zone_data['id']);
        break;
    }
}

if ($zone) {
    WP_CLI::log("Shipping zone \"{$zone_name}\" already exists (id {$zone->get_id()}), updating in place.");
} else {
    $zone = new WC_Shipping_Zone();
    $zone->set_zone_name($zone_name);
    $zone->set_zone_order(0);
    $zone->save();
    WP_CLI::log("Created shipping zone \"{$zone_name}\" (id {$zone->get_id()}).");
}

$zone->set_locations([['code' => $zone_country, 'type' => 'country']]);
$zone->save();

/**
 * Find-or-create a shipping method instance on the zone by method id, then
 * unconditionally overwrite its settings option to match the given config.
 *
 * @param array<string, mixed> $settings
 */
function wc_configure_shipping_method(WC_Shipping_Zone $zone, string $method_id, array $settings): void
{
    $instance_id = null;

    foreach ($zone->get_shipping_methods() as $method) {
        if ($method->id === $method_id) {
            $instance_id = $method->get_instance_id();
            break;
        }
    }

    if ($instance_id === null) {
        $instance_id = $zone->add_shipping_method($method_id);
        WP_CLI::log("Added {$method_id} shipping method to zone \"{$zone->get_zone_name()}\" (instance {$instance_id}).");
    }

    update_option("woocommerce_{$method_id}_{$instance_id}_settings", $settings);
}

wc_configure_shipping_method($zone, 'flat_rate', [
    'title' => 'Flat rate',
    'tax_status' => 'taxable',
    'cost' => $flat_rate_cost,
]);

wc_configure_shipping_method($zone, 'free_shipping', [
    'title' => 'Free shipping',
    'requires' => $free_shipping_requires,
    'min_amount' => $free_shipping_min_amount,
]);

WP_CLI::log("Configured zone \"{$zone_name}\" covering {$zone_country}: flat rate {$flat_rate_cost}, free shipping over {$free_shipping_min_amount} ({$free_shipping_requires}).");

// --- Tax settings ----------------------------------------------------------

$tax_options = [
    'woocommerce_calc_taxes' => wc_configure_env('WC_TAX_ENABLED', 'yes'),
    'woocommerce_prices_include_tax' => wc_configure_env('WC_TAX_PRICES_INCLUDE_TAX', 'no'),
    'woocommerce_tax_based_on' => wc_configure_env('WC_TAX_BASED_ON', 'shipping'),
    'woocommerce_shipping_tax_class' => wc_configure_env('WC_TAX_SHIPPING_TAX_CLASS', 'inherit'),
    'woocommerce_tax_round_at_subtotal' => wc_configure_env('WC_TAX_ROUND_AT_SUBTOTAL', 'no'),
    'woocommerce_tax_display_shop' => wc_configure_env('WC_TAX_DISPLAY_SHOP', 'excl'),
    'woocommerce_tax_display_cart' => wc_configure_env('WC_TAX_DISPLAY_CART', 'excl'),
    'woocommerce_tax_total_display' => wc_configure_env('WC_TAX_TOTAL_DISPLAY', 'itemized'),
];

foreach ($tax_options as $option => $value) {
    update_option($option, $value);
    WP_CLI::log("Set {$option} = {$value}");
}

// Upsert a single standard-class tax rate by (country, state, name) rather
// than blindly inserting, so re-running this script doesn't pile up
// duplicate rows in wp_woocommerce_tax_rates every time.
$tax_rate_country = wc_configure_env('WC_TAX_RATE_COUNTRY', 'US');
$tax_rate_state = wc_configure_env('WC_TAX_RATE_STATE', '*');
$tax_rate_name = wc_configure_env('WC_TAX_RATE_NAME', 'Tax');

$tax_rate = [
    'tax_rate_country' => $tax_rate_country,
    'tax_rate_state' => $tax_rate_state,
    'tax_rate' => wc_configure_env('WC_TAX_RATE_PERCENT', '0'),
    'tax_rate_name' => $tax_rate_name,
    'tax_rate_priority' => wc_configure_env('WC_TAX_RATE_PRIORITY', '1'),
    'tax_rate_compound' => wc_configure_env('WC_TAX_RATE_COMPOUND', 'no') === 'yes' ? '1' : '0',
    'tax_rate_shipping' => wc_configure_env('WC_TAX_RATE_SHIPPING', 'yes') === 'yes' ? '1' : '0',
    'tax_rate_order' => '0',
    'tax_rate_class' => '', // standard rate
];

global $wpdb;

// '*' is the wildcard WooCommerce uses for "all countries/states"; it's
// normalized to '' in the rates table, so match on the normalized form.
$normalized_country = $tax_rate_country === '*' ? '' : strtoupper($tax_rate_country);
$normalized_state = $tax_rate_state === '*' ? '' : strtoupper($tax_rate_state);

$existing_rate_id = $wpdb->get_var($wpdb->prepare(
    "SELECT tax_rate_id FROM {$wpdb->prefix}woocommerce_tax_rates
     WHERE tax_rate_country = %s AND tax_rate_state = %s AND tax_rate_class = %s AND tax_rate_name = %s",
    $normalized_country,
    $normalized_state,
    '',
    $tax_rate_name,
));

if ($existing_rate_id) {
    WC_Tax::_update_tax_rate((int) $existing_rate_id, $tax_rate);
    WP_CLI::log("Updated standard tax rate \"{$tax_rate_name}\" (id {$existing_rate_id}) to {$tax_rate['tax_rate']}% for {$tax_rate_country}/{$tax_rate_state}.");
} else {
    $new_rate_id = WC_Tax::_insert_tax_rate($tax_rate);
    WP_CLI::log("Created standard tax rate \"{$tax_rate_name}\" (id {$new_rate_id}) at {$tax_rate['tax_rate']}% for {$tax_rate_country}/{$tax_rate_state}.");
}

WP_CLI::success('WooCommerce configured from .env placeholders. Review General/Shipping/Tax settings before going live.');
