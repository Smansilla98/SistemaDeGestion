# 🚀 Pasos Rápidos: Configurar BD en Railway

## ✅ Paso 1: Crear Base de Datos PostgreSQL

1. En Railway → Tu proyecto
2. Click en **"New"** → **"Database"** → **"Add PostgreSQL"**
3. Railway creará automáticamente la base de datos
4. **¡Importante!** Railway generará automáticamente la variable `DATABASE_URL`

---

## ✅ Paso 2: Configurar Variables de Entorno

En Railway → Tu servicio web → **"Variables"** → Agregar estas variables:

```env
APP_NAME="Sistema de Gestión de Restaurante"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:***REDACTED-ROTATED-CREDENTIAL***
APP_URL=https://sistemadegestion-production-5d57.up.railway.app

DB_CONNECTION=pgsql
```

**⚠️ IMPORTANTE**: Railway debería agregar automáticamente `DATABASE_URL` cuando creas la base de datos. Si no aparece:

1. Ve a la base de datos PostgreSQL que creaste
2. Click en **"Variables"**
3. Copia el valor de `DATABASE_URL`
4. Agrégalo en las variables de tu servicio web

---

## ✅ Paso 3: Ejecutar Migraciones

En Railway → Tu servicio web → **"Deployments"** → Click en el último deployment → **"View Logs"** → **"Shell"**:

```bash
php artisan migrate --force
php artisan db:seed --force
```

O espera a que Railway haga redeploy automáticamente y luego ejecuta los comandos.

---

## 🔍 Verificar

1. Recarga tu aplicación: https://sistemadegestion-production-5d57.up.railway.app
2. Debería funcionar sin el error de SQLite

---

## 📋 Variables Completas (Opcional)

Si quieres configurar todo manualmente:

```env
APP_NAME="Sistema de Gestión de Restaurante"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:***REDACTED-ROTATED-CREDENTIAL***
APP_URL=https://sistemadegestion-production-5d57.up.railway.app

DB_CONNECTION=pgsql
DATABASE_URL=postgresql://postgres:password@host:5432/railway

CACHE_DRIVER=file
SESSION_DRIVER=database
QUEUE_CONNECTION=sync

LOG_CHANNEL=stack
LOG_LEVEL=error
```

---

**Nota**: Railway puede tardar 2-3 minutos en aplicar los cambios. Si no funciona inmediatamente, espera un poco y recarga.

