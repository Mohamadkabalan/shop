<?php

namespace App\ShopAccount;

use WC_Order;

defined('ABSPATH') || exit;

/**
 * WooCommerce only shows an "Order again" button on the single order-view
 * page, and only for orders in the "completed" status. This adds the same
 * action directly to the My Account orders list, and extends eligibility to
 * the store's custom "Shipped" status — a shipped physical-goods order is
 * just as reorderable as a completed one.
 */
class ReorderOrders
{
    public function boot(): void
    {
        add_filter('woocommerce_valid_order_statuses_for_order_again', [$this, 'allowReorderingShippedOrders']);
        add_filter('woocommerce_my_account_my_orders_actions', [$this, 'addReorderAction'], 10, 2);
    }

    /**
     * @param array<int, string> $statuses
     * @return array<int, string>
     */
    public function allowReorderingShippedOrders(array $statuses): array
    {
        $statuses[] = 'shipped';

        return $statuses;
    }

    /**
     * @param array<string, array<string, string>> $actions
     * @return array<string, array<string, string>>
     */
    public function addReorderAction(array $actions, WC_Order $order): array
    {
        $validStatuses = apply_filters('woocommerce_valid_order_statuses_for_order_again', ['completed']);

        if (! is_user_logged_in() || ! $order->has_status($validStatuses)) {
            return $actions;
        }

        $actions['order-again'] = [
            'url' => wp_nonce_url(add_query_arg('order_again', $order->get_id(), wc_get_cart_url()), 'woocommerce-order_again'),
            'name' => __('Order again', 'shop-account'),
            'aria-label' => sprintf(
                /* translators: %s: order number */
                __('Order %s again', 'shop-account'),
                $order->get_order_number(),
            ),
        ];

        return $actions;
    }
}
