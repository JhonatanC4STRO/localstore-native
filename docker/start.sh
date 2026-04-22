#!/bin/bash
set -e

PORT=${PORT:-80}

# Reemplazar puerto en configuración Apache
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
