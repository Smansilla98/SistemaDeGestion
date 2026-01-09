# Dockerfile para Laravel en Render
FROM php:8.2-cli

# Variables de entorno para evitar prompts
ENV DEBIAN_FRONTEND=noninteractive

# Actualizar lista de paquetes
RUN apt-get update

# Instalar dependencias del sistema (paso por paso)
RUN apt-get install -y --no-install-recommends \
    git \
    curl \
    ca-certificates

# Instalar dependencias para extensiones PHP
RUN apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip

# Instalar extensiones PHP (una por una para mejor debugging)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql \
    && docker-php-ext-install -j$(nproc) pdo_pgsql \
    && docker-php-ext-install -j$(nproc) mbstring \
    && docker-php-ext-install -j$(nproc) exif \
    && docker-php-ext-install -j$(nproc) pcntl \
    && docker-php-ext-install -j$(nproc) bcmath \
    && docker-php-ext-install -j$(nproc) zip \
    && docker-php-ext-install -j$(nproc) gd

# Limpiar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Node.js 18.x desde NodeSource
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos del proyecto
COPY . .

# Instalar dependencias PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction || true

# Instalar dependencias Node (si package.json existe)
RUN if [ -f "package.json" ]; then npm install || true; fi

# Compilar assets (si package.json existe)
RUN if [ -f "package.json" ]; then npm run build || true; fi

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache || true

# Exponer puerto (Render usa $PORT)
EXPOSE ${PORT:-8000}

# Comando para iniciar Laravel
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
