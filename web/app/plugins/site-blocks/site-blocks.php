<?php

/**
 * Plugin Name: Site Blocks
 * Description: Custom Gutenberg blocks for this site, built with @wordpress/scripts. See README.md for the build workflow.
 * Version: 1.0.0
 * Author: Custom
 * Text Domain: site-blocks
 */

namespace App\SiteBlocks;

defined('ABSPATH') || exit;

// Must run unconditionally, outside plugins_loaded: WordPress fires the
// activate_{plugin} action during the activation request itself, so the
// callback has to already be registered by the time that happens.
register_activation_hook(__FILE__, [ContactSubmissionsTable::class, 'install']);

add_action('plugins_loaded', function () {
    // Registration and category setup have no WooCommerce dependency —
    // only Featured Products' *rendering* needs it, and that's already
    // guarded inside its own render.php. Gating the whole plugin here would
    // wrongly disable the (WooCommerce-independent) Contact Form block too.
    (new BlockCategory())->boot();
    (new BlockRegistration())->boot();
    (new ContactFormHandler())->boot();

    if (class_exists('WooCommerce')) {
        (new FieldGroups())->boot();
    }
});
