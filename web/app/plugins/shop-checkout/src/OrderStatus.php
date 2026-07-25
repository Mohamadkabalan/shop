<?php

namespace App\ShopCheckout;

use WC_Order;

defined('ABSPATH') || exit;

/**
 * Adds a "Shipped" order status between Processing and Completed — the
 * default WooCommerce flow has no fulfillment-tracking step for physical
 * goods between "payment taken" and "order done".
 */
class OrderStatus
{
    private const STATUS_SLUG = 'wc-shipped';

    private const META_KEY = '_date_shipped';

    public function boot(): void
    {
        add_action('init', [$this, 'registerStatus']);
        add_filter('wc_order_statuses', [$this, 'insertStatusAfterProcessing']);
        add_action('woocommerce_order_status_changed', [$this, 'recordShippedDate'], 10, 4);
    }

    public function registerStatus(): void
    {
        register_post_status(self::STATUS_SLUG, [
            'label' => _x('Shipped', 'Order status', 'shop-checkout'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop(
                'Shipped <span class="count">(%s)</span>',
                'Shipped <span class="count">(%s)</span>',
                'shop-checkout'
            ),
        ]);
    }

    public function insertStatusAfterProcessing(array $orderStatuses): array
    {
        $withShipped = [];

        foreach ($orderStatuses as $slug => $label) {
            $withShipped[$slug] = $label;

            if ('wc-processing' === $slug) {
                $withShipped[self::STATUS_SLUG] = _x('Shipped', 'Order status', 'shop-checkout');
            }
        }

        return $withShipped;
    }

    public function recordShippedDate(int $orderId, string $oldStatus, string $newStatus, WC_Order $order): void
    {
        if ('shipped' !== $newStatus || $order->get_meta(self::META_KEY)) {
            return;
        }

        $order->update_meta_data(self::META_KEY, current_time('mysql'));
        $order->save();
    }
}
