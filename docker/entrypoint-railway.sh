#!/bin/sh
set -e
cd /var/www/html

PORT="${PORT:-8080}"
sed "s/__PORT__/${PORT}/" /opt/railway-nginx.conf > /etc/nginx/conf.d/default.conf

if [ -f artisan ]; then
    echo "=== Esperando base de datos ==="
    DB_READY=0
    for i in $(seq 1 30); do
        if php -r "
            try {
                new PDO(
                    'mysql:host='.(getenv('DB_HOST') ?: '127.0.0.1').
                    ';port='.(getenv('DB_PORT') ?: '3306').
                    ';dbname='.(getenv('DB_DATABASE') ?: ''),
                    getenv('DB_USERNAME') ?: 'root',
                    getenv('DB_PASSWORD') ?: '',
                    [PDO::ATTR_TIMEOUT => 2]
                );
                exit(0);
            } catch (Exception \$e) {
                exit(1);
            }
        " 2>/dev/null; then
            echo "✓ Base de datos disponible"
            DB_READY=1
            break
        fi
        echo "Intento $i/30..."
        sleep 2
    done

    if [ "$DB_READY" -ne 1 ]; then
        echo "✗ La base de datos no estuvo disponible después de 30 intentos."
        exit 1
    fi

    echo "=== Ejecutando migraciones ==="
    php artisan migrate --force --no-interaction

    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

php-fpm -D
exec nginx -g "daemon off;"
