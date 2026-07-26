<?php

namespace App\ShopApi;

defined('ABSPATH') || exit;

/**
 * WordPress's default REST CORS handling sends `Access-Control-Allow-Origin:
 * *`, which browsers refuse to combine with credentialed requests (cookies) —
 * and the cart endpoints need cookies, since that's how the WooCommerce cart
 * session is identified across requests from a separate frontend origin.
 *
 * Replaces the default with a single allowed origin (SHOP_FRONTEND_URL) sent
 * with Access-Control-Allow-Credentials, scoped to the myshop/v1 namespace
 * only so other REST usage (wp-admin, core, other plugins) is untouched.
 */
class Cors
{
    public function boot(): void
    {
        add_action('rest_api_init', function () {
            remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
            add_filter('rest_pre_serve_request', [$this, 'sendHeaders'], 10, 4);
        }, 15);
    }

    /**
     * @param mixed $served
     * @return mixed
     */
    public function sendHeaders($served, \WP_HTTP_Response $result, \WP_REST_Request $request, \WP_REST_Server $server)
    {
        if (! str_starts_with($request->get_route(), '/' . RestApi::NAMESPACE_ROUTE)) {
            return $served;
        }

        $allowedOrigin = untrailingslashit(getenv('SHOP_FRONTEND_URL') ?: home_url());
        $origin = get_http_origin();

        if ($origin && untrailingslashit($origin) === $allowedOrigin) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Access-Control-Allow-Credentials: true');
            header('Vary: Origin');
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');

        return $served;
    }
}
