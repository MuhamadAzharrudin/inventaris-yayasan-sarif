#!/bin/sh
set -e

mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/app/public

# Link storage
php artisan storage:link --force 2>/dev/null || true

# Run database migrations if RUN_MIGRATIONS is set to true
if [ "" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || true
fi

# Optimize application
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Execute supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf