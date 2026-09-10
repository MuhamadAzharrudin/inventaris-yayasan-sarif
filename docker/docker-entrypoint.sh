#!/bin/sh

set -e

echo "===================================="
echo "Starting PHP-FPM + Nginx"
echo "===================================="

mkdir -p /run/nginx
mkdir -p /var/log/supervisor

chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache

chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

exec /usr/bin/supervisord -c /etc/supervisord.conf