<?php

/**
 * Renders the Featured Products block on the frontend.
 *
 * Included directly by core (block.json's "render" file convention), so
 * this runs in global scope with $attributes/$content/$block already set.
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string $content Block's saved inner content (always empty — see index.js's save()).
 * @var WP_Block $block Block instance.
 */

defined('ABSPATH') || exit;

// Degrade to nothing rather than fatal if either dependency is missing —
// this block is meaningless without both (ACF supplies the field values,
// WooCommerce supplies the products).
if (! function_exists('get_field') || ! class_exists('WooCommerce')) {
    return;
}

$title = get_field('title');
$productIds = get_field('products');
$columns = get_field('columns') ?: '3';

if (empty($productIds)) {
    return;
}
?>
<div <?php echo get_block_wrapper_attributes(['class' => 'site-blocks-featured-products columns-' . esc_attr($columns)]); ?>>
    <?php if ($title) : ?>
        <h2 class="site-blocks-featured-products__title"><?php echo esc_html($title); ?></h2>
    <?php endif; ?>
    <div class="site-blocks-featured-products__grid">
        <?php foreach ($productIds as $productId) :
            $product = wc_get_product($productId);

            if (! $product) {
                continue;
            }
        ?>
            <div class="site-blocks-featured-products__card">
                <a class="site-blocks-featured-products__link" href="<?php echo esc_url(get_permalink($productId)); ?>">
                    <div class="site-blocks-featured-products__image">
                        <?php echo get_the_post_thumbnail($productId, 'woocommerce_thumbnail'); ?>
                    </div>
                    <div class="site-blocks-featured-products__name"><?php echo esc_html($product->get_name()); ?></div>
                    <div class="site-blocks-featured-products__price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
