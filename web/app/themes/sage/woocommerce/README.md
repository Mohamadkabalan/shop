# WooCommerce template overrides

This folder is picked up automatically by WooCommerce's `wc_locate_template()` —
any file here takes priority over the plugin's own copy in
`web/app/plugins/woocommerce/templates/`, regardless of the fact that this is
a Sage theme (WooCommerce's template loader looks for plain PHP files at the
theme root, independent of Sage's Blade view resolution).

## What's here now

Three commonly-customized starting points, copied verbatim from the installed
WooCommerce version so their `@version` header matches exactly:

- `archive-product.php` — shop / product archive wrapper
- `single-product.php` — single product page wrapper
- `content-product.php` — the product card used in loops/archives

## Adding more overrides

Copy the file you need from
`web/app/plugins/woocommerce/templates/` into this folder, preserving the
same relative path, e.g.:

```
web/app/plugins/woocommerce/templates/cart/cart.php
  -> web/app/themes/sage/woocommerce/cart/cart.php

web/app/plugins/woocommerce/templates/single-product/add-to-cart/simple.php
  -> web/app/themes/sage/woocommerce/single-product/add-to-cart/simple.php
```

Common subfolders you'll likely need eventually: `cart/`, `checkout/`,
`myaccount/`, `single-product/`, `order/`, `emails/`, `global/`.

## Staying in sync with WooCommerce updates

Each template ships an `@version` header. When WooCommerce updates a template
you've overridden, **WooCommerce > Status > Templates** in wp-admin will flag
your copy as outdated — that's the plugin telling you to diff the new version
against your override, not an error. Don't blanket re-copy; review the diff
first so you don't lose your customizations.
