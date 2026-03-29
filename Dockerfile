# Etapa 1: Vite → public/build/manifest.json (requerido por @vite en Blade en producción).
FROM node:22-alpine AS vite
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# Laravel en Railway: nginx escucha $PORT y hace proxy a PHP-FPM (un solo contenedor).
# PHP 8.3: lock (zipstream-php) exige ^8.3; gd: PhpSpreadsheet / maatwebsite/excel.
FROM php:8.3-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip nginx \
    libzip-dev libonig-dev \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql zip opcache mbstring bcmath gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .
COPY --from=vite /app/public/build ./public/build

RUN rm -f /etc/nginx/sites-enabled/default \
    && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts \
    && chown -R www-data:www-data storage bootstrap/cache public/build \
    && chmod -R ug+rwx storage bootstrap/cache public/build

COPY docker/nginx/railway.default.conf /opt/railway-nginx.conf
COPY docker/entrypoint-railway.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
