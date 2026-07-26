<?php

namespace App\ShopApi;

use WC_Product;

defined('ABSPATH') || exit;

/**
 * Formats a WC_Product into a plain array of scalars/arrays — clean JSON,
 * no HTML except the description fields (which are legitimately rich text;
 * a headless frontend renders that HTML same as it would from any other
 * headless CMS content API).
 */
class ProductFormatter
{
    /**
     * @return array<string, mixed>
     */
    public static function summary(WC_Product $product): array
    {
        return [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'permalink' => $product->get_permalink(),
            'sku' => $product->get_sku(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'on_sale' => $product->is_on_sale(),
            'currency' => get_woocommerce_currency(),
            'in_stock' => $product->is_in_stock(),
            'purchasable' => $product->is_purchasable(),
            'average_rating' => (float) $product->get_average_rating(),
            'review_count' => $product->get_review_count(),
            'image' => self::imageUrl($product->get_image_id()),
            'categories' => self::categories($product),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function detail(WC_Product $product): array
    {
        return self::summary($product) + [
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'stock_status' => $product->get_stock_status(),
            'stock_quantity' => $product->get_stock_quantity(),
            'weight' => $product->get_weight(),
            'dimensions' => [
                'length' => $product->get_length(),
                'width' => $product->get_width(),
                'height' => $product->get_height(),
            ],
            'gallery_images' => array_map([self::class, 'imageUrl'], $product->get_gallery_image_ids()),
            'attributes' => self::attributes($product),
        ];
    }

    /**
     * @param int|string $attachmentId get_image_id()/get_gallery_image_ids() return '' (not 0) when unset.
     */
    private static function imageUrl($attachmentId): ?string
    {
        $attachmentId = (int) $attachmentId;

        if (! $attachmentId) {
            return wc_placeholder_img_src();
        }

        $url = wp_get_attachment_image_url($attachmentId, 'woocommerce_single');

        return $url ?: wc_placeholder_img_src();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function categories(WC_Product $product): array
    {
        $terms = get_the_terms($product->get_id(), 'product_cat');

        if (! is_array($terms)) {
            return [];
        }

        return array_map(
            fn($term) => ['id' => $term->term_id, 'name' => $term->name, 'slug' => $term->slug],
            $terms,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function attributes(WC_Product $product): array
    {
        $formatted = [];

        foreach ($product->get_attributes() as $attribute) {
            $formatted[] = [
                'name' => wc_attribute_label($attribute->get_name()),
                'options' => $attribute->is_taxonomy()
                    ? wp_list_pluck($attribute->get_terms() ?: [], 'name')
                    : $attribute->get_options(),
                'variation' => $attribute->get_variation(),
            ];
        }

        return $formatted;
    }
}
