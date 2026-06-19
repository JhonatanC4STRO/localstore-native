#!/bin/bash
set -e

mkdir -p /var/www/html/public/uploads/products
if [ ! -f /var/www/html/public/uploads/.seeded ]; then
    cp -rn /opt/uploads-seed/. /var/www/html/public/uploads/ 2>/dev/null || true
    touch /var/www/html/public/uploads/.seeded
fi
chmod -R 777 /var/www/html/public/uploads

PORT=${PORT:-80}
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
