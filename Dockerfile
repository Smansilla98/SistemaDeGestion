FROM php:8.2-cli

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpq-dev curl \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dockerfile
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN php artisan config:cache
RUN php artisan route:cache


# Permisos
RUN mkdir -p storage bootstrap/cache && chmod -R 775 storage bootstrap/cache

# CMD
CMD php artisan config:clear && \
    php artisan cache:clear && \
    php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=8080
