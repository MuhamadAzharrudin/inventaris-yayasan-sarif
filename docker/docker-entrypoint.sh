#!/bin/sh

# Ensure necessary runtime directories exist
mkdir -p /run/nginx
mkdir -p /var/log/supervisor
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/app/public
mkdir -p /var/www/html/bootstrap/cache

# Fix permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage link safely
php artisan storage:link --force 2>/dev/null || true

# Run database migrations if RUN_MIGRATIONS is set
if [ "" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force 2>/dev/null || true
fi

# Cache optimization (silent fallback if DB is not reachable at build time)
php artisan config:clear 2>/dev/null || true
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

# Start supervisor (keeps container alive)
exec /usr/bin/supervisord -n -c /etc/supervisord.conf