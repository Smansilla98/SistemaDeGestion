# 🔗 Conectar Base de Datos al Servicio Web en Railway

## ✅ Base de Datos Creada

Ya tienes PostgreSQL configurado con estas variables:
- `DATABASE_URL` (privada)
- `DATABASE_PUBLIC_URL` (pública)
- `PGHOST`, `PGPORT`, `PGDATABASE`, `PGUSER`, `PGPASSWORD`

---

## 🔗 Opción 1: Railway Comparte Variables Automáticamente (Recomendado)

Railway **debería compartir automáticamente** las variables de la base de datos con tu servicio web si están en el mismo proyecto.

### Verificar si ya están compartidas:

1. Ve a tu **servicio web** (Laravel)
2. Click en **"Variables"**
3. Busca si ya aparecen:
   - `DATABASE_URL`
   - `PGHOST`
   - `PGPORT`
   - `PGDATABASE`
   - `PGUSER`
   - `PGPASSWORD`

**Si ya aparecen** → ¡Perfecto! Solo agrega las variables de Laravel (ver abajo).

**Si NO aparecen** → Sigue con la Opción 2.

---

## 🔗 Opción 2: Agregar Variables Manualmente

### Paso 1: Ir al Servicio Web

1. En Railway → Tu proyecto
2. Click en tu **servicio web** (Laravel)
3. Click en **"Variables"**

### Paso 2: Agregar Variables de Base de Datos

Agrega estas variables (puedes copiarlas desde la base de datos):

```env
DATABASE_URL=postgresql://postgres:${{POSTGRES_PASSWORD}}@${{RAILWAY_PRIVATE_DOMAIN}}:5432/railway
```

O si prefieres variables individuales:

```env
DB_CONNECTION=pgsql
DB_HOST=${{RAILWAY_PRIVATE_DOMAIN}}
DB_PORT=5432
DB_DATABASE=railway
DB_USERNAME=postgres
DB_PASSWORD=${{POSTGRES_PASSWORD}}
```

**⚠️ Nota**: Railway usa `${{...}}` para referenciar variables de otros servicios. Si no funciona, copia los valores reales desde la base de datos.

---

### Paso 3: Agregar Variables de Laravel

Agrega también estas variables esenciales:

```env
APP_NAME="Sistema de Gestión de Restaurante"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:BCJ4ibU3Q0uUyglsfIgY4iLbz/VEIr5hy1xugHLolus=
APP_URL=https://sistemadegestion-production-5d57.up.railway.app

DB_CONNECTION=pgsql

CACHE_DRIVER=file
SESSION_DRIVER=database
QUEUE_CONNECTION=sync

LOG_CHANNEL=stack
LOG_LEVEL=error
```

---

## 🔍 Verificar Conexión

### Opción A: Ver Logs de Railway

1. Railway → Tu servicio web → **"Deployments"**
2. Click en el último deployment
3. Verifica que no haya errores de conexión

### Opción B: Usar Shell de Railway

1. Railway → Tu servicio web → **"Deployments"** → **"View Logs"** → **"Shell"**
2. Ejecuta:

```bash
php artisan tinker
>>> DB::connection()->getPdo();
```

Si funciona, verás información de la conexión PDO.

---

## 🚀 Ejecutar Migraciones

Una vez que las variables estén configuradas:

1. Railway → Tu servicio web → **"Deployments"** → **"View Logs"** → **"Shell"**
2. Ejecuta:

```bash
php artisan migrate --force
php artisan db:seed --force
```

---

## 📋 Checklist

- [ ] Variables de base de datos compartidas o agregadas manualmente
- [ ] `DB_CONNECTION=pgsql` configurado
- [ ] `DATABASE_URL` o variables individuales configuradas
- [ ] `APP_KEY` configurado
- [ ] `APP_URL` configurado
- [ ] Migraciones ejecutadas
- [ ] Seeders ejecutados (opcional)

---

## 🆘 Si No Funciona

### Error: "Connection refused"
- Verifica que `DB_HOST` sea `${{RAILWAY_PRIVATE_DOMAIN}}`
- Asegúrate de que ambos servicios estén en el mismo proyecto

### Error: "Authentication failed"
- Verifica que `DB_PASSWORD` sea `${{POSTGRES_PASSWORD}}`
- O copia el valor real desde la base de datos

### Error: "Database does not exist"
- Verifica que `DB_DATABASE` sea `railway`

---

## 💡 Tip: Usar Valores Reales

Si `${{...}}` no funciona, puedes copiar los valores reales:

1. Ve a la base de datos PostgreSQL → **"Variables"**
2. Copia los valores reales (no las referencias)
3. Pégalos en tu servicio web

Por ejemplo:
- `DB_HOST=containers-us-west-xxx.railway.app` (valor real)
- `DB_PASSWORD=abc123xyz...` (valor real)

---

**Después de configurar, Railway debería hacer redeploy automáticamente. Espera 2-3 minutos y recarga tu aplicación.**

