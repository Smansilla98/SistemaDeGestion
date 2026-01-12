# 🔍 Verificar Runtime - Error 500 Después del Build

## ✅ Build Exitoso

El build de Docker se completó correctamente (17.53 segundos). El problema está en **runtime**, no en build.

---

## 🔍 Pasos para Diagnosticar Runtime

### Paso 1: Ver Logs de Runtime (No de Build)

En Railway:

1. Ve a tu **servicio web**
2. Click en **"Deployments"**
3. Click en el último deployment (el que está **activo/running**)
4. Click en **"View Logs"**
5. **IMPORTANTE**: Busca logs de **runtime**, no de build

Los logs de runtime mostrarán:
- Errores de PHP
- Errores de conexión a base de datos
- Errores de Laravel
- Stack traces completos

---

### Paso 2: Verificar que el Contenedor Esté Corriendo

En Railway → Tu servicio web → **"Metrics"** o **"Deployments"**:

- Verifica que el estado sea **"Active"** o **"Running"**
- Verifica que haya tráfico (requests)

---

### Paso 3: Verificar Variables de Entorno en Runtime

En Railway → Tu servicio web → **"Deployments"** → **"View Logs"** → **"Shell"**:

```bash
# Ver todas las variables de entorno
env

# Verificar variables específicas
env | grep APP_
env | grep DB_

# Verificar que DATABASE_URL esté configurada
echo $DATABASE_URL

# Verificar que no tenga comillas
env | grep DATABASE_URL
```

---

### Paso 4: Ver Logs de Laravel

En Railway Shell:

```bash
# Ver los últimos errores
tail -n 100 storage/logs/laravel.log

# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Si el archivo no existe, verifica permisos
ls -la storage/logs/
```

---

### Paso 5: Probar Conexión a Base de Datos

En Railway Shell:

```bash
php artisan tinker
>>> DB::connection()->getPdo();
```

Si funciona, verás información de la conexión. Si falla, verás el error exacto.

---

### Paso 6: Ejecutar Migraciones

En Railway Shell:

```bash
# Verificar estado de migraciones
php artisan migrate:status

# Ejecutar migraciones
php artisan migrate --force

# Si hay seeders
php artisan db:seed --force
```

---

### Paso 7: Limpiar Cache

En Railway Shell:

```bash
# Limpiar todo el cache
php artisan optimize:clear

# O individualmente
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

---

## 🆘 Errores Comunes en Runtime

### Error 1: "No application encryption key"

**Solución**:
```bash
php artisan key:generate --show
```
Agrega la clave como `APP_KEY` en Railway Variables.

---

### Error 2: "Connection refused" o "127.0.0.1"

**Solución**: Verifica que `DATABASE_URL` esté sin comillas y con valores reales.

---

### Error 3: "Table 'sessions' doesn't exist"

**Solución**:
```bash
php artisan migrate --force
```

---

### Error 4: "The stream or file could not be opened"

**Solución**:
```bash
chmod -R 775 storage bootstrap/cache
php artisan storage:link
```

---

### Error 5: "Class 'PDO' not found"

**Solución**: Las extensiones deberían estar instaladas. Verifica:
```bash
php -m | grep pdo
php -m | grep pgsql
```

---

## 📋 Checklist de Verificación Runtime

- [ ] Logs de runtime revisados (no de build)
- [ ] Contenedor está corriendo (Active/Running)
- [ ] Variables de entorno verificadas en Shell (`env | grep DB_`)
- [ ] `DATABASE_URL` sin comillas y con valores reales
- [ ] Logs de Laravel revisados (`tail storage/logs/laravel.log`)
- [ ] Conexión a base de datos probada (`php artisan tinker`)
- [ ] Migraciones ejecutadas (`php artisan migrate --force`)
- [ ] Cache limpiado (`php artisan optimize:clear`)

---

## 🚀 Comandos Útiles en Railway Shell

```bash
# Verificar PHP
php -v

# Verificar extensiones
php -m

# Verificar Composer
composer --version

# Verificar Node
node -v
npm -v

# Ver estructura de directorios
ls -la
ls -la storage/
ls -la bootstrap/cache/

# Verificar permisos
ls -la storage/logs/
ls -la storage/framework/

# Ver variables de entorno
env | sort

# Probar conexión
php artisan tinker
>>> DB::connection()->getPdo();
>>> exit

# Ver rutas
php artisan route:list

# Ver configuración
php artisan config:show database
```

---

## 💡 Diferencia: Build vs Runtime

- **Build logs**: Muestran la construcción del Dockerfile (instalación de paquetes, composer install, etc.)
- **Runtime logs**: Muestran la ejecución de la aplicación (errores de PHP, Laravel, conexiones, etc.)

**Para diagnosticar el error 500, necesitas ver los logs de RUNTIME, no de BUILD.**

---

## 🔍 Cómo Identificar Logs de Runtime

Los logs de runtime típicamente muestran:
- Errores de PHP (stack traces)
- Errores de Laravel (exceptions)
- Mensajes de `php artisan serve`
- Requests HTTP
- Errores de conexión a base de datos

Si solo ves logs de build, espera a que la aplicación reciba una request o busca en la sección de "Logs" del servicio (no del deployment).

---

**El build fue exitoso. Ahora necesitas revisar los logs de RUNTIME para ver el error exacto que causa el 500.**


