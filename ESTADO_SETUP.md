# ✅ Estado del Setup - Sistema de Gestión de Restaurante

**Fecha**: 2024-11-25

---

## ✅ Configuración Completada

### 1. Archivo .env Configurado

El archivo `.env` ha sido configurado con los siguientes parámetros:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3308
DB_DATABASE=restaurante_db
DB_USERNAME=user
DB_PASSWORD=password
```

✅ **Estado**: Configurado correctamente

---

## ⚠️ Pasos Pendientes

### 1. Crear la Base de Datos

**Ejecuta uno de estos comandos:**

```bash
# Opción 1: Desde línea de comando
mysql -u user -ppassword -P 3308 -e "CREATE DATABASE IF NOT EXISTS restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Opción 2: Usando el script SQL
mysql -u user -ppassword -P 3308 < scripts/create_database.sql

# Opción 3: Desde MySQL
mysql -u user -ppassword -P 3308
CREATE DATABASE IF NOT EXISTS restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### 2. Verificar Extensiones PHP

Asegúrate de tener estas extensiones instaladas:

```bash
php -m | grep -E "pdo_mysql|xml|dom|mbstring"
```

Si faltan, instálalas:

```bash
sudo apt-get install php8.3-mysql php8.3-xml php8.3-dom php8.3-mbstring
```

### 3. Ejecutar Comandos de Laravel

Una vez que la base de datos esté creada y las extensiones instaladas:

```bash
# Generar APP_KEY (si no existe)
php artisan key:generate

# Limpiar caché
php artisan config:clear
php artisan cache:clear

# Ejecutar migraciones
php artisan migrate

# Poblar con datos de prueba
php artisan db:seed

# Crear enlace de storage
php artisan storage:link

# Iniciar servidor
php artisan serve
```

---

## 📋 Checklist de Setup

- [x] .env configurado con usuario `user`, password `password`, puerto `3308`
- [ ] Base de datos `restaurante_db` creada
- [ ] Extensiones PHP instaladas (pdo_mysql, xml, dom, mbstring)
- [ ] APP_KEY generado
- [ ] Migraciones ejecutadas
- [ ] Seeders ejecutados
- [ ] Storage link creado
- [ ] Servidor iniciado

---

## 🚀 Comando Rápido (Todo en Uno)

Si MySQL está funcionando y las extensiones PHP están instaladas:

```bash
# 1. Crear base de datos
mysql -u user -ppassword -P 3308 -e "CREATE DATABASE IF NOT EXISTS restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Configurar Laravel
cd /home/santimansilla-bkp/Escritorio/enst/restaurante-laravel
php artisan key:generate
php artisan config:clear
php artisan cache:clear

# 3. Migraciones y datos
php artisan migrate
php artisan db:seed

# 4. Storage
php artisan storage:link

# 5. Iniciar servidor
php artisan serve
```

Luego accede a: **http://localhost:8000**

---

## 🔑 Credenciales de Acceso

Una vez ejecutados los seeders:

- **Admin**: `admin@restaurante.com` / `admin123`
- **Mozo**: `mozo@restaurante.com` / `mozo123`
- **Cocina**: `cocina@restaurante.com` / `cocina123`
- **Cajero**: `caja@restaurante.com` / `caja123`

---

## 📚 Documentación Adicional

- `INSTRUCCIONES_SETUP.md` - Instrucciones detalladas con solución de problemas
- `GUIA_INSTALACION_LOCAL.md` - Guía completa de instalación
- `scripts/create_database.sql` - Script SQL para crear la base de datos

---

**Estado Actual**: ✅ .env configurado, pendiente crear base de datos y ejecutar migraciones

