#!/usr/bin/env bash
# Activates WooCommerce and configures it from .env placeholders via WP-CLI,
# without touching the browser-based setup wizard. Repeatable/idempotent.
#
# Run inside the app container as www-data (not root), e.g.:
#   docker compose exec -u www-data app bash bin/woocommerce-setup.sh
set -euo pipefail

cd "$(dirname "$0")/.."

wp plugin activate woocommerce yith-woocommerce-wishlist shop-account redis-cache wp-super-cache ewww-image-optimizer
wp eval-file bin/woocommerce-configure.php
wp eval-file bin/shop-filters-configure.php
wp eval-file bin/shop-reviews-configure.php
wp eval-file bin/shop-cache-configure.php
wp rewrite flush
