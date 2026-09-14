#!/bin/sh
set -e

echo "=========================================="
echo "=== Iniciando Sistema de Restaurante ==="
echo "=========================================="

# --- Defaults limpios (API JWT + mobile) ---
export PHP_MEMORY_LIMIT="${PHP_MEMORY_LIMIT:-256M}"
export JWT_TTL="${JWT_TTL:-3600}"
export JWT_REFRESH_TTL="${JWT_REFRESH_TTL:-2592000}"
export QUEUE_CONNECTION="${QUEUE_CONNECTION:-database}"

# Compat: si alguien puso JWT_KEY en Railway, usarlo como JWT_SECRET
if [ -z "${JWT_SECRET:-}" ] && [ -n "${JWT_KEY:-}" ]; then
    export JWT_SECRET="$JWT_KEY"
    echo "⚠️  JWT_KEY detectado → exportado como JWT_SECRET (renombrá la var en Railway)."
fi

# Issuer alineado al host público
if [ -z "${JWT_ISSUER:-}" ] && [ -n "${APP_URL:-}" ]; then
    export JWT_ISSUER="$APP_URL"
fi

# URL pública de la API (sin barra final)
API_PUBLIC_BASE="${APP_URL:-http://127.0.0.1:${PORT:-8000}}"
API_PUBLIC_BASE=$(printf '%s' "$API_PUBLIC_BASE" | sed 's:/*$::')
API_PUBLIC_URL="${API_PUBLIC_BASE}/api"

# Mostrar variables básicas (sin secretos)
echo "=== Variables de Entorno ==="
echo "APP_ENV: ${APP_ENV:-no configurado}"
echo "APP_URL: ${APP_URL:-no configurado}"
echo "DB_CONNECTION: ${DB_CONNECTION:-no configurado}"
echo "DB_HOST: ${DB_HOST:-no configurado}"
echo "DB_DATABASE: ${DB_DATABASE:-no configurado}"
echo "DB_USERNAME: ${DB_USERNAME:-no configurado}"
echo "QUEUE_CONNECTION: ${QUEUE_CONNECTION}"
echo "PORT: ${PORT:-8000}"
echo "PHP_MEMORY_LIMIT: ${PHP_MEMORY_LIMIT}"
echo "JWT_TTL: ${JWT_TTL}"
echo "JWT_REFRESH_TTL: ${JWT_REFRESH_TTL}"
echo "JWT_ISSUER: ${JWT_ISSUER:-derivado de APP_URL}"
echo "API_PUBLIC_URL: ${API_PUBLIC_URL}"
if [ -z "${JWT_SECRET:-}" ]; then
    echo "JWT_SECRET: (vacío — se usará APP_KEY; definí JWT_SECRET en producción)"
else
    echo "JWT_SECRET: configurado"
fi
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

# Autoload fresco (Domain/, Jobs/, Support/, Controllers/Api)
echo "=== Composer dump-autoload ==="
composer dump-autoload --no-interaction --optimize || true

# Limpieza de cachés (arranque limpio)
echo "=== Limpiando cachés ==="
php artisan optimize:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan config:clear || true
php artisan event:clear 2>/dev/null || true
php artisan queue:restart 2>/dev/null || true

# Migraciones (secuencias, centavos, refresh_tokens, device_tokens, etc.)
echo "=== Ejecutando migraciones ==="
php artisan migrate --force --no-interaction || {
    echo "⚠️  ADVERTENCIA: Las migraciones fallaron. Verificá los logs."
    echo "   El sistema puede funcionar con funcionalidad limitada."
}

