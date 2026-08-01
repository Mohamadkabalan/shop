<?php

namespace App\SiteBlocks;

defined('ABSPATH') || exit;

/**
 * Registers the "Site Blocks" inserter category so this plugin's blocks are
 * grouped together instead of falling under a generic built-in category.
 */
class BlockCategory
{
    public const SLUG = 'site-blocks';

    public function boot(): void
    {
        add_filter('block_categories_all', [$this, 'registerCategory']);
    }

    /**
     * @param array<int, array<string, string>> $categories
     * @return array<int, array<string, string>>
     */
    public function registerCategory(array $categories): array
    {
        return array_merge($categories, [
            [
                'slug' => self::SLUG,
                'title' => __('Site Blocks', 'site-blocks'),
            ],
        ]);
    }
}
