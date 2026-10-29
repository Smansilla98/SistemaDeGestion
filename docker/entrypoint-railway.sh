#!/bin/sh
set -e
cd /var/www/html

PORT="${PORT:-8080}"
sed "s/__PORT__/${PORT}/" /opt/railway-nginx.conf > /etc/nginx/conf.d/default.conf

if [ -f artisan ]; then
    . /var/www/html/docker/patuka-boot.sh
    patuka_db_env

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

    patuka_first_run

    php artisan storage:link 2>/dev/null || true
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true

    # Lo que artisan creó como root (logs, cachés) tiene que poder escribirlo php-fpm.
    chown -R www-data:www-data storage bootstrap/cache

    # Cola: avisos push, notificaciones y tickets. Scheduler: recordatorios y sincronización de formularios.
    # Con más de una réplica, dejar el scheduler en una sola (SCHEDULER_ENABLED=0 en las demás).
    if [ "${QUEUE_WORKER_ENABLED:-1}" = "1" ]; then
        echo "=== Iniciando queue worker ==="
        su www-data -s /bin/sh -c "while true; do php artisan queue:work --queue=printing,default --sleep=1 --tries=5 --timeout=90 --max-time=3600; sleep 1; done" &
    fi
    if [ "${SCHEDULER_ENABLED:-1}" = "1" ]; then
        echo "=== Iniciando scheduler ==="
        su www-data -s /bin/sh -c "php artisan schedule:work" > /dev/null 2>&1 &
    fi
fi

php-fpm -D
exec nginx -g "daemon off;"
