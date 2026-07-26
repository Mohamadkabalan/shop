<?php

/**
 * Idempotent product reviews/ratings + moderation setup, run via WP-CLI:
 *
 *   wp eval-file bin/shop-reviews-configure.php
 *
 * Requires the woocommerce plugin to already be active. Configures
 * WooCommerce's own review settings (WooCommerce > Settings > Products >
 * Reviews) plus the WordPress "Discussion" options that govern moderation,
 * since product reviews are ordinary wp_comments rows (comment_type =
 * 'review') and WordPress doesn't have a review-specific moderation queue.
 *
 * Caveat: comment_moderation, comment_previously_approved, and
 * comment_max_links are sitewide — they also apply to comments on any blog
 * posts/pages this install has, not just product reviews.
 */

if (! defined('WP_CLI') || ! class_exists('WooCommerce')) {
    WP_CLI::error('WooCommerce must be an active plugin before running this script (wp plugin activate woocommerce).');
}

function shop_reviews_configure_env(string $key, string $default): string
{
    $value = getenv($key);

    return $value === false || $value === '' ? $default : $value;
}

// --- WooCommerce review/rating display -------------------------------------

$review_settings = [
    'woocommerce_enable_reviews' => shop_reviews_configure_env('WC_REVIEWS_ENABLED', 'yes'),
    'woocommerce_enable_review_rating' => shop_reviews_configure_env('WC_REVIEWS_RATING_ENABLED', 'yes'),
    'woocommerce_review_rating_required' => shop_reviews_configure_env('WC_REVIEWS_RATING_REQUIRED', 'yes'),
    'woocommerce_review_rating_verification_label' => shop_reviews_configure_env('WC_REVIEWS_VERIFIED_LABEL', 'yes'),
    // Hides the review submission form entirely from anyone who hasn't
    // bought the product — see wc_customer_bought_product() in
    // templates/single-product-reviews.php.
    'woocommerce_review_rating_verification_required' => shop_reviews_configure_env('WC_REVIEWS_VERIFIED_ONLY', 'yes'),
];

foreach ($review_settings as $option => $value) {
    update_option($option, $value);
    WP_CLI::log("Set {$option} = {$value}");
}

// --- Moderation (WordPress Discussion settings, sitewide) ------------------

$moderation_settings = [
    // Hold every new review for manual approval before it's public.
    'comment_moderation' => shop_reviews_configure_env('WC_REVIEWS_REQUIRE_MODERATION', 'yes') === 'yes' ? '1' : '0',
    // ...unless this same person already has a previously-approved review, in
    // which case let them skip the queue on repeat reviews.
    'comment_previously_approved' => shop_reviews_configure_env('WC_REVIEWS_AUTO_APPROVE_RETURNING', 'yes') === 'yes' ? '1' : '0',
    // Auto-hold (regardless of the above) any review containing more than
    // this many links — a common spam signal.
    'comment_max_links' => shop_reviews_configure_env('WC_REVIEWS_MAX_LINKS', '2'),
];

foreach ($moderation_settings as $option => $value) {
    update_option($option, $value);
    WP_CLI::log("Set {$option} = {$value}");
}

WP_CLI::success('Product reviews configured from .env placeholders. Review Products > Reviews and Settings > Discussion before going live.');
