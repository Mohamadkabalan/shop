# Site Blocks

Custom Gutenberg blocks for this site, built with `@wordpress/scripts`.

## Blocks

- **Featured Products** (`site-blocks/featured-products`) — a manually curated grid of WooCommerce products (image, title, price). No add-to-cart, rating, or description — just those three. Fields (title, products, columns) are managed via ACF Pro, attached to this block through a `block` location rule (see `src/FieldGroups.php`), not through block.json attributes.

## Requirements

- WooCommerce — `render.php` calls `wc_get_product()`; the block silently renders nothing without it.
- ACF Pro — the `block` location rule (targeting a native, non-`acf_register_block_type()` block) requires ACF Pro 5.8+. Without ACF active, the block still registers and appears in the inserter, but has no editable fields and renders nothing on the frontend (`render.php` bails out via `function_exists('get_field')`).

## Building

The `app` Docker container has no Node.js — the JS/CSS build runs on the **host machine**. The compiled output lands in `build/`, which the container already sees through the existing bind mount (`.:/var/www/html` in `docker-compose.yml`) — the same way Sage's `public/build` is built on the host and picked up by the container without Node ever running inside it.

From the plugin directory, on your host machine:

```bash
cd web/app/plugins/site-blocks
npm install
npm run build      # one-off build
npm run start      # watches src/ and rebuilds on save, for active development
```

`build/` and `node_modules/` are gitignored (see this plugin's own `.gitignore`) — same as the Sage theme's `public/build`. Every environment needs to run `npm install && npm run build` here once, after `composer install`, before the block will actually appear (`BlockRegistration` checks for `build/featured-products/block.json` and no-ops if it's missing, rather than fataling).
