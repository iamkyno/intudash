#!/usr/bin/env bash
#
# Production deploy / optimisation script for IntuDash.
# Run after pulling new code on the server.
#
set -euo pipefail

echo "▸ Installing composer dependencies (production)…"
composer install --no-dev --optimize-autoloader --no-interaction

echo "▸ Running database migrations…"
php artisan migrate --force

echo "▸ Rebuilding caches…"
php artisan config:clear
php artisan config:cache      # compile config into one file
php artisan route:cache       # compile route definitions
php artisan view:cache        # precompile Blade templates
php artisan event:cache

echo "▸ Restarting queue workers so they pick up new code…"
php artisan queue:restart

echo "✔ Deploy complete."
echo
echo "Reminder — these must be running as long-lived processes (see deploy/):"
echo "  • Queue worker : php artisan queue:work --tries=3 --timeout=320"
echo "  • Scheduler    : * * * * * php artisan schedule:run  (cron, every minute)"
