FROM php:8.2-apache

# Instalar extensiones necesarias
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Configurar Apache para el puerto dinámico
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configurar Apache para escuchar en el puerto dinámico
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf
RUN sed -i 's/:80/:${PORT}/' /etc/apache2/sites-available/000-default.conf

# Copiar composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar código
WORKDIR /var/www/html
COPY . .

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Permisos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Crear script de inicio
RUN echo '#!/bin/bash\n\
set -e\n\
echo "=== Configurando Apache en puerto $PORT ==="\n\
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf\n\
sed -i "s/:80/:$PORT/" /etc/apache2/sites-available/000-default.conf\n\
echo "=== Limpiando configuración ==="\n\
php artisan config:clear\n\
echo "=== Ejecutando migraciones ==="\n\
php artisan migrate --force\n\
echo "=== Ejecutando seeders ==="\n\
php artisan db:seed --force\n\
echo "=== Iniciando Apache en puerto $PORT ==="\n\
apache2-foreground\n\
' > /start.sh && chmod +x /start.sh

EXPOSE ${PORT:-8000}

CMD ["/start.sh"]