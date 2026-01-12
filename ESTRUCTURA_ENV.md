# 📋 Estructura Completa del Archivo .env

Este documento contiene la estructura completa del archivo `.env` para el Sistema de Gestión de Restaurante en Laravel, configurado para Railway.

---

## 📝 Contenido del Archivo .env

```env
# ============================================
# CONFIGURACIÓN DE LA APLICACIÓN
# ============================================
APP_NAME="Sistema de Gestión de Restaurante"
APP_ENV=production
APP_KEY=base64:***REDACTED-ROTATED-CREDENTIAL***
APP_DEBUG=false
APP_URL=https://sistemadegestion-production-5d57.up.railway.app

# ============================================
# LOGGING (Registro de eventos)
# ============================================
LOG_CHANNEL=stack
LOG_LEVEL=error
LOG_DEPRECATIONS_CHANNEL=null

# ============================================
# BASE DE DATOS - PostgreSQL (Railway)
# ============================================
# IMPORTANTE: Reemplaza estos valores con los REALES de Railway
# 
# Para obtener los valores reales:
# 1. Ve a Railway → Base de datos PostgreSQL → Variables
# 2. Copia RAILWAY_PRIVATE_DOMAIN (para DB_HOST)
# 3. Copia POSTGRES_PASSWORD (para DB_PASSWORD)
# 4. Reemplaza TU_HOST_AQUI y TU_PASSWORD_AQUI con esos valores

# Opción 1: Usar DATABASE_URL (Recomendado)
DATABASE_URL=postgresql://postgres:TU_PASSWORD_AQUI@TU_HOST_AQUI:5432/railway

# Opción 2: Variables individuales (si DATABASE_URL no funciona)
DB_CONNECTION=pgsql
DB_HOST=TU_HOST_AQUI
DB_PORT=5432
DB_DATABASE=railway
DB_USERNAME=postgres
DB_PASSWORD=TU_PASSWORD_AQUI

# ============================================
# CACHE (Caché de la aplicación)
# ============================================
CACHE_STORE=database
CACHE_PREFIX=laravel_cache

# ============================================
# SESIONES (Gestión de sesiones de usuario)
# ============================================
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

# ============================================
# COLA DE TRABAJOS (QUEUE)
# ============================================
# Opciones: sync, database, redis, sqs, beanstalkd
QUEUE_CONNECTION=sync

# Si usas 'database' como driver:
# QUEUE_CONNECTION=database

# Si usas Redis:
# QUEUE_CONNECTION=redis
# REDIS_HOST=127.0.0.1
# REDIS_PASSWORD=null
# REDIS_PORT=6379

# ============================================
# BROADCASTING (Notificaciones en tiempo real)
# ============================================
# Opciones: log, pusher, redis, null
BROADCAST_DRIVER=log

# Si usas Pusher:
# BROADCAST_DRIVER=pusher
# PUSHER_APP_ID=tu_app_id
# PUSHER_APP_KEY=tu_app_key
# PUSHER_APP_SECRET=tu_app_secret
# PUSHER_APP_CLUSTER=mt1
# PUSHER_HOST=
# PUSHER_PORT=443
# PUSHER_SCHEME=https
# PUSHER_APP_CLUSTER=eu

# Si usas Redis:
# BROADCAST_DRIVER=redis

# ============================================
# FILESYSTEM (Almacenamiento de archivos)
# ============================================
# Opciones: local, public, s3, ftp, sftp
FILESYSTEM_DISK=local

# Si usas AWS S3:
# FILESYSTEM_DISK=s3
# AWS_ACCESS_KEY_ID=tu_access_key
# AWS_SECRET_ACCESS_KEY=tu_secret_key
# AWS_DEFAULT_REGION=us-east-1
# AWS_BUCKET=tu_bucket_name
# AWS_USE_PATH_STYLE_ENDPOINT=false
# AWS_ENDPOINT=

# ============================================
# MAIL (Correo electrónico)
# ============================================
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@sistemadegestion.com"
MAIL_FROM_NAME="${APP_NAME}"

# Configuración para producción (ejemplo con Mailtrap):
# MAIL_MAILER=smtp
# MAIL_HOST=smtp.mailtrap.io
# MAIL_PORT=2525
# MAIL_USERNAME=tu_usuario
# MAIL_PASSWORD=tu_password
# MAIL_ENCRYPTION=tls
# MAIL_FROM_ADDRESS="noreply@sistemadegestion.com"
# MAIL_FROM_NAME="${APP_NAME}"

# Otras opciones de mail:
# - sendmail
# - mailgun
# - ses
# - postmark
# - array (solo para testing)

# ============================================
# SERVICIOS EXTERNOS - AWS
# ============================================
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false
AWS_ENDPOINT=

# ============================================
# VITE (Frontend assets)
# ============================================
VITE_APP_NAME="${APP_NAME}"

# ============================================
# SEGURIDAD - Sanctum (API Authentication)
# ============================================
SANCTUM_STATEFUL_DOMAINS=sistemadegestion-production-5d57.up.railway.app
SESSION_DOMAIN=null

# ============================================
# OPCIONALES - SCOUT (Búsqueda)
# ============================================
# Si usas Algolia:
# SCOUT_DRIVER=algolia
# ALGOLIA_APP_ID=tu_app_id
# ALGOLIA_SECRET=tu_secret

# Si usas Meilisearch:
# SCOUT_DRIVER=meilisearch
# MEILISEARCH_HOST=http://127.0.0.1:7700
# MEILISEARCH_KEY=tu_key

# Si usas Typesense:
# SCOUT_DRIVER=typesense
# TYPESENSE_API_KEY=tu_api_key
# TYPESENSE_HOST=tu_host
# TYPESENSE_PORT=443
# TYPESENSE_PROTOCOL=https

# ============================================
# OPCIONALES - HORIZON (Monitor de colas)
# ============================================
# Si usas Laravel Horizon con Redis:
# HORIZON_PREFIX=horizon
# HORIZON_BALANCE=auto
# HORIZON_MAX_PROCESSES=1

# ============================================
# OPCIONALES - TELESCOPE (Debugging)
# ============================================
# Solo para desarrollo, deshabilitar en producción:
# TELESCOPE_ENABLED=false

# ============================================
# OPCIONALES - CORS
# ============================================
# Si necesitas configurar CORS manualmente:
# CORS_ALLOWED_ORIGINS=https://sistemadegestion-production-5d57.up.railway.app

# ============================================
# OPCIONALES - RATE LIMITING
# ============================================
# Configuración de límites de tasa (si es necesario):
# RATE_LIMIT_ENABLED=true
# RATE_LIMIT_MAX_ATTEMPTS=60
# RATE_LIMIT_DECAY_MINUTES=1

# ============================================
# OPCIONALES - TIMEZONE
# ============================================
# Si necesitas cambiar la zona horaria:
# APP_TIMEZONE=America/Argentina/Buenos_Aires

# ============================================
# OPCIONALES - LOCALE
# ============================================
# Si necesitas cambiar el idioma:
# APP_LOCALE=es
# APP_FALLBACK_LOCALE=en
# APP_FAKER_LOCALE=es_AR
```

