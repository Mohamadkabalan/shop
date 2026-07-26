<?php

/**
 * Idempotent SEO setup, run via WP-CLI:
 *
 *   wp eval-file bin/shop-seo-configure.php
 *
 * Ensures the Rank Math modules this store needs are active — Schema
 * (rich-snippet: Product structured data via
 * includes/modules/schema/snippets/class-product-woocommerce.php), the
 * WooCommerce integration (brand/GTIN, product-aware breadcrumbs), and XML
 * Sitemaps — and that products/product categories are included in the
 * sitemap. Rank Math's own installer already enables all three by default
 * on first activation; this script converges to that state on every run in
 * case a future update changes the defaults, or WooCommerce wasn't active
 * yet when Rank Math was first installed.
 */

if (! defined('WP_CLI') || ! class_exists('WooCommerce')) {
    WP_CLI::error('WooCommerce must be an active plugin before running this script (wp plugin activate woocommerce).');
}

if (! defined('RANK_MATH_VERSION')) {
    WP_CLI::error('Rank Math must be an active plugin before running this script (wp plugin activate seo-by-rank-math).');
}

// --- Modules: Schema, WooCommerce integration, XML Sitemaps ----------------

$required_modules = ['rich-snippet', 'woocommerce', 'sitemap'];
$active_modules = get_option('rank_math_modules', []);

if (! is_array($active_modules)) {
    $active_modules = [];
}

$missing_modules = array_diff($required_modules, $active_modules);

if ($missing_modules) {
    update_option('rank_math_modules', array_values(array_unique(array_merge($active_modules, $required_modules))));
    WP_CLI::log('Enabled Rank Math modules: ' . implode(', ', $missing_modules));
} else {
    WP_CLI::log('Rank Math modules already enabled: ' . implode(', ', $required_modules));
}

// --- Sitemap: make sure products and product categories are included -------

$sitemap_settings = get_option('rank-math-options-sitemap', []);

if (! is_array($sitemap_settings)) {
    $sitemap_settings = [];
}

$sitemap_settings['pt_product_sitemap'] = 'on';
$sitemap_settings['tax_product_cat_sitemap'] = 'on';

update_option('rank-math-options-sitemap', $sitemap_settings);
WP_CLI::log('Sitemap includes products and product categories.');

// The /sitemap_index.xml endpoint is a rewrite rule; make sure it's registered.
flush_rewrite_rules();

WP_CLI::success('Rank Math configured: WooCommerce product schema markup and XML sitemaps enabled.');
