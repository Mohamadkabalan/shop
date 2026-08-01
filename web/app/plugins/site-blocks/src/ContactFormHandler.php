<?php

namespace App\SiteBlocks;

defined('ABSPATH') || exit;

/**
 * Handles POSTs from the Contact Form block via admin-post.php. Registered
 * both with and without the "nopriv" suffix since this form is public —
 * logged-out visitors must be able to submit it.
 *
 * Deliberately minimal per the task: no email notifications, no spam
 * protection — verify nonce, sanitize/validate, insert, redirect back with
 * a status the block's render.php turns into a message.
 */
class ContactFormHandler
{
    private const ACTION = 'site_blocks_contact_form';

    private const NONCE_FIELD = 'site_blocks_contact_nonce';

    public function boot(): void
    {
        add_action('admin_post_' . self::ACTION, [$this, 'handle']);
        add_action('admin_post_nopriv_' . self::ACTION, [$this, 'handle']);
    }

    public function handle(): void
    {
        $redirectTo = isset($_POST['redirect_to'])
            ? wp_validate_redirect(wp_unslash($_POST['redirect_to']), home_url('/'))
            : home_url('/');

        $nonce = isset($_POST[self::NONCE_FIELD]) ? wp_unslash($_POST[self::NONCE_FIELD]) : '';

        if (! wp_verify_nonce($nonce, self::ACTION)) {
            $this->redirectWithError($redirectTo, 'invalid_request');
        }

        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';

        if ('' === $name || '' === $email || '' === $message) {
            $this->redirectWithError($redirectTo, 'missing_fields');
        }

        if (! is_email($email)) {
            $this->redirectWithError($redirectTo, 'invalid_email');
        }

        global $wpdb;

        $wpdb->insert(
            ContactSubmissionsTable::tableName(),
            [
                'name' => $name,
                'email' => $email,
                'message' => $message,
                'submitted_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s'],
        );

        wp_safe_redirect(add_query_arg('site_blocks_contact', 'success', $redirectTo));
        exit;
    }

    private function redirectWithError(string $redirectTo, string $code): never
    {
        wp_safe_redirect(add_query_arg([
            'site_blocks_contact' => 'error',
            'site_blocks_contact_error' => $code,
        ], $redirectTo));
        exit;
    }
}
