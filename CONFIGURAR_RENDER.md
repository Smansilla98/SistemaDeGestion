# 🚀 Configurar Render para Laravel

Guía paso a paso para configurar el Sistema de Gestión de Restaurante en Render.

---

## 📋 Configuración del Web Service

### ⚠️ Importante: Render no tiene PHP directo

Render no ofrece PHP como opción directa, pero podemos usar **Docker** para ejecutar Laravel.

### Paso 1: Información Básica

- **Name**: `restaurante-laravel` (o el nombre que prefieras)
- **Environment**: `Docker` ✅
- **Region**: Seleccionar la más cercana a ti
  - Si estás en Argentina: **South America (São Paulo)** o **Oregon (US West)**
  - Si estás en USA: **Virginia (US East)** o **Oregon (US West)**

### Paso 2: Repositorio

- **Repository**: Conectar tu repositorio de GitHub
- **Branch**: `main` ✅

### Paso 3: Configuración Avanzada

#### Root Directory
```
restaurante-laravel
```
⚠️ **Importante**: Si tu proyecto está en la carpeta `restaurante-laravel` dentro del repositorio, debes especificarlo aquí.

#### Dockerfile Path (si Render lo pide)
```
Dockerfile
```
O dejar vacío si está en la raíz del Root Directory.

#### Build Command
**Dejar vacío** - Docker se encargará del build automáticamente usando el Dockerfile.

#### Start Command
**Dejar vacío** - El Dockerfile ya tiene el CMD configurado.

**O si Render requiere un Start Command:**
```bash
php artisan serve --host=0.0.0.0 --port=$PORT
```

### Alternativa: Si Render no detecta el Dockerfile

Si Render no detecta automáticamente el Dockerfile, puedes especificar:

**Dockerfile Path**: `restaurante-laravel/Dockerfile`

---

## 🔧 Configuración Completa

### Valores para el Formulario de Render:

```
Language: Docker ✅
Branch: main
Region: South America (São Paulo) [o la más cercana]
Root Directory: restaurante-laravel
Dockerfile Path: Dockerfile (o dejar vacío si está en la raíz)
Build Command: (dejar vacío - Docker lo maneja)
Start Command: (dejar vacío - Dockerfile tiene el CMD)
```

### ⚠️ Nota sobre Dockerfile

El Dockerfile debe estar en la raíz del proyecto Laravel (dentro de `restaurante-laravel/`).

Si Render no detecta el Dockerfile automáticamente:
- **Dockerfile Path**: `Dockerfile` (relativo al Root Directory)

---

## 🔐 Variables de Entorno

Después de crear el servicio, agregar estas variables en **Environment**:

### Variables Básicas

```env
APP_NAME="Sistema de Gestión de Restaurante"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-app.onrender.com
```

### Generar APP_KEY

```bash
# En tu máquina local
php artisan key:generate --show
# Copiar la clave generada
```

Agregar en Render:
```env
APP_KEY=base64:TU_CLAVE_GENERADA_AQUI
```

### Base de Datos (Supabase o PostgreSQL)

#### Si usas Supabase:
```env
DB_CONNECTION=pgsql
DB_URL=postgresql://postgres:password@db.xxxxx.supabase.co:5432/postgres
DB_SSLMODE=require
```

#### Si usas PostgreSQL de Render:
Render generará automáticamente estas variables:
- `DATABASE_URL` (usar esta)
- O las variables individuales

```env
DB_CONNECTION=pgsql
DB_HOST=TU_HOST_RENDER
DB_PORT=5432
DB_DATABASE=TU_DATABASE
DB_USERNAME=TU_USUARIO
DB_PASSWORD=TU_PASSWORD
```

### Otras Variables

```env
LOG_CHANNEL=stack
LOG_LEVEL=error
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

---

## 📝 Pasos Completos

### 1. Crear Base de Datos (si no usas Supabase)

1. En Render Dashboard → "New +" → "PostgreSQL"
2. Nombre: `restaurante-db`
3. Región: Misma que el Web Service
4. Plan: Free (para empezar)
5. Crear

### 2. Crear Web Service

1. "New +" → "Web Service"
2. Conectar repositorio: `SistemaDeGestion`
3. Configurar:
   - **Name**: `restaurante-laravel`
   - **Environment**: `Docker` ✅
   - **Region**: Misma que la base de datos
   - **Branch**: `main`
   - **Root Directory**: `restaurante-laravel`
   - **Dockerfile Path**: `Dockerfile` (o dejar vacío)
   - **Build Command**: (dejar vacío - Docker lo maneja)
   - **Start Command**: (dejar vacío - Dockerfile tiene el CMD)

### 3. Configurar Variables de Entorno

En el Web Service → "Environment", agregar todas las variables mencionadas arriba.

### 4. Ejecutar Migraciones

Una vez desplegado, en Render → Web Service → "Shell":

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

### 5. Verificar

Tu aplicación estará disponible en:
`https://tu-app.onrender.com`

---

## ⚠️ Errores Comunes

### Error: "Dockerfile not found"
- **Solución**: Verificar que el Dockerfile esté en `restaurante-laravel/Dockerfile`
- Verificar que el Root Directory sea correcto
- Especificar `Dockerfile Path` si es necesario

### Error: "Root Directory no encontrado"
- **Solución**: Verificar que el directorio sea `restaurante-laravel` (sin barra al final)

### Error: "Build failed" (Docker)
- **Solución**: Verificar que el Dockerfile esté correcto
- Verificar logs de build en Render
- Verificar que `composer.json` y `package.json` existan

### Error: "Start failed"
- **Solución**: Verificar que el Dockerfile tenga el CMD correcto
- Verificar que `APP_KEY` esté configurado
- Verificar que el puerto sea `8000` o usar `$PORT`

### Error: "Database connection failed"
- **Solución**: Verificar variables de entorno de base de datos
- Si usas Supabase, verificar `DB_SSLMODE=require`
- Verificar que las variables estén en el Web Service, no solo en la base de datos

---

## ✅ Checklist Final

- [ ] Dockerfile creado en `restaurante-laravel/Dockerfile`
- [ ] Language: **Docker** (no Node, Python, etc.)
- [ ] Branch: `main`
- [ ] Root Directory: `restaurante-laravel`
- [ ] Dockerfile Path: `Dockerfile` (o vacío)
- [ ] Build Command: (vacío - Docker lo maneja)
- [ ] Start Command: (vacío - Dockerfile tiene el CMD)
- [ ] Variables de entorno agregadas
- [ ] APP_KEY generado y configurado
- [ ] Base de datos configurada (Supabase o Render)
- [ ] Migraciones ejecutadas (en Shell después del deploy)
- [ ] Storage link creado (en Shell después del deploy)

---

**¡Listo para desplegar en Render! 🚀**

