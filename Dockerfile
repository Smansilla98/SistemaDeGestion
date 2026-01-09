# Dockerfile Ultra Minimal - Solo lo esencial
FROM php:8.2-cli

ENV DEBIAN_FRONTEND=noninteractive

# Actualizar repositorios
RUN apt-get update || true

# Instalar paquetes básicos uno por uno
RUN apt-get install -y git || true
RUN apt-get install -y curl || true
RUN apt-get install -y libzip-dev || true
RUN apt-get install -y zip || true
RUN apt-get install -y unzip || true

# Instalar extensiones PHP (solo las esenciales)
RUN docker-php-ext-install zip || true
RUN docker-php-ext-install pdo_mysql || true
RUN docker-php-ext-install pdo_pgsql || true

# Limpiar
RUN apt-get clean || true
RUN rm -rf /var/lib/apt/lists/* || true

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Node.js desde NodeSource (en pasos separados)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - || true
RUN apt-get update || true
RUN apt-get install -y nodejs || true
RUN apt-get clean || true
RUN rm -rf /var/lib/apt/lists/* || true

WORKDIR /var/www/html

COPY . .

# Instalar dependencias PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction || true

# Node (opcional, no falla si no existe)
RUN if [ -f "package.json" ]; then npm install || true; fi
RUN if [ -f "package.json" ]; then npm run build || true; fi

# Permisos básicos
RUN chmod -R 775 storage bootstrap/cache || true

EXPOSE ${PORT:-8000}

CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
