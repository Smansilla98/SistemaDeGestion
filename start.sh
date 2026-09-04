#!/bin/sh
set -e

echo "=========================================="
echo "=== Iniciando Sistema de Restaurante ==="
echo "=========================================="

# Mostrar variables básicas (sin secretos)
echo "=== Variables de Entorno ==="
echo "APP_ENV: ${APP_ENV:-no configurado}"
echo "DB_CONNECTION: ${DB_CONNECTION:-no configurado}"
echo "DB_HOST: ${DB_HOST:-no configurado}"
echo "DB_DATABASE: ${DB_DATABASE:-no configurado}"
echo "DB_USERNAME: ${DB_USERNAME:-no configurado}"
echo "QUEUE_CONNECTION: ${QUEUE_CONNECTION:-database}"
echo "PORT: ${PORT:-8000}"
echo ""

# Esperar DB
echo "=== Esperando base de datos ==="
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
        break
    fi
    echo "Intento $i/30..."
    sleep 2
done

# Autoload fresco (Domain/, Jobs/, Support/)
echo "=== Composer dump-autoload ==="
composer dump-autoload --no-interaction --optimize || true

# Limpieza de cachés
echo "=== Limpiando cachés ==="
php artisan optimize:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan config:clear || true
php artisan queue:restart 2>/dev/null || true

# Migraciones (secuencias, unique por tenant, audit, índices, etc.)
echo "=== Ejecutando migraciones ==="
php artisan migrate --force --no-interaction || {
    echo "⚠️  ADVERTENCIA: Las migraciones fallaron. Verificá los logs."
    echo "   El sistema puede funcionar con funcionalidad limitada."
}

# Reparaciones Conurbania (secuencias, sesiones duplicadas, snapshots)
echo "=== Reparación de integridad Conurbania ==="
php artisan conurbania:repair --force 2>/dev/null || {
    echo "⚠️  conurbania:repair no disponible o falló (se continúa)."
}

# Enum table_sessions legacy
echo "=== Verificando enum de table_sessions ==="
php artisan fix:table-sessions-enum 2>/dev/null || true

# Storage
echo "=== Verificando storage ==="
php artisan storage:link || true

# Tablas de cola si usan database driver
if [ "${QUEUE_CONNECTION:-database}" = "database" ]; then
    echo "=== Verificando tablas de cola ==="
    php artisan queue:table 2>/dev/null || true
    php artisan queue:failed-table 2>/dev/null || true
    php artisan migrate --force --no-interaction 2>/dev/null || true
fi

# Producción: cache
if [ "${APP_ENV:-local}" = "production" ]; then
    echo "=== Cache de producción ==="
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
    php artisan event:cache 2>/dev/null || true
fi

# Worker de impresión + default (jobs PrintKitchenTicket)
echo "=== Iniciando queue worker (printing,default) ==="
php artisan queue:work --queue=printing,default --sleep=1 --tries=5 --timeout=90 --max-time=3600 &
QUEUE_PID=$!
echo "✓ Queue worker PID=$QUEUE_PID"

cleanup() {
    echo ""
    echo "=== Deteniendo queue worker ($QUEUE_PID) ==="
    kill "$QUEUE_PID" 2>/dev/null || true
    wait "$QUEUE_PID" 2>/dev/null || true
}
trap cleanup EXIT INT TERM

echo ""
echo "=========================================="
echo "=== Servidor iniciado ==="
echo "Host: 0.0.0.0"
echo "Port: ${PORT:-8000}"
echo "Queues: printing,default"
echo "=========================================="
echo ""

php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
