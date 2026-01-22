#!/bin/bash
set -e

echo "=========================================="
echo "=== Iniciando Sistema de Restaurante ==="
echo "=========================================="

# Mostrar variables de entorno de base de datos (sin mostrar password completo)
echo "=== Variables de Entorno de BD ==="
echo "DB_CONNECTION: ${DB_CONNECTION:-no configurado}"
echo "DB_HOST: ${DB_HOST:-no configurado}"
echo "DB_PORT: ${DB_PORT:-no configurado}"
echo "DB_DATABASE: ${DB_DATABASE:-no configurado}"
echo "DB_USERNAME: ${DB_USERNAME:-no configurado}"
if [ -n "$DATABASE_URL" ]; then
    echo "DATABASE_URL: configurado (oculto por seguridad)"
else
    echo "DATABASE_URL: no configurado"
fi
echo ""

# Función para esperar a que la base de datos esté disponible
wait_for_db() {
    echo "=== Esperando conexión a base de datos ==="
    max_attempts=30
    attempt=0
    
    # Obtener variables de entorno
    DB_HOST="${DB_HOST:-127.0.0.1}"
    DB_PORT="${DB_PORT:-3306}"
    DB_USERNAME="${DB_USERNAME:-root}"
    DB_PASSWORD="${DB_PASSWORD:-}"
    
    while [ $attempt -lt $max_attempts ]; do
        if php -r "
            try {
                \$host = getenv('DB_HOST') ?: '127.0.0.1';
                \$port = getenv('DB_PORT') ?: '3306';
                \$user = getenv('DB_USERNAME') ?: 'root';
                \$pass = getenv('DB_PASSWORD') ?: '';
                
                \$pdo = new PDO(
                    'mysql:host=' . \$host . ';port=' . \$port,
                    \$user,
                    \$pass,
                    [PDO::ATTR_TIMEOUT => 2]
                );
                echo 'Conexión exitosa\n';
                exit(0);
            } catch (Exception \$e) {
                exit(1);
            }
        " 2>/dev/null; then
            echo "✓ Base de datos disponible"
            return 0
        fi
        
        attempt=$((attempt + 1))
        echo "Intento $attempt/$max_attempts - Esperando 2 segundos..."
        sleep 2
    done
    
    echo "⚠️  No se pudo conectar a la base de datos después de $max_attempts intentos"
    echo "Continuando de todas formas..."
    return 1
}

# Limpiar configuración
echo "=== Limpiando configuración ==="
php artisan config:clear || true
php artisan cache:clear || true
php artisan view:clear || true

# Esperar a que la base de datos esté disponible
wait_for_db || true

# Verificar conexión antes de migrar
echo "=== Verificando conexión a base de datos ==="
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-laravel}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"

if php -r "
    try {
        \$host = getenv('DB_HOST') ?: '127.0.0.1';
        \$port = getenv('DB_PORT') ?: '3306';
        \$database = getenv('DB_DATABASE') ?: 'laravel';
        \$user = getenv('DB_USERNAME') ?: 'root';
        \$pass = getenv('DB_PASSWORD') ?: '';
        
        \$pdo = new PDO(
            'mysql:host=' . \$host . ';port=' . \$port . ';dbname=' . \$database,
            \$user,
            \$pass,
            [PDO::ATTR_TIMEOUT => 5, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo '✓ Conexión verificada\n';
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null; then
    echo "✓ Conexión verificada"
else
    echo "⚠️  No se pudo verificar la conexión, pero continuando..."
fi

# Ejecutar migraciones
#echo "=== Ejecutando migraciones ==="
#if php artisan migrate --force -vvv; then
#    echo "✓ Migraciones completadas"
#else
#    echo "⚠️  Error en migraciones, continuando..."
#fi

# Ejecutar seeders
echo "=== Ejecutando seeders ==="
if php artisan db:seed --force; then
    echo "✓ Seeders completados"
else
    echo "⚠️  Error en seeders, continuando..."
fi

# Crear enlace de storage si no existe
echo "=== Verificando enlace de storage ==="
php artisan storage:link || echo "⚠️  Enlace de storage ya existe o no se pudo crear"

# Optimizar Laravel
echo "=== Optimizando Laravel ==="
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Mostrar información del servidor
echo ""
echo "=========================================="
echo "=== Servidor iniciando ==="
echo "Host: 0.0.0.0"
echo "Port: ${PORT:-8000}"
echo "=========================================="
echo ""

# Iniciar servidor
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
