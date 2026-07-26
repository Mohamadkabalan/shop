<?php

/**
 * Idempotent shop-page filtering setup, run via WP-CLI instead of clicking
 * through wp-admin > Appearance > Widgets:
 *
 *   wp eval-file bin/shop-filters-configure.php
 *
 * Requires the woocommerce plugin to already be active. Creates two example
 * global attributes (Color, Size) — placeholders for the attribute filter
 * widget, rename/remove them for the real catalog before going live — and
 * seeds WooCommerce's native filter widgets into the "Shop Filters" sidebar
 * (registered in web/app/themes/sage/app/setup.php, rendered by
 * web/app/themes/sage/woocommerce/global/sidebar.php).
 *
 * Deliberately uses WooCommerce's built-in classic widgets (price range,
 * category, attribute, rating, active-filters, search) rather than a
 * third-party filter plugin — they already do full category/price
 * range/attribute filtering with no extra dependency.
 */

if (! defined('WP_CLI') || ! class_exists('WooCommerce')) {
    WP_CLI::error('WooCommerce must be an active plugin before running this script (wp plugin activate woocommerce).');
}

const SHOP_FILTERS_SIDEBAR_ID = 'sidebar-shop';

/**
 * Find-or-create a global product attribute (e.g. "Color"), returning its
 * taxonomy name (e.g. "pa_color"). Re-registers attribute taxonomies in the
 * current request afterwards, since WC_Post_Types::register_taxonomies()
 * normally only runs once, on 'init', before this script's changes exist.
 */
function shop_filters_ensure_attribute(string $name): string
{
    $slug = wc_sanitize_taxonomy_name($name);

    foreach (wc_get_attribute_taxonomies() as $attribute) {
        if ($attribute->attribute_name === $slug) {
            return wc_attribute_taxonomy_name($slug);
        }
    }

    $result = wc_create_attribute([
        'name' => $name,
        'slug' => $slug,
        'type' => 'select',
        'order_by' => 'menu_order',
        'has_archives' => false,
    ]);

    if (is_wp_error($result)) {
        WP_CLI::error("Could not create the \"{$name}\" attribute: {$result->get_error_message()}");
    }

    WP_CLI::log("Created example product attribute \"{$name}\" ({$slug}).");

    // Registered taxonomies are cached for the request; refresh so the new
    // pa_{$slug} taxonomy is usable immediately (e.g. by the demo catalog seed).
    delete_transient('wc_attribute_taxonomies');
    WC_Post_Types::register_taxonomies();

    return wc_attribute_taxonomy_name($slug);
}

/**
 * Find-or-create a widget instance in the shop sidebar and overwrite its
 * settings to match, so this script converges to the same state on every
 * run instead of piling up duplicate widgets.
 *
 * @param array<string, mixed> $instance
 * @param callable(array<string, mixed>): bool $isMatch Identifies "this same widget" among existing instances of $idBase.
 */
function shop_filters_upsert_widget(string $idBase, array $instance, callable $isMatch): void
{
    $optionName = "widget_{$idBase}";
    $instances = get_option($optionName);
    if (! is_array($instances)) {
        $instances = [];
    }

    $number = null;
    foreach ($instances as $key => $existing) {
        if ('_multiwidget' === $key || ! is_array($existing)) {
            continue;
        }
        if ($isMatch($existing)) {
            $number = $key;
            break;
        }
    }

    if (null === $number) {
        $numericKeys = array_filter(array_keys($instances), 'is_int');
        $number = $numericKeys ? max($numericKeys) + 1 : 1;
    }

    $instances[$number] = $instance;
    $instances['_multiwidget'] = 1;
    update_option($optionName, $instances);

    $widgetId = "{$idBase}-{$number}";
    $sidebarsWidgets = wp_get_sidebars_widgets();

    if (empty($sidebarsWidgets[SHOP_FILTERS_SIDEBAR_ID]) || ! is_array($sidebarsWidgets[SHOP_FILTERS_SIDEBAR_ID])) {
        $sidebarsWidgets[SHOP_FILTERS_SIDEBAR_ID] = [];
    }

    if (! in_array($widgetId, $sidebarsWidgets[SHOP_FILTERS_SIDEBAR_ID], true)) {
        $sidebarsWidgets[SHOP_FILTERS_SIDEBAR_ID][] = $widgetId;
    }

    wp_set_sidebars_widgets($sidebarsWidgets);

    WP_CLI::log("Configured \"{$idBase}\" widget (instance {$widgetId}) in the Shop Filters sidebar.");
}

if (! is_registered_sidebar(SHOP_FILTERS_SIDEBAR_ID)) {
    WP_CLI::error('The "sidebar-shop" widget area is not registered — check web/app/themes/sage/app/setup.php and that the Sage theme is active.');
}

// --- Example attributes, used by the attribute filter widgets below -------

$colorTaxonomy = shop_filters_ensure_attribute('Color');
$sizeTaxonomy = shop_filters_ensure_attribute('Size');

// --- Filter widgets, in the order they should read top-to-bottom ----------

shop_filters_upsert_widget(
    'woocommerce_layered_nav_filters',
    ['title' => __('Active filters', 'woocommerce')],
    fn() => true,
);

shop_filters_upsert_widget(
    'woocommerce_product_search',
    ['title' => ''],
    fn() => true,
);

shop_filters_upsert_widget(
    'woocommerce_product_categories',
    [
        'title' => __('Categories', 'woocommerce'),
        'orderby' => 'name',
        'dropdown' => 0,
        'count' => 1,
        'hierarchical' => 1,
        'show_children_only' => 0,
        'hide_empty' => 0,
        'max_depth' => '',
    ],
    fn() => true,
);

shop_filters_upsert_widget(
    'woocommerce_price_filter',
    ['title' => __('Filter by price', 'woocommerce')],
    fn() => true,
);

foreach (['Color' => $colorTaxonomy, 'Size' => $sizeTaxonomy] as $label => $taxonomy) {
    $attributeSlug = str_replace('pa_', '', $taxonomy);

    shop_filters_upsert_widget(
        'woocommerce_layered_nav',
        [
            'title' => $label,
            'attribute' => $attributeSlug,
            'display_type' => 'list',
            'query_type' => 'and',
        ],
        fn(array $existing) => ($existing['attribute'] ?? null) === $attributeSlug,
    );
}

shop_filters_upsert_widget(
    'woocommerce_rating_filter',
    ['title' => __('Average rating', 'woocommerce')],
    fn() => true,
);

WP_CLI::success('Shop Filters sidebar configured: search, categories, price range, color/size attributes, and rating.');
