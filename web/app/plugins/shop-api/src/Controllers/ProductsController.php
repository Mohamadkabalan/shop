<?php

namespace App\ShopApi\Controllers;

use App\ShopApi\ProductFormatter;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined('ABSPATH') || exit;

class ProductsController
{
    public function listItems(WP_REST_Request $request): WP_REST_Response
    {
        $args = [
            'status' => 'publish',
            'limit' => $request->get_param('per_page'),
            'page' => $request->get_param('page'),
            'paginate' => true,
        ];

        $category = $request->get_param('category');
        if ($category) {
            $args['category'] = [$category];
        }

        $search = $request->get_param('search');
        if ($search) {
            $args['s'] = $search;
        }

        $results = wc_get_products($args);

        $items = array_map(
            fn($product) => ProductFormatter::summary($product),
            $results->products,
        );

        $response = new WP_REST_Response($items);
        $response->header('X-WP-Total', (string) $results->total);
        $response->header('X-WP-TotalPages', (string) $results->max_num_pages);

        return $response;
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public function getItem(WP_REST_Request $request)
    {
        $product = wc_get_product($request->get_param('id'));

        if (! $product || 'publish' !== $product->get_status()) {
            return new WP_Error('product_not_found', 'Product not found.', ['status' => 404]);
        }

        return new WP_REST_Response(ProductFormatter::detail($product));
    }
}
