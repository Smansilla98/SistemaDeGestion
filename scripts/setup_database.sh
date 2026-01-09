#!/bin/bash

# Script para configurar la base de datos del Sistema de Gestión de Restaurante
# Uso: ./scripts/setup_database.sh

echo "🔧 Configurando base de datos..."

# Verificar si mysql está disponible
if ! command -v mysql &> /dev/null; then
    echo "⚠️  MySQL client no está instalado."
    echo "   Por favor, crea la base de datos manualmente con:"
    echo "   mysql -u user -ppassword -P 3308"
    echo "   CREATE DATABASE restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    exit 1
fi

# Crear la base de datos
echo "📦 Creando base de datos..."
mysql -u user -ppassword -P 3308 < scripts/create_database.sql

if [ $? -eq 0 ]; then
    echo "✅ Base de datos creada correctamente"
else
    echo "❌ Error al crear la base de datos"
    exit 1
fi

