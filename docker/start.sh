#!/bin/bash
set -e

# Railway inyecta $PORT dinámicamente; Apache debe escuchar en ese puerto
PORT=${PORT:-80}

sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/*:80>/*:$PORT>/" /etc/apache2/sites-available/000-default.conf

exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
