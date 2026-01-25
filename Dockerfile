# Dockerfile Unificado - Laravel con MySQL
FROM php:8.2-cli

# Variables de entorno
ENV DEBIAN_FRONTEND=noninteractive
ENV PORT=8000

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP para MySQL
RUN docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Node.js 18.x desde NodeSource
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos del proyecto
COPY . .

# Instalar dependencias PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Regenerar autoloader para asegurar que todas las clases estén disponibles
RUN composer dump-autoload --optimize --no-interaction || true

# Instalar dependencias Node (si existe package.json)
RUN if [ -f "package.json" ]; then npm install; fi

# Compilar assets (si existe package.json)
RUN if [ -f "package.json" ]; then npm run build; fi

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Copiar y dar permisos al script de inicio
RUN chmod +x /var/www/html/start.sh || true

# Limpiar cachés y optimizaciones (ejecutado durante el build)
RUN php artisan optimize:clear || true
RUN php artisan config:clear || true

# Regenerar autoloader una vez más después de limpiar cachés
RUN composer dump-autoload --optimize --no-interaction || true

# Verificar que Controller extiende correctamente (para debugging)
RUN php -r "require 'vendor/autoload.php'; \$reflection = new ReflectionClass('App\\Http\\Controllers\\Controller'); echo 'Controller extends: ' . \$reflection->getParentClass()->getName() . PHP_EOL;" || true

# Exponer puerto (Render/Railway usan $PORT)
EXPOSE ${PORT:-8000}

# Comando para iniciar Laravel usando el script mejorado
# Nota: migrate --force se ejecuta en start.sh porque necesita la base de datos en runtime
CMD ["/var/www/html/start.sh"]
