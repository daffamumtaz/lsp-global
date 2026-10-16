#!/bin/sh
set -eu
cd /app

if [ "${1:-}" != 'apache2-foreground' ]; then
    exec "$@"
fi

: "${APP_KEY:?APP_KEY must be configured and retained across deployments}"
: "${APP_URL:?APP_URL must be configured}"
: "${DB_CONNECTION:?DB_CONNECTION must be configured}"
: "${PORT:=10000}"
case "$PORT" in
    ''|*[!0-9]*) echo 'PORT must be numeric' >&2; exit 1 ;;
esac
if [ "$PORT" -lt 1 ] || [ "$PORT" -gt 65535 ]; then
    echo 'PORT must be between 1 and 65535' >&2
    exit 1
fi
if [ "${PUBLIC_FILESYSTEM_DRIVER:-local}" = 'supabase' ]; then
    : "${AWS_ACCESS_KEY_ID:?Configure Supabase S3 access key}"
    : "${AWS_SECRET_ACCESS_KEY:?Configure Supabase S3 secret key}"
    : "${AWS_DEFAULT_REGION:?Configure Supabase region}"
    : "${AWS_BUCKET:?Configure a public media bucket}"
    : "${AWS_ENDPOINT:?Configure Supabase S3 endpoint}"
    : "${AWS_URL:?Configure public bucket URL}"
fi

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/private storage/app/public
chown -R www-data:www-data storage bootstrap/cache
sed -i "s/^Listen .*/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:.*>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf
php artisan config:cache --no-interaction
if [ "${RUN_MIGRATIONS:-false}" = 'true' ]; then
    php artisan deployment:prepare --no-interaction
fi
php artisan route:cache --no-interaction
php artisan event:cache --no-interaction
php artisan view:cache --no-interaction
if [ "${PUBLIC_FILESYSTEM_DRIVER:-local}" = 'local' ]; then
    php artisan storage:link --no-interaction
fi
chown -R www-data:www-data storage bootstrap/cache
exec docker-php-entrypoint "$@"
