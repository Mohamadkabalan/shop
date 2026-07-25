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

// --- Shipping zone placeholder -------------------------------------------

$zone_name = wc_configure_env('WC_SHIPPING_ZONE_NAME', 'Default Zone');
$zone_country = wc_configure_env('WC_SHIPPING_ZONE_COUNTRY', 'US');
$flat_rate_cost = wc_configure_env('WC_SHIPPING_FLAT_RATE_COST', '0');

$existing_zone = null;
foreach (WC_Shipping_Zones::get_zones() as $zone_data) {
    if ($zone_data['zone_name'] === $zone_name) {
        $existing_zone = new WC_Shipping_Zone($zone_data['id']);
        break;
    }
}

if ($existing_zone) {
    WP_CLI::log("Shipping zone \"{$zone_name}\" already exists (id {$existing_zone->get_id()}), skipping creation.");
} else {
    $zone = new WC_Shipping_Zone();
    $zone->set_zone_name($zone_name);
    $zone->set_zone_order(0);
    $zone_id = $zone->save();

    $zone->add_location($zone_country, 'country');
    $zone->save(); // add_location() only queues the prop; it isn't persisted until save().

    $instance_id = $zone->add_shipping_method('flat_rate');
    update_option("woocommerce_flat_rate_{$instance_id}_settings", [
        'title' => 'Flat rate',
        'tax_status' => 'taxable',
        'cost' => $flat_rate_cost,
    ]);

    WP_CLI::log("Created shipping zone \"{$zone_name}\" (id {$zone_id}) covering {$zone_country} with a flat rate of {$flat_rate_cost}.");
}

WP_CLI::success('WooCommerce configured from .env placeholders. Review General/Shipping settings before going live.');
