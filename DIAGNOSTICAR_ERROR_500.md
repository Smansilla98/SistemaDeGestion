# 🔍 Diagnosticar Error 500 en Railway

## ❌ Error Actual

La aplicación devuelve un error 500: https://sistemadegestion-production-5d57.up.railway.app/

---

## 🔍 Pasos para Diagnosticar

### Paso 1: Ver Logs en Railway

1. Ve a Railway → Tu servicio web
2. Click en **"Deployments"**
3. Click en el último deployment
4. Click en **"View Logs"**
5. Busca los errores más recientes

**Copia el mensaje de error completo** - esto nos dirá exactamente qué está fallando.

---

### Paso 2: Verificar Variables de Entorno

En Railway → Tu servicio web → **"Variables"**, verifica que estén configuradas:

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
```

---

### Paso 3: Habilitar Debug Temporalmente

Para ver el error completo, habilita temporalmente el debug:

En Railway → Variables → Cambiar:

```env
APP_DEBUG=true
```

**⚠️ IMPORTANTE**: Deshabilita después de diagnosticar el problema.

Esto mostrará el error completo en la página (no recomendado en producción, pero útil para debugging).

---

### Paso 4: Verificar en Railway Shell

En Railway → Tu servicio web → **"Deployments"** → **"View Logs"** → **"Shell"**:

```bash
# Verificar variables
env | grep APP_
env | grep DB_

# Verificar conexión a base de datos
php artisan tinker
>>> DB::connection()->getPdo();

# Ver logs de Laravel
tail -n 50 storage/logs/laravel.log

# Limpiar cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Verificar permisos
ls -la storage/
ls -la bootstrap/cache/
```

---

## 🆘 Errores Comunes y Soluciones

### Error 1: "No application encryption key has been specified"

**Solución**:
```bash
# En Railway Shell
php artisan key:generate --show
```

Copia la clave y agrégala como `APP_KEY` en Railway Variables.

---

### Error 2: "SQLSTATE[08006] [7] connection to server at '127.0.0.1'"

**Solución**: Verifica que `DATABASE_URL` y las variables individuales de DB estén configuradas correctamente (sin comillas).

---

### Error 3: "The stream or file could not be opened"

**Solución**:
```bash
# En Railway Shell
chmod -R 775 storage bootstrap/cache
php artisan storage:link
```

---

### Error 4: "Table 'sessions' doesn't exist"

**Solución**:
```bash
# En Railway Shell
php artisan migrate --force
```

---

### Error 5: "Class 'PDO' not found"

**Solución**: Verifica que las extensiones PHP estén instaladas en el Dockerfile (ya deberían estar).

---

## 📋 Checklist de Verificación

- [ ] Logs de Railway revisados
- [ ] Variables de entorno configuradas correctamente
- [ ] `APP_KEY` configurado
- [ ] `DATABASE_URL` y variables individuales configuradas
- [ ] `APP_DEBUG=false` (o `true` temporalmente para debugging)
- [ ] Permisos de storage correctos
- [ ] Migraciones ejecutadas
- [ ] Cache limpiado

---

## 🚀 Comandos Útiles en Railway Shell

```bash
# Verificar PHP
php -v

# Verificar extensiones PHP
php -m | grep pdo

# Verificar Composer
composer --version

# Verificar variables de entorno
env | grep APP_
env | grep DB_

# Limpiar todo el cache
php artisan optimize:clear

# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Ejecutar migraciones
php artisan migrate --force

# Ejecutar seeders
php artisan db:seed --force
```

---

## 💡 Próximos Pasos

1. **Revisa los logs en Railway** - Esto te dirá el error exacto
2. **Copia el mensaje de error completo** - Compártelo para poder ayudarte mejor
3. **Verifica las variables de entorno** - Asegúrate de que estén sin comillas
4. **Limpia el cache** - Ejecuta `php artisan optimize:clear`

---

**El error 500 puede tener muchas causas. Los logs de Railway te dirán exactamente qué está fallando.**


