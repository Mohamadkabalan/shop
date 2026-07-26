<?php

namespace App\ShopApi\Controllers;

use App\ShopApi\ProductFormatter;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') || exit;

/**
 * Wraps WooCommerce's own session-backed WC_Cart — the same cart a browser
 * session sees on the storefront itself, identified by WooCommerce's session
 * cookie. For this to work from a separate frontend origin, requests must be
 * made with credentials included (e.g. fetch's `credentials: 'include'`) and
 * the browser must accept the cross-site cookie (in production, that means
 * serving the frontend over HTTPS so the session cookie can use
 * SameSite=None; Secure). See Cors.php for the matching CORS headers.
 */
class CartController
{
    public function getCart(): WP_REST_Response
    {
        wc_load_cart();

        return new WP_REST_Response($this->formatCart());
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public function addItem(WP_REST_Request $request)
    {
        wc_load_cart();

        $productId = $request->get_param('product_id');
        $product = wc_get_product($productId);

        if (! $product || 'publish' !== $product->get_status()) {
            return new WP_Error('product_not_found', 'Product not found.', ['status' => 404]);
        }

        if (! $product->is_purchasable() || ! $product->is_in_stock()) {
            return new WP_Error('product_not_purchasable', 'This product is not currently available for purchase.', ['status' => 409]);
        }

        wc_clear_notices();
        $cartItemKey = WC()->cart->add_to_cart($productId, $request->get_param('quantity'), $request->get_param('variation_id'));
        $errors = $this->pullErrorNotices();

        if (! $cartItemKey) {
            return new WP_Error('cannot_add_to_cart', $errors ?: 'Could not add this product to the cart.', ['status' => 400]);
        }

        return new WP_REST_Response($this->formatCart());
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public function updateItem(WP_REST_Request $request)
    {
        wc_load_cart();

        $key = $request->get_param('key');

        if (! WC()->cart->get_cart_item($key)) {
            return new WP_Error('cart_item_not_found', 'No item with that key is in the cart.', ['status' => 404]);
        }

        wc_clear_notices();
        $quantity = $request->get_param('quantity');
        $updated = 0 === $quantity
            ? WC()->cart->remove_cart_item($key)
            : WC()->cart->set_quantity($key, $quantity);
        $errors = $this->pullErrorNotices();

        if (! $updated) {
            return new WP_Error('cannot_update_cart_item', $errors ?: 'Could not update this cart item.', ['status' => 400]);
        }

        return new WP_REST_Response($this->formatCart());
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public function removeItem(WP_REST_Request $request)
    {
        wc_load_cart();

        $key = $request->get_param('key');

        if (! WC()->cart->get_cart_item($key)) {
            return new WP_Error('cart_item_not_found', 'No item with that key is in the cart.', ['status' => 404]);
        }

        WC()->cart->remove_cart_item($key);

        return new WP_REST_Response($this->formatCart());
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCart(): array
    {
        $cart = WC()->cart;

        $items = array_map(
            fn(array $item) => [
                'key' => $item['key'],
                'product' => ProductFormatter::summary($item['data']),
                'quantity' => $item['quantity'],
                'line_subtotal' => wc_format_decimal($item['line_subtotal'], 2),
                'line_total' => wc_format_decimal($item['line_total'], 2),
            ],
            $cart->get_cart(),
        );

        return [
            'items' => array_values($items),
            'item_count' => $cart->get_cart_contents_count(),
            'currency' => get_woocommerce_currency(),
            'subtotal' => wc_format_decimal($cart->get_subtotal(), 2),
            'discount_total' => wc_format_decimal($cart->get_discount_total(), 2),
            'shipping_total' => wc_format_decimal($cart->get_shipping_total(), 2),
            'tax_total' => wc_format_decimal($cart->get_total_tax(), 2),
            'total' => wc_format_decimal($cart->get_total('edit'), 2),
        ];
    }

    private function pullErrorNotices(): string
    {
        $notices = wp_list_pluck(wc_get_notices('error'), 'notice');
        wc_clear_notices();

        return implode(' ', array_map('wp_strip_all_tags', $notices));
    }
}
