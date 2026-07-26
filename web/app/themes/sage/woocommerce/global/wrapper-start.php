<?php
/**
 * Opening wrapper for all WooCommerce content (shop, single product, cart,
 * checkout, my-account). Overrides WooCommerce's default template, which
 * only assigns theme-specific markup for a handful of legacy default themes
 * (twentyten..twentysixteen) and falls back to generic, unstyled markup
 * otherwise — leaving WooCommerce pages outside the `#app` CSS grid's
 * "main" area (see resources/css/app.css).
 *
 * @see woocommerce/global/sidebar.php for the "sidebar" counterpart.
 */

defined('ABSPATH') || exit;
?>
<main id="main" class="main">
