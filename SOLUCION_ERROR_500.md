# 🔧 Solución: Error 500 en Render

## ✅ Progreso

¡El deploy funcionó! Pero ahora hay un error 500. Esto significa:
- ✅ Build de Docker exitoso
- ✅ Aplicación desplegada
- ❌ Error en runtime (cuando la app ejecuta)

---

## 🔍 Causas Comunes de Error 500

### 1. **APP_KEY no configurado** (Más común)
Laravel necesita `APP_KEY` para funcionar.

**Solución**:
```bash
# En tu máquina local
php artisan key:generate --show
# Copiar la clave generada
```

En Render → Environment Variables:
```env
APP_KEY=base64:TU_CLAVE_GENERADA_AQUI
```

---

### 2. **Base de Datos no configurada**

**Solución**: Agregar variables de entorno en Render:

#### Si usas Supabase:
```env
DB_CONNECTION=pgsql
DB_URL=postgresql://postgres:password@db.xxxxx.supabase.co:5432/postgres
DB_SSLMODE=require
```

#### Si usas PostgreSQL de Render:
Render debería generar automáticamente `DATABASE_URL`. Si no:
```env
DB_CONNECTION=pgsql
DB_HOST=TU_HOST_RENDER
DB_PORT=5432
DB_DATABASE=TU_DATABASE
DB_USERNAME=TU_USUARIO
DB_PASSWORD=TU_PASSWORD
```

---

### 3. **Permisos de Storage/Cache**

**Solución**: Ejecutar en Render → Shell:
```bash
chmod -R 775 storage bootstrap/cache
php artisan storage:link
```

---

### 4. **Migraciones no ejecutadas**

**Solución**: Ejecutar en Render → Shell:
```bash
php artisan migrate --force
php artisan db:seed --force
```

---

### 5. **Variables de Entorno Faltantes**

**Variables mínimas necesarias**:
```env
APP_NAME="Sistema de Gestión de Restaurante"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:TU_CLAVE
APP_URL=https://sistemadegestion-4wwm.onrender.com

# Base de datos (Supabase o Render)
DB_CONNECTION=pgsql
DB_URL=postgresql://...
# O variables individuales

LOG_CHANNEL=stack
LOG_LEVEL=error
```

---

## 🔍 Cómo Ver el Error Real

### Opción 1: Ver Logs en Render

1. En Render → Tu servicio → "Logs"
2. Buscar errores recientes
3. Ver el mensaje de error completo

### Opción 2: Habilitar APP_DEBUG temporalmente

**⚠️ Solo para debugging, deshabilitar después**

En Render → Environment Variables:
```env
APP_DEBUG=true
```

Esto mostrará el error completo en la página (no recomendado en producción).

### Opción 3: Ver Logs de Laravel

En Render → Shell:
```bash
tail -f storage/logs/laravel.log
```

O ver el último error:
```bash
tail -n 50 storage/logs/laravel.log
```

---

## ✅ Checklist de Verificación

- [ ] `APP_KEY` configurado
- [ ] Variables de base de datos configuradas
- [ ] `APP_URL` correcto (https://sistemadegestion-4wwm.onrender.com)
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false` (o `true` para debugging)
- [ ] Permisos de storage correctos
- [ ] Migraciones ejecutadas
- [ ] Storage link creado

---

## 🚀 Pasos para Resolver

### Paso 1: Ver Logs

En Render → Logs, buscar el error específico.

### Paso 2: Verificar Variables de Entorno

En Render → Environment, verificar que todas estén configuradas.

### Paso 3: Generar APP_KEY

```bash
# Local
php artisan key:generate --show
```

Agregar en Render → Environment.

### Paso 4: Configurar Base de Datos

Agregar variables de base de datos (Supabase o Render).

### Paso 5: Ejecutar Comandos en Shell

En Render → Shell:
```bash
php artisan config:clear
php artisan cache:clear
php artisan migrate --force
php artisan storage:link
chmod -R 775 storage bootstrap/cache
```

### Paso 6: Verificar

Recargar la página: https://sistemadegestion-4wwm.onrender.com

---

## 🆘 Errores Específicos Comunes

### "No application encryption key has been specified"
**Solución**: Agregar `APP_KEY` en variables de entorno.

### "SQLSTATE[HY000] [2002] Connection refused"
**Solución**: Verificar variables de base de datos.

### "The stream or file could not be opened"
**Solución**: Ejecutar `chmod -R 775 storage bootstrap/cache`

### "Class 'PDO' not found"
**Solución**: Verificar que las extensiones PHP estén instaladas (ya deberían estar en el Dockerfile).

---

## 📝 Comandos Útiles en Render Shell

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

# Limpiar cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Ver logs
tail -f storage/logs/laravel.log
```

---

**Primero, revisa los logs en Render para ver el error específico. Eso nos dirá exactamente qué falta.**


