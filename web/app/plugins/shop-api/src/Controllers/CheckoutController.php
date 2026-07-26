<?php

namespace App\ShopApi\Controllers;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') || exit;

/**
 * Creates a WooCommerce order from the current cart, in "pending payment"
 * status — it does not collect payment itself. The frontend is expected to
 * take the returned order id/key and total to whichever payment flow the
 * chosen gateway needs next (e.g. Stripe/PayPal's own client-side SDK),
 * exactly like WooCommerce's own checkout page hands off to those gateways'
 * process_payment() after order creation.
 *
 * Guest checkout is supported (matches woocommerce_enable_guest_checkout):
 * an authenticated request (Application Passwords) attaches customer_id via
 * get_current_user_id(); an unauthenticated request creates a guest order
 * identified only by the billing email.
 */
class CheckoutController
{
    private const ADDRESS_FIELDS = [
        'first_name' => 'sanitize_text_field',
        'last_name' => 'sanitize_text_field',
        'company' => 'sanitize_text_field',
        'address_1' => 'sanitize_text_field',
        'address_2' => 'sanitize_text_field',
        'city' => 'sanitize_text_field',
        'state' => 'sanitize_text_field',
        'postcode' => 'sanitize_text_field',
        'country' => 'sanitize_text_field',
        'email' => 'sanitize_email',
        'phone' => 'sanitize_text_field',
    ];

    private const REQUIRED_BILLING_FIELDS = ['first_name', 'last_name', 'address_1', 'city', 'country', 'email'];

    /**
     * @return array<string, mixed>
     */
    public function getArgsSchema(): array
    {
        return [
            'billing' => [
                'type' => 'object',
                'required' => true,
            ],
            'shipping' => [
                'type' => 'object',
                'required' => false,
            ],
            'payment_method' => [
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_key',
            ],
            'order_comments' => [
                'type' => 'string',
                'required' => false,
                'sanitize_callback' => 'sanitize_textarea_field',
            ],
        ];
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public function createOrder(WP_REST_Request $request)
    {
        wc_load_cart();

        if (WC()->cart->is_empty()) {
            return new WP_Error('cart_empty', 'The cart is empty.', ['status' => 400]);
        }

        $billing = $this->sanitizeAddress((array) $request->get_param('billing'));
        $missing = array_diff(self::REQUIRED_BILLING_FIELDS, array_keys(array_filter($billing)));

        if ($missing) {
            return new WP_Error(
                'missing_billing_fields',
                'Missing required billing fields: ' . implode(', ', $missing),
                ['status' => 400],
            );
        }

        if (! is_email($billing['email'])) {
            return new WP_Error('invalid_billing_email', 'The billing email address is not valid.', ['status' => 400]);
        }

        $shippingParam = $request->get_param('shipping');
        $shipping = $shippingParam ? $this->sanitizeAddress((array) $shippingParam) : $billing;

        $paymentMethod = $request->get_param('payment_method');
        $availableGateways = WC()->payment_gateways->get_available_payment_gateways();

        if (! isset($availableGateways[$paymentMethod])) {
            return new WP_Error(
                'invalid_payment_method',
                'Unknown or unavailable payment method. Available: ' . implode(', ', array_keys($availableGateways)),
                ['status' => 400],
            );
        }

        WC()->cart->calculate_totals();

        $orderData = ['payment_method' => $paymentMethod];

        foreach ($billing as $field => $value) {
            $orderData["billing_{$field}"] = $value;
        }

        foreach ($shipping as $field => $value) {
            if (isset(self::ADDRESS_FIELDS[$field])) {
                $orderData["shipping_{$field}"] = $value;
            }
        }

        if ($request->get_param('order_comments')) {
            $orderData['order_comments'] = $request->get_param('order_comments');
        }

        $orderId = WC()->checkout()->create_order($orderData);

        if (is_wp_error($orderId)) {
            $orderId->add_data(['status' => 400]);

            return $orderId;
        }

        WC()->session->set('order_awaiting_payment', $orderId);
        WC()->session->save_data();

        $order = wc_get_order($orderId);

        return new WP_REST_Response([
            'order_id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'order_key' => $order->get_order_key(),
            'status' => $order->get_status(),
            'total' => wc_format_decimal($order->get_total(), 2),
            'currency' => $order->get_currency(),
            'payment_method' => $order->get_payment_method(),
            'customer_id' => $order->get_customer_id(),
        ], 201);
    }

    /**
     * @param array<string, mixed> $address
     * @return array<string, string>
     */
    private function sanitizeAddress(array $address): array
    {
        $sanitized = [];

        foreach (self::ADDRESS_FIELDS as $field => $sanitizer) {
            if (isset($address[$field])) {
                $sanitized[$field] = $sanitizer($address[$field]);
            }
        }

        return $sanitized;
    }
}