# Esquema crítico de cobro (enum de payments + audit_logs legacy)
echo "=== Verificando esquema de cobro (payments/audit_logs) ==="
php artisan checkout:verify-schema --fix 2>/dev/null || {
    echo "⚠️  checkout:verify-schema no disponible o falló (se continúa)."
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
if [ "${QUEUE_CONNECTION}" = "database" ]; then
    echo "=== Verificando tablas de cola ==="
    php artisan queue:table 2>/dev/null || true
    php artisan queue:failed-table 2>/dev/null || true
    php artisan migrate --force --no-interaction 2>/dev/null || true
fi

# Preparar cliente Expo (mobile/.env) sin secretos de servidor
echo "=== Cliente móvil (Expo) ==="
if [ -d "mobile" ]; then
    MOBILE_ENV="mobile/.env"
    if [ ! -f "$MOBILE_ENV" ]; then
        if [ -f "mobile/.env.example" ]; then
            cp mobile/.env.example "$MOBILE_ENV"
            echo "✓ Creado mobile/.env desde .env.example"
        else
            touch "$MOBILE_ENV"
            echo "✓ Creado mobile/.env vacío"
        fi
    fi

    # Asegurar EXPO_PUBLIC_API_URL = APP_URL/api (idempotente)
    if grep -q '^EXPO_PUBLIC_API_URL=' "$MOBILE_ENV" 2>/dev/null; then
        # portable sed in-place
        TMP_ENV=$(mktemp)
        sed "s|^EXPO_PUBLIC_API_URL=.*|EXPO_PUBLIC_API_URL=${API_PUBLIC_URL}|" "$MOBILE_ENV" > "$TMP_ENV"
        mv "$TMP_ENV" "$MOBILE_ENV"
    else
        echo "EXPO_PUBLIC_API_URL=${API_PUBLIC_URL}" >> "$MOBILE_ENV"
    fi

    if ! grep -q '^APP_ENV=' "$MOBILE_ENV" 2>/dev/null; then
        echo "APP_ENV=${APP_ENV:-production}" >> "$MOBILE_ENV"
    fi

    echo "✓ mobile/.env → EXPO_PUBLIC_API_URL=${API_PUBLIC_URL}"
    echo "  Arranque local de la app: cd mobile && npm ci && npx expo start -c"
else
    echo "ℹ️  Carpeta mobile/ no presente en esta imagen (OK en algunos deploys)."
fi

# Smoke mínimo de rutas JWT (no falla el boot)
echo "=== Smoke API JWT ==="
php artisan route:list --path=api/auth 2>/dev/null | head -20 || true

# Producción: cache
if [ "${APP_ENV:-local}" = "production" ]; then
    echo "=== Cache de producción ==="
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
    php artisan event:cache 2>/dev/null || true
fi

# Worker: impresión cocina + default (Expo push SendExpoPushNotification, etc.)
echo "=== Iniciando queue worker (printing,default) ==="
php -d memory_limit="${PHP_MEMORY_LIMIT}" artisan queue:work \
    --queue=printing,default \
    --sleep=1 \
    --tries=5 \
    --timeout=90 \
    --max-time=3600 &
QUEUE_PID=$!
echo "✓ Queue worker PID=$QUEUE_PID"

# Expo opcional (solo local / si START_EXPO=1 y hay node). Nunca en Railway por defecto.
EXPO_PID=""
if [ "${START_EXPO:-0}" = "1" ] && [ -d "mobile" ] && command -v npm >/dev/null 2>&1; then
    echo "=== Iniciando Expo (START_EXPO=1) ==="
    (
        cd mobile
        if [ ! -d node_modules ]; then
            npm ci --legacy-peer-deps || npm install --legacy-peer-deps
        fi
        npx expo start --clear
    ) &
    EXPO_PID=$!
    echo "✓ Expo PID=$EXPO_PID"
fi

cleanup() {
    echo ""
    echo "=== Deteniendo procesos ==="
    if [ -n "${QUEUE_PID:-}" ]; then
        kill "$QUEUE_PID" 2>/dev/null || true
        wait "$QUEUE_PID" 2>/dev/null || true
    fi
    if [ -n "${EXPO_PID:-}" ]; then
        kill "$EXPO_PID" 2>/dev/null || true
        wait "$EXPO_PID" 2>/dev/null || true
    fi
}
trap cleanup EXIT INT TERM

echo ""
echo "=========================================="
echo "=== Servidor iniciado ==="
echo "Host: 0.0.0.0"
echo "Port: ${PORT:-8000}"
echo "API: ${API_PUBLIC_URL}"
echo "Queues: printing,default (tickets + push Expo)"
echo "OpenAPI: /docs"
echo "Mobile: mobile/ (Expo Go) — START_EXPO=1 para levantarlo acá"
echo "=========================================="
echo ""

php -d memory_limit="${PHP_MEMORY_LIMIT}" artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
