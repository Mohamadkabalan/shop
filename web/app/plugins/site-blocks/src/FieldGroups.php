<?php

namespace App\SiteBlocks;

defined('ABSPATH') || exit;

/**
 * Registers ACF field groups for this plugin's blocks. The "block" location
 * rule (targeting a native block registered via block.json, not just ACF's
 * own acf_register_block_type()) requires ACF Pro 5.8+.
 *
 * Guarded by function_exists() so the whole plugin still activates cleanly,
 * and the block still registers (just with no editable fields and an empty
 * frontend render — see featured-products/render.php), if ACF Pro isn't
 * installed/active.
 */
class FieldGroups
{
    public function boot(): void
    {
        add_action('acf/init', [$this, 'registerFeaturedProductsFields']);
    }

    public function registerFeaturedProductsFields(): void
    {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_site_blocks_featured_products',
            'title' => 'Featured Products',
            'fields' => [
                [
                    'key' => 'field_site_blocks_fp_title',
                    'label' => 'Title',
                    'name' => 'title',
                    'type' => 'text',
                ],
                [
                    'key' => 'field_site_blocks_fp_products',
                    'label' => 'Products',
                    'name' => 'products',
                    'type' => 'relationship',
                    'instructions' => 'Search and add the specific products to feature.',
                    'post_type' => ['product'],
                    // Only the search box — the type/taxonomy filter dropdowns
                    // are pointless here since post_type is already fixed to
                    // "product". This is a manual picker: the editor searches
                    // and adds specific products one at a time, not a query.
                    'filters' => ['search'],
                    'return_format' => 'id',
                ],
                [
                    'key' => 'field_site_blocks_fp_columns',
                    'label' => 'Number of Columns',
                    'name' => 'columns',
                    'type' => 'select',
                    'choices' => [
                        '2' => '2',
                        '3' => '3',
                        '4' => '4',
                    ],
                    'default_value' => '3',
                    'allow_null' => 0,
                    'multiple' => 0,
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'site-blocks/featured-products',
                    ],
                ],
            ],
        ]);
    }
}
