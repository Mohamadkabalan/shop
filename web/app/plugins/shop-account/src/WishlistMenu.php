<?php

namespace App\ShopAccount;

defined('ABSPATH') || exit;

/**
 * YITH WooCommerce Wishlist creates its own standalone page (shortcode-based,
 * id stored in the yith_wcwl_wishlist_page_id option) but never links it from
 * the WooCommerce My Account navigation — without this, customers have no way
 * to find their wishlist from their account.
 *
 * "wishlist" isn't a real WooCommerce account endpoint (it's a separate page,
 * not an /my-account/wishlist/ tab), so the nav link is added as a plain menu
 * item and its generated URL is redirected to the actual page permalink via
 * the woocommerce_get_endpoint_url filter.
 */
class WishlistMenu
{
    private const MENU_KEY = 'wishlist';

    public function boot(): void
    {
        add_action('init', [$this, 'ensureWishlistPageExists']);
        add_filter('woocommerce_account_menu_items', [$this, 'addWishlistMenuItem']);
        add_filter('woocommerce_get_endpoint_url', [$this, 'pointWishlistAtItsPage'], 10, 2);
    }

    /**
     * YITH only creates its wishlist page from an is_admin() check on init
     * (see YITH_WCWL_Install::maybe_install), so it never runs when this
     * store is provisioned headlessly via WP-CLI (bin/woocommerce-setup.sh).
     * Delegate to YITH's own installer — rather than duplicating its slug
     * and page content here — but only on the first request, since its
     * init() unconditionally re-registers the plugin version option.
     */
    public function ensureWishlistPageExists(): void
    {
        if (get_option('yith_wcwl_wishlist_page_id') || ! class_exists(\YITH_WCWL_Install::class)) {
            return;
        }

        \YITH_WCWL_Install::get_instance()->init();
    }

    /**
     * @param array<string, string> $items
     * @return array<string, string>
     */
    public function addWishlistMenuItem(array $items): array
    {
        if (! $this->wishlistPageId()) {
            return $items;
        }

        $withWishlist = [];

        foreach ($items as $key => $label) {
            $withWishlist[$key] = $label;

            if ('orders' === $key) {
                $withWishlist[self::MENU_KEY] = __('Wishlist', 'shop-account');
            }
        }

        // "orders" wasn't in the menu (unlikely, but don't silently drop the link).
        if (! isset($withWishlist[self::MENU_KEY])) {
            $withWishlist[self::MENU_KEY] = __('Wishlist', 'shop-account');
        }

        return $withWishlist;
    }

    public function pointWishlistAtItsPage(string $url, string $endpoint): string
    {
        if (self::MENU_KEY !== $endpoint) {
            return $url;
        }

        $pageId = $this->wishlistPageId();

        return $pageId ? get_permalink($pageId) : $url;
    }

    private function wishlistPageId(): int
    {
        $pageId = (int) get_option('yith_wcwl_wishlist_page_id');

        if (! $pageId || 'publish' !== get_post_status($pageId)) {
            return 0;
        }

        return $pageId;
    }
}
