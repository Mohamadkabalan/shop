<?php

namespace App\SiteBlocks;

defined('ABSPATH') || exit;

/**
 * Registers this plugin's blocks from their built (not source) output.
 *
 * Source lives in src/<block-name>/; @wordpress/scripts compiles it into
 * build/<block-name>/ (see README.md). build/ is gitignored — same as
 * Sage's public/build — so a fresh clone won't have it until `npm install
 * && npm run build` has been run once.
 */
class BlockRegistration
{
    public function boot(): void
    {
        add_action('init', [$this, 'registerBlocks']);
    }

    public function registerBlocks(): void
    {
        $buildPath = dirname(__DIR__) . '/build/featured-products';

        if (! file_exists($buildPath . '/block.json')) {
            return;
        }

        register_block_type($buildPath);
    }
}
