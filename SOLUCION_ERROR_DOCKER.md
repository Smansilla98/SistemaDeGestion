# 🔧 Solución: Error de Build en Dockerfile

## ❌ Error Original

```
error: failed to solve: process "/bin/sh -c apt-get update && apt-get install -y ..." 
did not complete successfully: exit code: 1
```

## 🔍 Causas del Error

1. **Node.js desde apt**: La versión de Node.js en los repositorios de Debian puede ser antigua o tener conflictos
2. **Falta libzip-dev**: Necesario para la extensión `zip` de PHP
3. **php:8.2-fpm**: Puede tener problemas con algunos paquetes

## ✅ Solución Aplicada

### Cambios en el Dockerfile:

1. **Cambio de imagen base**: `php:8.2-fpm` → `php:8.2-cli`
   - Más ligero
   - Mejor para `php artisan serve`

2. **Instalación de Node.js desde NodeSource**:
   ```dockerfile
   RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
       && apt-get install -y nodejs
   ```
   - Más confiable
   - Versión actualizada (18.x)

3. **Agregado libzip-dev**:
   ```dockerfile
   libzip-dev \
   ```
   - Necesario para la extensión `zip` de PHP

4. **Manejo condicional de npm**:
   ```dockerfile
   RUN if [ -f "package.json" ]; then npm install; fi
   ```
   - No falla si no hay `package.json`

## 📋 Dockerfile Corregido

El Dockerfile actualizado incluye:

```dockerfile
FROM php:8.2-cli

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Node.js 18.x desde NodeSource
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ... resto del Dockerfile
```

## 🔄 Si el Error Persiste

### Opción 1: Usar Dockerfile Alternativo

Renombrar `Dockerfile.alternativo` a `Dockerfile`:

```bash
mv Dockerfile.alternativo Dockerfile
```

Este Dockerfile tiene:
- Pasos más separados para mejor debugging
- Manejo de errores mejorado
- Verificaciones de instalación

### Opción 2: Build Local para Debugging

```bash
# Construir localmente para ver el error completo
docker build -t restaurante-laravel .

# Ver logs detallados
docker build --progress=plain -t restaurante-laravel .
```

### Opción 3: Simplificar (sin Node.js)

Si no necesitas compilar assets en el build:

```dockerfile
# Comentar las líneas de npm
# RUN if [ -f "package.json" ]; then npm install; fi
# RUN if [ -f "package.json" ]; then npm run build; fi
```

Y compilar assets localmente antes de subir a GitHub.

## ✅ Verificación

Después de corregir, verificar:

1. **Build exitoso en Render**
2. **Logs sin errores**
3. **Aplicación funcionando**

## 🆘 Si Aún Falla

1. Revisar logs completos en Render
2. Verificar que el Dockerfile esté en `restaurante-laravel/Dockerfile`
3. Verificar que el Root Directory sea correcto
4. Probar el Dockerfile localmente:
   ```bash
   docker build -t test .
   ```

---

**El Dockerfile principal ya está corregido. Si el error persiste, usar `Dockerfile.alternativo`.**

