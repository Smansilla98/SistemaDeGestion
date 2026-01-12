# 🔧 Solución Definitiva: Error 127.0.0.1

## ❌ Problema Persistente

Laravel sigue intentando conectarse a `127.0.0.1` incluso después de configurar las variables de entorno.

**Error**: `connection to server at "127.0.0.1", port 5432 failed`

---

## 🔍 Causa Raíz

Laravel no está leyendo las variables de entorno de Railway correctamente. Esto puede deberse a:

1. **Variables no están siendo pasadas al contenedor**
2. **Laravel está usando configuración cacheada**
3. **Las variables tienen formato incorrecto (comillas, espacios, etc.)**

---

## ✅ Solución Definitiva

### Paso 1: Verificar Variables en Railway Shell

En Railway → Tu servicio web → **"Deployments"** → **"View Logs"** → **"Shell"**:

```bash
# Ver TODAS las variables de entorno
env

# Verificar específicamente las de base de datos
env | grep -i db
env | grep -i database

# Verificar que DATABASE_URL esté presente y sin comillas
echo "DATABASE_URL: [$DATABASE_URL]"

# Verificar variables individuales
echo "DB_HOST: [$DB_HOST]"
echo "DB_PORT: [$DB_PORT]"
echo "DB_DATABASE: [$DB_DATABASE]"
echo "DB_USERNAME: [$DB_USERNAME]"
echo "DB_PASSWORD: [$DB_PASSWORD]"
```

**Si alguna variable está vacía o tiene comillas, ese es el problema.**

---

### Paso 2: Configurar Variables Correctamente en Railway

En Railway → Tu servicio web → **"Variables"**, asegúrate de que estén configuradas **EXACTAMENTE** así (sin comillas, sin espacios extra):

```env
APP_NAME="Sistema de Gestión de Restaurante"
APP_ENV=production
APP_KEY=base64:***REDACTED-ROTATED-CREDENTIAL***
APP_DEBUG=false
APP_URL=https://sistemadegestion-production-5d57.up.railway.app

DATABASE_URL=postgresql://postgres:***REDACTED-ROTATED-CREDENTIAL***@postgres.railway.internal:5432/railway
DB_CONNECTION=pgsql
DB_HOST=postgres.railway.internal
DB_PORT=5432
DB_DATABASE=railway
DB_USERNAME=postgres
DB_PASSWORD=***REDACTED-ROTATED-CREDENTIAL***

CACHE_STORE=database
SESSION_DRIVER=database
```

**⚠️ IMPORTANTE**:
- **NO uses comillas dobles** (excepto en APP_NAME si tiene espacios)
- **NO dejes espacios** antes o después del `=`
- **NO uses referencias** `${{...}}`

---

### Paso 3: Limpiar TODO el Cache

En Railway Shell:

```bash
# Limpiar todo el cache de Laravel
php artisan optimize:clear

# O individualmente
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Eliminar archivos de cache manualmente
rm -f bootstrap/cache/config.php
rm -f bootstrap/cache/routes.php
rm -f bootstrap/cache/services.php
```

---

### Paso 4: Verificar Configuración de Laravel

En Railway Shell:

```bash
# Ver la configuración actual de base de datos
php artisan tinker
>>> config('database.connections.pgsql');
>>> exit

# Esto mostrará qué valores está usando Laravel
```

Si muestra `127.0.0.1`, significa que no está leyendo las variables de entorno.

---

### Paso 5: Forzar Lectura de Variables

Si las variables están configuradas pero Laravel no las lee, prueba esto:

En Railway Shell:

```bash
# Verificar que las variables estén disponibles
env | grep DB_

# Si están disponibles, forzar la lectura
php artisan config:clear
php artisan config:cache

# Probar conexión
php artisan tinker
>>> DB::connection()->getPdo();
```

---

### Paso 6: Modificar Dockerfile para Asegurar Variables

Si el problema persiste, modifica el Dockerfile para que no ejecute seeders automáticamente sin las variables:

El Dockerfile actual solo ejecuta `php artisan serve`. Si Railway está ejecutando seeders automáticamente, necesitas asegurarte de que las variables estén disponibles.

**Opción A**: No ejecutar seeders automáticamente (recomendado)

El Dockerfile actual está bien. Ejecuta los seeders manualmente después de verificar las variables.

**Opción B**: Modificar CMD para verificar variables primero

```dockerfile
CMD php artisan config:clear && \
    php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
```

**NO ejecutes seeders automáticamente** hasta que las variables estén verificadas.

---

## 🔍 Verificación Final

En Railway Shell:

```bash
# 1. Verificar variables
env | grep DB_

# 2. Limpiar cache
php artisan optimize:clear

# 3. Ver configuración
php artisan tinker
>>> config('database.default');
>>> config('database.connections.pgsql.host');
>>> exit

# 4. Probar conexión
php artisan tinker
>>> DB::connection()->getPdo();
```

Si todo está correcto, deberías ver:
- `database.default` = `"pgsql"`
- `database.connections.pgsql.host` = `"postgres.railway.internal"` (no `"127.0.0.1"`)
- La conexión PDO funciona

---

## 📋 Checklist Final

- [ ] Variables verificadas en Shell (`env | grep DB_`)
- [ ] `DATABASE_URL` sin comillas y con valores reales
- [ ] Variables individuales (`DB_HOST`, `DB_PORT`, etc.) configuradas
- [ ] Cache limpiado (`php artisan optimize:clear`)
- [ ] Configuración verificada (`php artisan tinker` → `config('database.connections.pgsql')`)
- [ ] Conexión probada (`DB::connection()->getPdo()`)
- [ ] Seeders NO ejecutados automáticamente hasta verificar variables

---

## 🚀 Después de Corregir

1. Railway debería hacer redeploy automáticamente
2. Espera 2-3 minutos
3. Verifica los logs
4. Si las variables están correctas pero sigue fallando, ejecuta en Shell:

```bash
php artisan optimize:clear
php artisan migrate --force
# NO ejecutes db:seed hasta verificar que la conexión funcione
```

---

## 💡 Si Nada Funciona

Si después de todo esto sigue intentando conectarse a `127.0.0.1`, puede ser que:

1. **Railway no está pasando las variables al contenedor** - Verifica en Railway → Variables que estén en el servicio correcto
2. **Hay un problema con el formato de las variables** - Asegúrate de que no tengan espacios, comillas extra, etc.
3. **Laravel está usando un archivo .env local** - Verifica que no haya un `.env` en el contenedor que esté sobrescribiendo las variables

**Último recurso**: Crea un script de inicio que verifique las variables antes de ejecutar Laravel.

---

**El problema está en que Laravel no lee las variables de entorno. Verifica en Railway Shell que las variables estén disponibles y sin comillas.**


