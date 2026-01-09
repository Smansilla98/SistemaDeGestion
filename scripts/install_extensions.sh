#!/bin/bash

# Script para instalar extensiones PHP necesarias
# Ejecutar con: sudo bash scripts/install_extensions.sh

echo "🔧 Instalando extensiones PHP necesarias..."
echo ""

# Actualizar lista de paquetes
echo "📦 Actualizando lista de paquetes..."
apt-get update

# Instalar extensiones PHP
echo "📥 Instalando extensiones PHP..."
apt-get install -y \
    php8.3-mysql \
    php8.3-xml \
    php8.3-dom \
    php8.3-mbstring \
    php8.3-curl \
    php8.3-zip \
    php8.3-gd \
    php8.3-bcmath

echo ""
echo "✅ Instalación completada!"
echo ""
echo "🔍 Verificando extensiones instaladas..."
php -m | grep -E "pdo_mysql|xml|dom|mbstring|curl|zip|gd|bcmath"

echo ""
echo "📋 Extensiones instaladas:"
echo "   - pdo_mysql (MySQL)"
echo "   - xml (XML)"
echo "   - dom (DOMDocument)"
echo "   - mbstring (Multi-byte strings)"
echo "   - curl (HTTP client)"
echo "   - zip (Compresión)"
echo "   - gd (Imágenes)"
echo "   - bcmath (Matemáticas de precisión)"
echo ""
echo "✅ Listo para continuar con las migraciones!"

