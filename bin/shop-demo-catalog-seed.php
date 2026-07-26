<?php

/**
 * OPTIONAL dev/demo data — deliberately NOT part of the reproducible
 * production seed chain (bin/woocommerce-setup.sh never calls this file).
 * Populates a local/demo environment with enough real catalog data to
 * actually see the Shop Filters sidebar (bin/shop-filters-configure.php)
 * filter something: 2 categories, Color/Size attribute terms, and 5 example
 * products spanning a price range.
 *
 * Run after the main setup, once Color/Size attributes exist:
 *
 *   wp eval-file bin/shop-filters-configure.php
 *   wp eval-file bin/shop-demo-catalog-seed.php
 *
 * Safe to re-run: matches existing demo categories/terms/products by
 * name/SKU rather than creating duplicates.
 */

if (! defined('WP_CLI') || ! class_exists('WooCommerce')) {
    WP_CLI::error('WooCommerce must be an active plugin before running this script (wp plugin activate woocommerce).');
}

if (! taxonomy_exists('pa_color') || ! taxonomy_exists('pa_size')) {
    WP_CLI::error('The Color/Size attributes don\'t exist yet — run `wp eval-file bin/shop-filters-configure.php` first.');
}

function shop_demo_ensure_category(string $name): int
{
    $existing = get_term_by('name', $name, 'product_cat');

    if ($existing) {
        return $existing->term_id;
    }

    $result = wp_insert_term($name, 'product_cat');

    if (is_wp_error($result)) {
        WP_CLI::error("Could not create category \"{$name}\": {$result->get_error_message()}");
    }

    WP_CLI::log("Created demo category \"{$name}\".");

    return $result['term_id'];
}

/**
 * @param array<int, string> $names
 * @return array<string, int> Term IDs keyed by name.
 */
function shop_demo_ensure_terms(string $taxonomy, array $names): array
{
    $termIds = [];

    foreach ($names as $name) {
        $existing = get_term_by('name', $name, $taxonomy);

        if ($existing) {
            $termIds[$name] = $existing->term_id;

            continue;
        }

        $result = wp_insert_term($name, $taxonomy);

        if (is_wp_error($result)) {
            WP_CLI::error("Could not create term \"{$name}\" in {$taxonomy}: {$result->get_error_message()}");
        }

        WP_CLI::log("Created demo attribute term \"{$name}\" ({$taxonomy}).");

        $termIds[$name] = $result['term_id'];
    }

    return $termIds;
}

/**
 * @param array<string, int> $colorTermIds Subset of demo color term IDs, keyed by name, to assign.
 * @param array<string, int> $sizeTermIds Subset of demo size term IDs, keyed by name, to assign.
 */
function shop_demo_ensure_product(
    string $sku,
    string $name,
    string $price,
    int $categoryId,
    array $colorTermIds,
    array $sizeTermIds,
): void {
    $existingId = wc_get_product_id_by_sku($sku);
    $product = $existingId ? wc_get_product($existingId) : new WC_Product_Simple();

    $product->set_name($name);
    $product->set_sku($sku);
    $product->set_regular_price($price);
    $product->set_status('publish');
    $product->set_category_ids([$categoryId]);

    $attributes = [];

    if ($colorTermIds) {
        $colorAttribute = new WC_Product_Attribute();
        $colorAttribute->set_id(wc_attribute_taxonomy_id_by_name('color'));
        $colorAttribute->set_name('pa_color');
        $colorAttribute->set_options(array_values($colorTermIds));
        $colorAttribute->set_visible(true);
        $attributes[] = $colorAttribute;
    }

    if ($sizeTermIds) {
        $sizeAttribute = new WC_Product_Attribute();
        $sizeAttribute->set_id(wc_attribute_taxonomy_id_by_name('size'));
        $sizeAttribute->set_name('pa_size');
        $sizeAttribute->set_options(array_values($sizeTermIds));
        $sizeAttribute->set_visible(true);
        $attributes[] = $sizeAttribute;
    }

    $product->set_attributes($attributes);
    $product->save();

    WP_CLI::log(($existingId ? 'Updated' : 'Created') . " demo product \"{$name}\" ({$sku}).");
}

$apparelCategoryId = shop_demo_ensure_category('Apparel');
$accessoriesCategoryId = shop_demo_ensure_category('Accessories');

$colors = shop_demo_ensure_terms('pa_color', ['Red', 'Blue', 'Green']);
$sizes = shop_demo_ensure_terms('pa_size', ['Small', 'Medium', 'Large']);

shop_demo_ensure_product(
    'DEMO-TSHIRT',
    'Classic T-Shirt',
    '19.99',
    $apparelCategoryId,
    array_intersect_key($colors, array_flip(['Red', 'Blue'])),
    $sizes,
);

shop_demo_ensure_product(
    'DEMO-HOODIE',
    'Pullover Hoodie',
    '49.99',
    $apparelCategoryId,
    array_intersect_key($colors, array_flip(['Blue', 'Green'])),
    array_intersect_key($sizes, array_flip(['Medium', 'Large'])),
);

shop_demo_ensure_product(
    'DEMO-TOTE',
    'Canvas Tote Bag',
    '14.50',
    $accessoriesCategoryId,
    array_intersect_key($colors, array_flip(['Green'])),
    [],
);

shop_demo_ensure_product(
    'DEMO-CAP',
    'Baseball Cap',
    '22.00',
    $accessoriesCategoryId,
    array_intersect_key($colors, array_flip(['Red'])),
    [],
);

shop_demo_ensure_product(
    'DEMO-BEANIE',
    'Wool Beanie',
    '16.75',
    $accessoriesCategoryId,
    array_intersect_key($colors, array_flip(['Red', 'Green'])),
    [],
);

WP_CLI::success('Demo catalog seeded: 2 categories, Color/Size terms, and 5 example products.');
