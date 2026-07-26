<?php

/**
 * Configuration overrides for WP_ENV === 'development'
 */

use Roots\WPConfig\Config;

use function Env\env;

Config::define('SAVEQUERIES', true);
Config::define('WP_DEBUG', true);
Config::define('WP_DEBUG_DISPLAY', true);
Config::define('WP_DEBUG_LOG', env('WP_DEBUG_LOG') ?? true);
Config::define('WP_DISABLE_FATAL_ERROR_HANDLER', true);
Config::define('SCRIPT_DEBUG', true);
Config::define('DISALLOW_INDEXING', true);

ini_set('display_errors', '1');

// Enable plugin and theme updates and installation from the admin
Config::define('DISALLOW_FILE_MODS', false);

// Caching stays off in development so code/content changes are always
// reflected immediately, with no stale page or object cache to work around.
Config::define('WP_REDIS_DISABLED', true);
Config::define('WP_CACHE', false);

// WordPress's default "pseudo-cron" spawns a loopback HTTP request on every
// page load whenever anything is due, and that request runs due jobs (WP-Cron
// events, WooCommerce's Action Scheduler queue) synchronously — with several
// plugins each scheduling their own hooks, a slow one (e.g. an external HTTP
// check) blocks that visitor's entire page load. Run `wp cron event run
// --due-now` manually (or on a real schedule in staging/production) instead.
Config::define('DISABLE_WP_CRON', true);
