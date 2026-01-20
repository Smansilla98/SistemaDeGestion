#!/bin/bash
set -e

echo "=== Limpiando configuración ==="
php artisan config:clear || true

echo "=== Ejecutando migraciones ==="
php artisan migrate --force || echo "Migrations failed, continuing..."

echo "=== Ejecutando seeders ==="
php artisan db:seed --force || echo "Seeding failed, continuing..."

echo "=== Iniciando servidor en 0.0.0.0:$PORT ==="
exec php artisan serve --host=0.0.0.0 --port=$PORT