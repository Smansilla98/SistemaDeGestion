# 🚀 Configurar Supabase con Laravel

Guía rápida para configurar Supabase como base de datos para el Sistema de Gestión de Restaurante.

---

## ✅ Ventajas de Supabase

- ✅ **Gratis**: 500MB de base de datos, 1GB de storage
- ✅ **Fácil**: Dashboard visual muy intuitivo
- ✅ **PostgreSQL**: Base de datos robusta y escalable
- ✅ **Storage**: Almacenamiento de archivos incluido
- ✅ **Realtime**: Soporte para notificaciones en tiempo real
- ✅ **Compatible**: Funciona con cualquier hosting

---

## 📋 Pasos de Configuración

### 1. Crear Proyecto en Supabase

1. Ir a https://supabase.com
2. Hacer clic en "Start your project"
3. Registrarse con GitHub o email
4. Crear nuevo proyecto:
   - **Name**: `restaurante-laravel`
   - **Database Password**: Generar contraseña segura (¡guardarla!)
   - **Region**: Seleccionar la más cercana
   - **Pricing Plan**: Free

### 2. Obtener Credenciales

En Supabase Dashboard → **Settings** → **Database**:

- **Host**: `db.xxxxx.supabase.co`
- **Port**: `5432`
- **Database**: `postgres`
- **User**: `postgres`
- **Password**: La que configuraste

O copiar el **Connection string** completo.

### 3. Configurar Laravel

#### Opción A: Connection String (Recomendado)

En `.env`:

```env
DB_CONNECTION=pgsql
DB_URL=postgresql://postgres:[TU_PASSWORD]@db.xxxxx.supabase.co:5432/postgres
DB_SSLMODE=require
```

#### Opción B: Valores Individuales

```env
DB_CONNECTION=pgsql
DB_HOST=db.xxxxx.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=tu_password_seguro
DB_SSLMODE=require
```

### 4. Instalar Extensión PostgreSQL (si es necesario)

```bash
# Ubuntu/Debian
sudo apt install php-pgsql

# Verificar
php -m | grep pgsql
```

### 5. Probar Conexión

```bash
php artisan tinker
>>> DB::connection()->getPdo();
# Debe mostrar: PDO connection
```

### 6. Ejecutar Migraciones

```bash
php artisan migrate
php artisan db:seed
```

---

## 🔧 Configuración para Producción

### En Render/Railway

Agregar variables de entorno:

```env
DB_CONNECTION=pgsql
DB_URL=postgresql://postgres:password@db.xxxxx.supabase.co:5432/postgres
DB_SSLMODE=require
```

### En VPS

Editar `.env` con las credenciales de Supabase.

---

## ✅ Verificación

1. En Supabase Dashboard → **Table Editor**
2. Deberías ver tus tablas después de ejecutar migraciones
3. Probar la aplicación: debe conectarse correctamente

---

## 🆘 Solución de Problemas

### Error: "SSL connection required"
```env
DB_SSLMODE=require
```

### Error: "Authentication failed"
- Verificar que el usuario sea `postgres`
- Verificar la contraseña exacta desde Supabase

### Error: "Connection refused"
- Verificar host: `db.xxxxx.supabase.co`
- Verificar puerto: `5432`

---

**¡Listo! Supabase configurado correctamente. 🎉**

