#!/bin/sh

set -e

echo "======================================"
echo "Starting Laravel application"
echo "======================================"

cd /var/www/html

# Ensure Laravel writable directories exist
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Set permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Clear old Laravel caches
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Build production caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Laravel initialization completed."

echo "======================================"
echo "Starting Supervisor"
echo "======================================"

exec /usr/bin/supervisord -c /etc/supervisord.conf