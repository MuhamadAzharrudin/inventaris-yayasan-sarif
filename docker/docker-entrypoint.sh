#!/bin/sh

set -e

echo "Starting Laravel..."

cd /var/www/html

mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

php artisan config:clear

echo "Starting Supervisor..."

exec /usr/bin/supervisord -c /etc/supervisord.conf