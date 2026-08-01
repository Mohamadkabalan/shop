<?php

/**
 * Renders the Contact Form block on the frontend: the form itself, plus a
 * success/error notice read from the query string set by
 * ContactFormHandler's post-submit redirect.
 *
 * Included directly by core (block.json's "render" file convention), so
 * this runs in global scope with $attributes/$content/$block already set.
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string $content Block's saved inner content (always empty — see index.js's save()).
 * @var WP_Block $block Block instance.
 */

defined('ABSPATH') || exit;

// Only used to pick which static message to show below — never echoed
// directly, so there's no injection surface from these query params.
$status = isset($_GET['site_blocks_contact']) ? sanitize_key(wp_unslash($_GET['site_blocks_contact'])) : '';
$errorCode = isset($_GET['site_blocks_contact_error']) ? sanitize_key(wp_unslash($_GET['site_blocks_contact_error'])) : '';

$errorMessages = [
    'missing_fields' => __('Please fill in all fields before submitting.', 'site-blocks'),
    'invalid_email' => __('Please enter a valid email address.', 'site-blocks'),
    'invalid_request' => __('Something went wrong — please try again.', 'site-blocks'),
];

$redirectTo = get_permalink() ?: home_url('/');
?>
<div <?php echo get_block_wrapper_attributes(['class' => 'site-blocks-contact-form']); ?>>
    <?php if ('success' === $status) : ?>
        <p class="site-blocks-contact-form__notice site-blocks-contact-form__notice--success">
            <?php esc_html_e('Thanks — your message has been sent.', 'site-blocks'); ?>
        </p>
    <?php elseif ('error' === $status) : ?>
        <p class="site-blocks-contact-form__notice site-blocks-contact-form__notice--error">
            <?php echo esc_html($errorMessages[$errorCode] ?? $errorMessages['invalid_request']); ?>
        </p>
    <?php endif; ?>

    <form class="site-blocks-contact-form__form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="site_blocks_contact_form">
        <input type="hidden" name="redirect_to" value="<?php echo esc_url($redirectTo); ?>">
        <?php wp_nonce_field('site_blocks_contact_form', 'site_blocks_contact_nonce'); ?>

        <p class="site-blocks-contact-form__field">
            <label for="site-blocks-contact-name"><?php esc_html_e('Name', 'site-blocks'); ?></label>
            <input type="text" id="site-blocks-contact-name" name="name" required>
        </p>
        <p class="site-blocks-contact-form__field">
            <label for="site-blocks-contact-email"><?php esc_html_e('Email', 'site-blocks'); ?></label>
            <input type="email" id="site-blocks-contact-email" name="email" required>
        </p>
        <p class="site-blocks-contact-form__field">
            <label for="site-blocks-contact-message"><?php esc_html_e('Message', 'site-blocks'); ?></label>
            <textarea id="site-blocks-contact-message" name="message" rows="5" required></textarea>
        </p>
        <p class="site-blocks-contact-form__submit">
            <button type="submit"><?php esc_html_e('Send message', 'site-blocks'); ?></button>
        </p>
    </form>
</div>
