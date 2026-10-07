#!/bin/sh
set -e

PORT="${PORT:-10000}"
export PORT
export RUN_QUEUE_WORKER="${RUN_QUEUE_WORKER:-true}"

echo "==> Configuring Nginx port to ${PORT}..."
sed -i "s/__PORT__/${PORT}/g" /etc/nginx/nginx.conf

cd /var/www/html

echo "==> Ensuring storage directory structure..."
mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Create storage symbolic link
php artisan storage:link || true

# Execute database migrations if requested
if [ "${RUN_MIGRATIONS}" = "true" ]; then
    echo "==> Running database migrations..."
    php artisan migrate --force || {
        echo "WARNING: Migrations failed. Continuing startup..."
    }
fi

# Execute database seeders if requested
if [ "${RUN_SEEDER}" = "true" ]; then
    echo "==> Running database seeders..."
    php artisan db:seed --force || {
        echo "WARNING: Seeders failed. Continuing startup..."
    }
fi

# Optimize Laravel cache in production
if [ "${APP_ENV}" = "production" ]; then
    echo "==> Caching Laravel configuration, routes, and views..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

echo "==> Starting Supervisord (Nginx + PHP-FPM + Queue Worker)..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
