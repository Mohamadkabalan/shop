<?php

namespace App\ShopApi;

use App\ShopApi\Controllers\CartController;
use App\ShopApi\Controllers\CheckoutController;
use App\ShopApi\Controllers\ProductsController;

defined('ABSPATH') || exit;

/**
 * Registers the myshop/v1 REST namespace. Route handlers live in
 * Controllers/ — this class only wires HTTP method/path/args to them.
 */
class RestApi
{
    public const NAMESPACE_ROUTE = 'myshop/v1';

    public function boot(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        $products = new ProductsController();
        $cart = new CartController();
        $checkout = new CheckoutController();

        register_rest_route(self::NAMESPACE_ROUTE, '/products', [
            'methods' => 'GET',
            'callback' => [$products, 'listItems'],
            'permission_callback' => '__return_true',
            'args' => [
                'page' => [
                    'type' => 'integer',
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => fn($value) => (int) $value >= 1,
                ],
                'per_page' => [
                    'type' => 'integer',
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => fn($value) => (int) $value >= 1 && (int) $value <= 100,
                ],
                'category' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_title',
                ],
                'search' => [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE_ROUTE, '/products/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$products, 'getItem'],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'type' => 'integer',
                    'required' => true,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE_ROUTE, '/cart', [
            'methods' => 'GET',
            'callback' => [$cart, 'getCart'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::NAMESPACE_ROUTE, '/cart/items', [
            'methods' => 'POST',
            'callback' => [$cart, 'addItem'],
            'permission_callback' => '__return_true',
            'args' => [
                'product_id' => [
                    'type' => 'integer',
                    'required' => true,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => fn($value) => (int) $value > 0,
                ],
                'quantity' => [
                    'type' => 'integer',
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => fn($value) => (int) $value >= 1,
                ],
                'variation_id' => [
                    'type' => 'integer',
                    'default' => 0,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE_ROUTE, '/cart/items/(?P<key>[a-z0-9]+)', [
            [
                'methods' => 'PUT',
                'callback' => [$cart, 'updateItem'],
                'permission_callback' => '__return_true',
                'args' => [
                    'key' => [
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'quantity' => [
                        'type' => 'integer',
                        'required' => true,
                        'sanitize_callback' => 'absint',
                        'validate_callback' => fn($value) => (int) $value >= 0,
                    ],
                ],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$cart, 'removeItem'],
                'permission_callback' => '__return_true',
                'args' => [
                    'key' => [
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE_ROUTE, '/checkout', [
            'methods' => 'POST',
            'callback' => [$checkout, 'createOrder'],
            'permission_callback' => '__return_true',
            'args' => $checkout->getArgsSchema(),
        ]);
    }
}
