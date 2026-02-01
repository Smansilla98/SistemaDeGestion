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
echo ""

# Esperar DB (solo verificación, NO migraciones)
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

# Limpieza segura
echo "=== Limpiando cachés ==="
php artisan optimize:clear || true

# Verificar si la base de datos tiene tablas
echo "=== Verificando estado de la base de datos ==="
DB_HAS_TABLES=$(php -r "
try {
    \$pdo = new PDO(
        'mysql:host='.(getenv('DB_HOST') ?: '127.0.0.1').
        ';port='.(getenv('DB_PORT') ?: '3306').
        ';dbname='.(getenv('DB_DATABASE') ?: ''),
        getenv('DB_USERNAME') ?: 'root',
        getenv('DB_PASSWORD') ?: ''
    );
    \$stmt = \$pdo->query('SHOW TABLES');
    \$tables = \$stmt->fetchAll(PDO::FETCH_COLUMN);
    echo count(\$tables) > 0 ? 'yes' : 'no';
} catch (Exception \$e) {
    echo 'no';
}
" 2>/dev/null || echo "no")

if [ "$DB_HAS_TABLES" = "yes" ]; then
    echo "✓ Base de datos tiene tablas existentes"
    echo "=== Ejecutando migraciones (modo seguro) ==="
    php artisan migrate --force --no-interaction || {
        echo "⚠️  ADVERTENCIA: Las migraciones fallaron. Verificá los logs."
        echo "   El sistema puede funcionar con funcionalidad limitada."
    }
    
    # Corregir enum de table_sessions si es necesario
    echo "=== Verificando y corrigiendo enum de table_sessions ==="
    php artisan fix:table-sessions-enum || {
        echo "⚠️  ADVERTENCIA: No se pudo corregir el enum. Continuando..."
    }
else
    echo "Base de datos vacía"
    echo "=== Ejecutando migraciones iniciales ==="
    php artisan migrate --force --no-interaction || {
        echo "⚠️  ADVERTENCIA: Las migraciones fallaron. Verificá los logs."
    }
fi

# Regenerar autoloader de Composer (por si hay cambios en clases)
composer dump-autoload --no-interaction --optimize || true

# Storage
echo "=== Verificando storage ==="
php artisan storage:link || true

echo ""
echo "=========================================="
echo "=== Servidor iniciado ==="
echo "Host: 0.0.0.0"
echo "Port: ${PORT:-8000}"
echo "=========================================="
echo ""

exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
