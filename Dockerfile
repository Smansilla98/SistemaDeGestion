FROM php:8.2-cli

ENV DEBIAN_FRONTEND=noninteractive

# Paquetes del sistema
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpq-dev \
    curl \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiamos composer primero
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copiamos el resto del proyecto
COPY . .

# Permisos
RUN mkdir -p storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

EXPOSE 8080

CMD php artisan optimize:clear && \
    php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=8080