---

## 📖 Descripción de Variables

### 🔧 Configuración de la Aplicación

| Variable | Descripción | Ejemplo |
|----------|-------------|---------|
| `APP_NAME` | Nombre de la aplicación | `"Sistema de Gestión de Restaurante"` |
| `APP_ENV` | Entorno de ejecución | `production`, `local`, `testing` |
| `APP_KEY` | Clave de encriptación de Laravel | `base64:...` (generada con `php artisan key:generate`) |
| `APP_DEBUG` | Modo debug (solo `true` en desarrollo) | `false` en producción |
| `APP_URL` | URL base de la aplicación | `https://sistemadegestion-production-5d57.up.railway.app` |

### 🗄️ Base de Datos

| Variable | Descripción | Ejemplo |
|----------|-------------|---------|
| `DATABASE_URL` | URL completa de conexión (recomendado) | `postgresql://user:pass@host:5432/db` |
| `DB_CONNECTION` | Tipo de base de datos | `pgsql`, `mysql`, `sqlite` |
| `DB_HOST` | Host de la base de datos | `containers-us-west-xxx.railway.app` |
| `DB_PORT` | Puerto de la base de datos | `5432` (PostgreSQL) |
| `DB_DATABASE` | Nombre de la base de datos | `railway` |
| `DB_USERNAME` | Usuario de la base de datos | `postgres` |
| `DB_PASSWORD` | Contraseña de la base de datos | `tu_password_secreto` |

