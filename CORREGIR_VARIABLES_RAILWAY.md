# 🔧 Corregir Variables de Entorno en Railway

## ❌ Problema

```
SQLSTATE[08006] [7] connection to server at "127.0.0.1", port 5432 failed
```

El error ocurre porque:
1. `DATABASE_URL` usa referencias `${{...}}` que Railway no resuelve en runtime
2. Las variables tienen valores de desarrollo en lugar de producción

---

## ✅ Solución

### Paso 1: Obtener Valores Reales de Railway

1. Ve a Railway → Tu **base de datos PostgreSQL** → **"Variables"**
2. Copia estos valores REALES (no referencias):
   - `RAILWAY_PRIVATE_DOMAIN` → Ejemplo: `postgres.railway.internal`
   - `POSTGRES_PASSWORD` → Ejemplo: `***REDACTED-ROTATED-CREDENTIAL***`

### Paso 2: Configurar Variables en Railway

En Railway → Tu **servicio web** → **"Variables"** → Reemplaza o agrega:

#### ❌ INCORRECTO (con referencias):
```env
DATABASE_URL="postgresql://postgres:${{POSTGRES_PASSWORD}}@${{RAILWAY_PRIVATE_DOMAIN}}:5432/railway"
```

#### ✅ CORRECTO (con valores reales):
```env
DATABASE_URL=postgresql://postgres:***REDACTED-ROTATED-CREDENTIAL***@postgres.railway.internal:5432/railway
```

### Paso 3: Configuración Completa para Producción

Reemplaza todas estas variables en Railway:

```env
APP_NAME="Sistema de Gestión de Restaurante"
APP_ENV=production
APP_KEY=base64:***REDACTED-ROTATED-CREDENTIAL***
APP_DEBUG=false
APP_URL=https://sistemadegestion-production-5d57.up.railway.app

LOG_CHANNEL=stack
LOG_LEVEL=error
LOG_DEPRECATIONS_CHANNEL=null

DATABASE_URL=postgresql://postgres:***REDACTED-ROTATED-CREDENTIAL***@postgres.railway.internal:5432/railway
DB_CONNECTION=pgsql

CACHE_STORE=database
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

QUEUE_CONNECTION=sync
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local

MAIL_MAILER=log
```

**⚠️ IMPORTANTE**: 
- Reemplaza `***REDACTED-ROTATED-CREDENTIAL***` con tu password real
- Reemplaza `postgres.railway.internal` con tu host real si es diferente
- **NO uses comillas dobles** en los valores (excepto en APP_NAME)

---

## 🔍 Comparación: Antes vs Después

### ❌ ANTES (Incorrecto):
```env
APP_DEBUG="true"
APP_ENV="local"
DATABASE_URL="postgresql://postgres:${{POSTGRES_PASSWORD}}@${{RAILWAY_PRIVATE_DOMAIN}}:5432/railway"
LOG_LEVEL="debug"
```

### ✅ DESPUÉS (Correcto):
```env
APP_DEBUG=false
APP_ENV=production
DATABASE_URL=postgresql://postgres:***REDACTED-ROTATED-CREDENTIAL***@postgres.railway.internal:5432/railway
LOG_LEVEL=error
```

---

## 📋 Checklist de Corrección

- [ ] `APP_ENV` cambiado de `"local"` a `production`
- [ ] `APP_DEBUG` cambiado de `"true"` a `false`
- [ ] `LOG_LEVEL` cambiado de `"debug"` a `error`
- [ ] `DATABASE_URL` con valores REALES (no referencias `${{...}}`)
- [ ] `APP_URL` con `https://` (no `http://`)
- [ ] Comillas dobles eliminadas de valores (excepto APP_NAME)
- [ ] `APP_NAME` actualizado a "Sistema de Gestión de Restaurante"

---

## 🚀 Después de Corregir

1. Railway debería hacer redeploy automáticamente
2. Espera 2-3 minutos
3. Verifica los logs en Railway
4. Si sigue fallando, ejecuta en Shell:

```bash
php artisan config:clear
php artisan cache:clear
php artisan migrate --force
```

---

## 🔍 Verificar en Railway Shell

En Railway → Tu servicio web → **"Deployments"** → **"View Logs"** → **"Shell"**:

```bash
# Verificar variables
env | grep DATABASE_URL
env | grep DB_

# Verificar que no haya referencias
env | grep '\$\{'

# Probar conexión
php artisan tinker
>>> DB::connection()->getPdo();
```

Si funciona, verás información de la conexión PDO.

---

## 💡 Por Qué Falla

Railway resuelve las referencias `${{...}}` durante el build, pero **NO en runtime**. Por eso Laravel intenta conectarse a `127.0.0.1` (valor por defecto) en lugar del host real.

**Solución**: Usa siempre valores reales en las variables de entorno de Railway.

---

**Después de corregir, tu aplicación debería conectarse correctamente a la base de datos.**


