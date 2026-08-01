<?php

namespace App\SiteBlocks;

defined('ABSPATH') || exit;

/**
 * The custom table backing the Contact Form block's submissions. No models
 * or ORM here on purpose — this is a single, simple write-mostly table, so
 * $wpdb directly (in ContactFormHandler) is enough.
 */
class ContactSubmissionsTable
{
    public static function tableName(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'site_blocks_contact_submissions';
    }

    /**
     * Run on plugin activation (see register_activation_hook() in
     * site-blocks.php). dbDelta() is idempotent — safe to call again on a
     * future version bump if the schema ever changes.
     */
    public static function install(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table = self::tableName();
        $charsetCollate = $wpdb->get_charset_collate();

        // dbDelta is picky about formatting: each column on its own line,
        // two spaces before the PRIMARY KEY parenthesis, no backticks.
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            email VARCHAR(191) NOT NULL,
            message TEXT NOT NULL,
            submitted_at DATETIME NOT NULL,
            PRIMARY KEY  (id)
        ) {$charsetCollate};";

        dbDelta($sql);
    }
}
