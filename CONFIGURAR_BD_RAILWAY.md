# 🔧 Configurar Base de Datos en Railway

## ❌ Error Actual

```
Database file at path [/var/www/html/database/database.sqlite] does not exist.
```

Laravel está intentando usar SQLite en lugar de PostgreSQL.

---

## ✅ Solución: Configurar Variables de Entorno en Railway

### Paso 1: Crear Base de Datos PostgreSQL en Railway

1. En Railway → Tu proyecto → **"New"** → **"Database"** → **"Add PostgreSQL"**
2. Railway creará automáticamente una base de datos PostgreSQL
3. Se generarán automáticamente las variables de entorno:
   - `DATABASE_URL` (conexión completa)
   - `PGHOST`
   - `PGPORT`
   - `PGDATABASE`
   - `PGUSER`
   - `PGPASSWORD`

---

### Paso 2: Configurar Variables de Entorno en el Servicio Web

En Railway → Tu servicio web → **"Variables"** → Agregar:

#### Opción A: Usar DATABASE_URL (Recomendado)

```env
DB_CONNECTION=pgsql
DATABASE_URL=postgresql://usuario:password@host:5432/database
```

Railway debería agregar `DATABASE_URL` automáticamente cuando creas la base de datos.

#### Opción B: Variables Individuales

Si prefieres usar variables individuales:

```env
DB_CONNECTION=pgsql
DB_HOST=containers-us-east-xxx.railway.app
DB_PORT=5432
DB_DATABASE=railway
DB_USERNAME=postgres
DB_PASSWORD=tu_password_aqui
```

**⚠️ Importante**: Reemplaza los valores con los que Railway te proporciona.

---

### Paso 3: Otras Variables Necesarias

Agregar también estas variables en Railway:

```env
APP_NAME="Sistema de Gestión de Restaurante"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:TU_CLAVE_GENERADA
APP_URL=https://sistemadegestion-production-5d57.up.railway.app

# Base de datos (ya configurada arriba)
DB_CONNECTION=pgsql
DATABASE_URL=postgresql://...

# Cache y Sesiones
CACHE_DRIVER=file
SESSION_DRIVER=database
QUEUE_CONNECTION=sync

# Logs
LOG_CHANNEL=stack
LOG_LEVEL=error
```

---

### Paso 4: Generar APP_KEY

En tu máquina local:

```bash
cd /home/santimansilla-bkp/Escritorio/enst/restaurante-laravel
php artisan key:generate --show
```

Copia la clave generada (ej: `base64:xxxxx...`) y agrégala como `APP_KEY` en Railway.

---

### Paso 5: Ejecutar Migraciones en Railway

En Railway → Tu servicio web → **"Deployments"** → **"View Logs"** → **"Shell"**:

```bash
php artisan migrate --force
php artisan db:seed --force
```

O agrega un script de inicio en el Dockerfile (ver abajo).

---

## 🐳 Actualizar Dockerfile para Migraciones Automáticas

Puedes agregar las migraciones al Dockerfile para que se ejecuten automáticamente:

```dockerfile
# Al final del Dockerfile, antes del CMD
RUN php artisan config:cache || true
RUN php artisan route:cache || true
RUN php artisan view:cache || true

# Ejecutar migraciones al iniciar (opcional)
# CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
```

O mejor, crear un script de inicio:

```dockerfile
# Crear script de inicio
RUN echo '#!/bin/sh\n\
php artisan migrate --force || true\n\
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}' > /start.sh && \
chmod +x /start.sh

CMD ["/start.sh"]
```

---

## 🔍 Verificar Configuración

### En Railway Shell:

```bash
# Verificar variables de entorno
env | grep DB_

# Verificar conexión
php artisan tinker
>>> DB::connection()->getPdo();
```

Si funciona, verás información de la conexión PDO.

---

## 📋 Checklist

- [ ] Base de datos PostgreSQL creada en Railway
- [ ] Variables de entorno configuradas en el servicio web
- [ ] `DB_CONNECTION=pgsql` configurado
- [ ] `DATABASE_URL` o variables individuales configuradas
- [ ] `APP_KEY` generado y configurado
- [ ] `APP_URL` configurado con la URL de Railway
- [ ] Migraciones ejecutadas (`php artisan migrate --force`)
- [ ] Seeders ejecutados si es necesario (`php artisan db:seed --force`)

---

## 🆘 Problemas Comunes

### Error: "Connection refused"
- Verifica que `DB_HOST` sea correcto
- Verifica que el puerto sea `5432`
- Asegúrate de que la base de datos esté en el mismo proyecto de Railway

### Error: "Authentication failed"
- Verifica `DB_USERNAME` y `DB_PASSWORD`
- Usa `DATABASE_URL` si Railway la proporciona automáticamente

### Error: "Database does not exist"
- Verifica `DB_DATABASE`
- Asegúrate de que la base de datos esté creada

---

## 🚀 Después de Configurar

1. Railway debería hacer redeploy automáticamente
2. Verifica los logs en Railway → "Deployments" → "View Logs"
3. Accede a tu aplicación: https://sistemadegestion-production-5d57.up.railway.app
4. Deberías ver la aplicación funcionando correctamente

---

**Nota**: Railway puede tardar unos minutos en aplicar los cambios. Si no funciona inmediatamente, espera 2-3 minutos y recarga.

