#!/usr/bin/env bash
# Run on the staging server as deploy, after restoring its DB and media.
set -euo pipefail
project_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$project_dir/backend"
test -f .env || { echo 'Configure backend/.env first.' >&2; exit 1; }
php8.5 -r 'if (!extension_loaded("pdo_mysql")) { fwrite(STDERR, "pdo_mysql is required\n"); exit(1); }'
if [ "${1:-}" != '--confirm-staging' ]; then
    echo 'Back up the staging database and media, then run with --confirm-staging.' >&2
    exit 1
fi
php8.5 -r 'require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); if ($app->environment() !== "staging" || config("app.debug") || config("database.default") !== "mariadb" || !config("app.key") || !str_starts_with(config("app.url"), "https://")) { fwrite(STDERR, "Require staging, debug off, MariaDB, retained APP_KEY and HTTPS APP_URL.\n"); exit(1); }'
php8.5 artisan down --retry=60
echo 'If a command fails, maintenance mode stays enabled. Resolve it before artisan up.'
php8.5 artisan config:clear
php8.5 artisan deployment:prepare --no-interaction
php8.5 artisan storage:link --no-interaction
php8.5 artisan optimize --no-interaction
echo 'Restart PHP-FPM and both systemd services, then run php8.5 artisan up and smoke checks.'
