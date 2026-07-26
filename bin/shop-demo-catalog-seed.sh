#!/usr/bin/env bash
# OPTIONAL dev/demo helper — populates example categories, attribute terms,
# and products so the Shop Filters sidebar has real data to filter. NOT part
# of the reproducible production setup: bin/woocommerce-setup.sh never calls
# this, and it should not be run against a production store.
#
# Run inside the app container as www-data (not root), after woocommerce-setup.sh:
#   docker compose exec -u www-data app bash bin/shop-demo-catalog-seed.sh
set -euo pipefail

cd "$(dirname "$0")/.."

wp eval-file bin/shop-demo-catalog-seed.php
