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
    /** @var array<int, string> */
    private const BLOCKS = ['featured-products', 'contact-form'];

    public function boot(): void
    {
        add_action('init', [$this, 'registerBlocks']);
    }

    public function registerBlocks(): void
    {
        foreach (self::BLOCKS as $block) {
            $buildPath = dirname(__DIR__) . "/build/{$block}";

            if (file_exists($buildPath . '/block.json')) {
                register_block_type($buildPath);
            }
        }
    }
}