### 💾 Cache y Sesiones

| Variable | Descripción | Opciones |
|----------|-------------|----------|
| `CACHE_STORE` | Driver de caché | `database`, `file`, `redis`, `memcached` |
| `SESSION_DRIVER` | Driver de sesiones | `database`, `file`, `redis`, `cookie` |
| `SESSION_LIFETIME` | Duración de sesión en minutos | `120` (2 horas) |

### 📧 Mail

| Variable | Descripción | Ejemplo |
|----------|-------------|---------|
| `MAIL_MAILER` | Driver de correo | `smtp`, `sendmail`, `mailgun` |
| `MAIL_HOST` | Servidor SMTP | `smtp.mailtrap.io` |
| `MAIL_PORT` | Puerto SMTP | `2525`, `587`, `465` |
| `MAIL_USERNAME` | Usuario SMTP | `tu_usuario` |
| `MAIL_PASSWORD` | Contraseña SMTP | `tu_password` |
| `MAIL_ENCRYPTION` | Tipo de encriptación | `tls`, `ssl`, `null` |
| `MAIL_FROM_ADDRESS` | Dirección de remitente | `noreply@sistemadegestion.com` |
| `MAIL_FROM_NAME` | Nombre del remitente | `"Sistema de Gestión de Restaurante"` |

---

## 🔐 Valores Sensibles

**⚠️ IMPORTANTE**: Las siguientes variables contienen información sensible y NO deben subirse a Git:

- `APP_KEY`
- `DB_PASSWORD`
- `DATABASE_URL` (contiene password)
- `MAIL_PASSWORD`
- `AWS_SECRET_ACCESS_KEY`
- `PUSHER_APP_SECRET`
- Cualquier token o clave API

Asegúrate de que el archivo `.env` esté en `.gitignore`.

---

## 🚀 Configuración para Railway

### Paso 1: Obtener Valores de Railway

1. Ve a Railway → Tu **base de datos PostgreSQL** → **"Variables"**
2. Copia estos valores:
   - `RAILWAY_PRIVATE_DOMAIN` → Para `DB_HOST`
   - `POSTGRES_PASSWORD` → Para `DB_PASSWORD`

### Paso 2: Configurar Variables en Railway

En Railway → Tu **servicio web** → **"Variables"** → Agrega:

```env
APP_NAME="Sistema de Gestión de Restaurante"
APP_ENV=production
APP_KEY=base64:***REDACTED-ROTATED-CREDENTIAL***
APP_DEBUG=false
APP_URL=https://sistemadegestion-production-5d57.up.railway.app

DB_CONNECTION=pgsql
DATABASE_URL=postgresql://postgres:TU_PASSWORD@TU_HOST:5432/railway

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=sync
```

**Reemplaza**:
- `TU_PASSWORD` → Con el valor de `POSTGRES_PASSWORD`
- `TU_HOST` → Con el valor de `RAILWAY_PRIVATE_DOMAIN`

---

## ✅ Checklist de Configuración

- [ ] `APP_KEY` generado y configurado
- [ ] `APP_URL` configurado con la URL correcta
- [ ] `DB_CONNECTION=pgsql` configurado
- [ ] `DATABASE_URL` o variables individuales configuradas con valores REALES
- [ ] `CACHE_STORE` configurado
- [ ] `SESSION_DRIVER` configurado
- [ ] Variables de mail configuradas (si es necesario)
- [ ] Variables opcionales configuradas según necesidades

---

## 📚 Referencias

- [Documentación de Laravel - Configuración](https://laravel.com/docs/configuration)
- [Documentación de Railway - Variables de Entorno](https://docs.railway.app/develop/variables)
- [Documentación de PostgreSQL](https://www.postgresql.org/docs/)

---

**Última actualización**: 2026-01-12

