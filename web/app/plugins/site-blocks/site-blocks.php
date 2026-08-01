<?php

/**
 * Plugin Name: Site Blocks
 * Description: Custom Gutenberg blocks for this site, built with @wordpress/scripts. Block fields are managed via ACF Pro (block-targeted field groups). See README.md for the build workflow.
 * Version: 1.0.0
 * Author: Custom
 * Text Domain: site-blocks
 */

namespace App\SiteBlocks;

defined('ABSPATH') || exit;

add_action('plugins_loaded', function () {
    if (! class_exists('WooCommerce')) {
        return;
    }

    (new BlockCategory())->boot();
    (new BlockRegistration())->boot();
    (new FieldGroups())->boot();
});
