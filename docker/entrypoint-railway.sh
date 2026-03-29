#!/bin/sh
set -e
cd /var/www/html

PORT="${PORT:-8080}"
sed "s/__PORT__/${PORT}/" /opt/railway-nginx.conf > /etc/nginx/conf.d/default.conf

if [ -f artisan ]; then
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

php-fpm -D
exec nginx -g "daemon off;"
