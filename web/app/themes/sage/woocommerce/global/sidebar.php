<?php
/**
 * Renders the "Shop Filters" widget area (registered in app/setup.php) on
 * product-listing pages only — shop, category, tag, and attribute archives.
 * Overrides WooCommerce's default template, which calls get_sidebar('shop')
 * against a theme root file (sidebar-shop.php) that this Blade-based theme
 * doesn't have, so nothing would otherwise render here.
 *
 * @see woocommerce/global/wrapper-start.php for the "main" counterpart.
 */

defined('ABSPATH') || exit;

if (! (is_shop() || is_product_taxonomy()) || ! is_active_sidebar('sidebar-shop')) {
    return;
}
?>
<aside class="sidebar">
    <?php dynamic_sidebar('sidebar-shop'); ?>
</aside>
