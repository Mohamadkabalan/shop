#!/usr/bin/env bash
# Activates WooCommerce and configures it from .env placeholders via WP-CLI,
# without touching the browser-based setup wizard. Repeatable/idempotent.
#
# Run inside the app container as www-data (not root), e.g.:
#   docker compose exec -u www-data app bash bin/woocommerce-setup.sh
set -euo pipefail

cd "$(dirname "$0")/.."

wp plugin activate woocommerce yith-woocommerce-wishlist shop-account
wp eval-file bin/woocommerce-configure.php
wp rewrite flush
