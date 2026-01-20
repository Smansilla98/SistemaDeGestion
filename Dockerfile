FROM php:8.2-cli

WORKDIR /var/www/html

# Copiar composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instalar dependencias y extensión PostgreSQL
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpq-dev curl \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

# Copiar código
COPY . .

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Permisos
RUN chmod -R 775 storage bootstrap/cache

# El comando de inicio lo maneja Railway con el Start Command
# No pongas CMD aquí