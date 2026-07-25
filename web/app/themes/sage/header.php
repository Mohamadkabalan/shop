<?php
/**
 * Classic-template bridge for plugins (WooCommerce) that call get_header()
 * directly instead of going through Sage's Blade template_include routing.
 * Renders the same chrome as resources/views/layouts/header.blade.php so
 * these pages share the site's header/nav/CSS instead of falling back to
 * WordPress's ancient wp-includes/theme-compat template.
 */

echo view('layouts.header')->render();
