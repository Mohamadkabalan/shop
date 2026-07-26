<?php

/**
 * Configuration overrides for WP_ENV === 'production'
 */

use Roots\WPConfig\Config;

// Caching is on in production: Redis object cache plus WP Super Cache's
// full-page cache for anonymous visitors (WooCommerce excludes its own
// cart/checkout/my-account pages from page caching via DONOTCACHEPAGE).
Config::define('WP_REDIS_DISABLED', false);
Config::define('WP_CACHE', true);
