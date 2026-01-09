# Dockerfile para Laravel en Render
FROM php:8.2-cli

ENV DEBIAN_FRONTEND=noninteractive

# Actualizar repositorios
RUN apt-get update

# Instalar paquetes básicos necesarios
RUN apt-get install -y --no-install-recommends \
    git \
    curl \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP esenciales (necesarias para Composer)
RUN docker-php-ext-install zip pdo_mysql pdo_pgsql

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Node.js desde NodeSource
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copiar solo composer.json y composer.lock primero (para cache de Docker)
COPY composer.json composer.lock* ./

# Instalar dependencias PHP (con manejo de errores)
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs || \
    composer install --no-dev --optimize-autoloader --no-interaction || \
    composer install --no-interaction || true

# Copiar el resto de los archivos
COPY . .

# Instalar dependencias Node (si package.json existe)
RUN if [ -f "package.json" ]; then npm install || true; fi

# Compilar assets (si package.json existe)
RUN if [ -f "package.json" ]; then npm run build || true; fi

# Permisos básicos
RUN chmod -R 775 storage bootstrap/cache || true

EXPOSE ${PORT:-8000}

CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
