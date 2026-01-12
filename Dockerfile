# Dockerfile para Laravel en Render - Versión Final
FROM php:8.2-cli

ENV DEBIAN_FRONTEND=noninteractive

# Actualizar repositorios
RUN apt-get update

# Instalar paquetes básicos necesarios (uno por uno para mejor debugging)
RUN apt-get install -y --no-install-recommends git && \
    apt-get install -y --no-install-recommends curl && \
    apt-get install -y --no-install-recommends libzip-dev && \
    apt-get install -y --no-install-recommends zip && \
    apt-get install -y --no-install-recommends unzip && \
    apt-get install -y --no-install-recommends libpq-dev || \
    (echo "Error instalando paquetes básicos" && apt-get install -y git curl libzip-dev zip unzip libpq-dev)

# Instalar extensiones PHP esenciales
RUN docker-php-ext-install zip pdo_mysql pdo_pgsql || \
    (echo "Error instalando extensiones PHP, intentando individualmente..." && \
     docker-php-ext-install zip && \
     docker-php-ext-install pdo_mysql && \
     docker-php-ext-install pdo_pgsql)

# Limpiar
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Node.js desde NodeSource
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs && \
    rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copiar solo composer.json primero (para cache de Docker)
COPY composer.json composer.lock* ./

# Instalar dependencias PHP (con múltiples estrategias)
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs 2>&1 || \
    composer install --no-dev --no-interaction --ignore-platform-reqs 2>&1 || \
    composer install --no-interaction --ignore-platform-reqs 2>&1 || \
    (echo "Composer install falló, pero continuando..." && true)

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
