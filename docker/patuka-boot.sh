#!/bin/sh
# Pasos de arranque de Patuka compartidos por docker/entrypoint-railway.sh y start.sh.
# Uso: . docker/patuka-boot.sh && patuka_db_env (antes de esperar la base) / patuka_first_run (después de migrar)

# Railway: si el servicio no tiene DB_*, se toman las variables del servicio MySQL
# (MYSQLHOST, MYSQLPORT, MYSQLDATABASE, MYSQLUSER, MYSQLPASSWORD).
patuka_db_env() {
    export DB_CONNECTION="${DB_CONNECTION:-mysql}"
    if [ -z "${DB_HOST:-}" ] && [ -n "${MYSQLHOST:-}" ]; then export DB_HOST="$MYSQLHOST"; fi
    if [ -z "${DB_PORT:-}" ] && [ -n "${MYSQLPORT:-}" ]; then export DB_PORT="$MYSQLPORT"; fi
    if [ -z "${DB_DATABASE:-}" ] && [ -n "${MYSQLDATABASE:-}" ]; then export DB_DATABASE="$MYSQLDATABASE"; fi
    if [ -z "${DB_USERNAME:-}" ] && [ -n "${MYSQLUSER:-}" ]; then export DB_USERNAME="$MYSQLUSER"; fi
    if [ -z "${DB_PASSWORD:-}" ] && [ -n "${MYSQLPASSWORD:-}" ]; then export DB_PASSWORD="$MYSQLPASSWORD"; fi

    # APP_URL mal pegada (espacios, comillas, sin https://) hace caer a todo artisan con "Invalid URI".
    # Se limpia; si queda vacía o inválida se usa el dominio público de Railway.
    APP_URL=$(printf '%s' "${APP_URL:-}" | tr -d " \t\r\n\"'" | sed 's:/*$::')
    if [ -n "$APP_URL" ] && ! printf '%s' "$APP_URL" | grep -Eq '^https?://'; then
        APP_URL="https://$APP_URL"
    fi
    if ! printf '%s' "$APP_URL" | grep -Eq '^https?://[A-Za-z0-9.-]+(:[0-9]+)?(/.*)?$'; then
        if [ -n "$APP_URL" ]; then echo "⚠️  APP_URL inválida (${APP_URL}): se ignora."; fi
        APP_URL=""
        if [ -n "${RAILWAY_PUBLIC_DOMAIN:-}" ]; then APP_URL="https://${RAILWAY_PUBLIC_DOMAIN}"; fi
    fi
    if [ -n "$APP_URL" ]; then export APP_URL; else unset APP_URL; fi
    echo "APP_URL: ${APP_URL:-(sin definir: http://localhost)}"

    # Sin APP_KEY se rompen sesiones y datos cifrados. No se genera sola: cambiaría en cada deploy.
    if [ -z "${APP_KEY:-}" ]; then
        echo "✗ Falta APP_KEY. Generala una vez con: php artisan key:generate --show"
        echo "  y cargala como variable del servicio en Railway."
        exit 1
    fi
}

# Primera ejecución: deja la organización Patuka configurada y crea el admin.
# Es idempotente: en los arranques siguientes no toca lo que el equipo cambió.
# PATUKA_SETUP=0 la desactiva.
patuka_first_run() {
    if [ "${PATUKA_SETUP:-1}" != "1" ]; then
        return 0
    fi
    echo "=== Patuka: primera ejecución ==="
    set -- --first-run
    if [ -n "${PATUKA_ADMIN_USER:-}" ]; then set -- "$@" "--admin-user=${PATUKA_ADMIN_USER}"; fi
    if [ -n "${PATUKA_ADMIN_PASSWORD:-}" ]; then set -- "$@" "--admin-password=${PATUKA_ADMIN_PASSWORD}"; fi
    if [ -n "${PATUKA_ADMIN_EMAIL:-}" ]; then set -- "$@" "--admin-email=${PATUKA_ADMIN_EMAIL}"; fi
    php artisan patuka:setup "$@" --no-interaction || echo "⚠️  patuka:setup falló (se continúa)."
}
