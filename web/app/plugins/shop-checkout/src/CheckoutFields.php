<?php

namespace App\ShopCheckout;

use WC_Order;

defined('ABSPATH') || exit;

/**
 * Registers the custom "Delivery notes" checkout field (Block-based
 * checkout, via the Additional Checkout Fields API) and surfaces it
 * wherever the built-in "Order notes" field already appears: the admin
 * order screen and order emails.
 *
 * "Company name" isn't handled here — it's a built-in address field that
 * WooCommerce ships hidden by default for the block checkout; enabling it
 * is just an option flip, done once in Activation.
 */
class CheckoutFields
{
    private const FIELD_ID = 'shop-checkout/delivery-notes';

    private const META_KEY = '_wc_other/' . self::FIELD_ID;

    public function boot(): void
    {
        add_action('woocommerce_init', [$this, 'registerDeliveryNotesField']);
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'renderInAdminOrder']);
        add_action('woocommerce_email_order_meta', [$this, 'renderInEmail'], 10, 3);
    }

    public function registerDeliveryNotesField(): void
    {
        woocommerce_register_additional_checkout_field([
            'id' => self::FIELD_ID,
            'label' => __('Delivery notes', 'shop-checkout'),
            'optionalLabel' => __('Delivery notes (optional)', 'shop-checkout'),
            'location' => 'order',
            'type' => 'text',
            'required' => false,
        ]);
    }

    public function renderInAdminOrder(WC_Order $order): void
    {
        $notes = $order->get_meta(self::META_KEY);

        if (! $notes) {
            return;
        }

        printf(
            '<p class="form-field form-field-wide"><strong>%s:</strong> %s</p>',
            esc_html__('Delivery notes', 'shop-checkout'),
            esc_html($notes)
        );
    }

    public function renderInEmail(WC_Order $order, $sent_to_admin, $plain_text): void
    {
        $notes = $order->get_meta(self::META_KEY);

        if (! $notes) {
            return;
        }

        if ($plain_text) {
            echo esc_html__('Delivery notes', 'shop-checkout') . ": {$notes}\n";

            return;
        }

        printf(
            '<p><strong>%s:</strong> %s</p>',
            esc_html__('Delivery notes', 'shop-checkout'),
            esc_html($notes)
        );
    }
}
