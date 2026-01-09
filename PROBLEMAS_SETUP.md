# ⚠️ Problemas Detectados al Levantar el Servidor

## 🔴 Errores Encontrados

### 1. Extensión PHP MySQL no encontrada
**Error**: `could not find driver (Connection: mysql)`

**Causa**: La extensión `pdo_mysql` no está instalada o habilitada.

**Solución**:
```bash
# Instalar extensión MySQL
sudo apt-get update
sudo apt-get install php8.3-mysql

# O si usas otra versión de PHP
sudo apt-get install php-mysql

# Verificar que esté instalada
php -m | grep pdo_mysql
```

### 2. Extensión PHP DOM no encontrada
**Error**: `Class "DOMDocument" not found`

**Causa**: La extensión `php-xml` y `php-dom` no están instaladas.

**Solución**:
```bash
# Instalar extensiones XML
sudo apt-get install php8.3-xml php8.3-dom

# O si usas otra versión
sudo apt-get install php-xml php-dom

# Verificar
php -m | grep -E "xml|dom"
```

### 3. Base de Datos no creada (posible)
**Error**: Podría fallar la conexión si la base de datos no existe.

**Solución**:
```bash
# Crear la base de datos
mysql -u user -ppassword -P 3308 -e "CREATE DATABASE IF NOT EXISTS restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

---

## ✅ Pasos para Resolver

### Paso 1: Instalar Extensiones PHP

```bash
# Instalar todas las extensiones necesarias
sudo apt-get update
sudo apt-get install php8.3-mysql php8.3-xml php8.3-dom php8.3-mbstring php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath

# Verificar instalación
php -m | grep -E "pdo_mysql|xml|dom|mbstring"
```

### Paso 2: Crear Base de Datos

```bash
# Verificar que MySQL esté corriendo
sudo systemctl status mysql

# Crear la base de datos
mysql -u user -ppassword -P 3308 -e "CREATE DATABASE IF NOT EXISTS restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Verificar que se creó
mysql -u user -ppassword -P 3308 -e "SHOW DATABASES LIKE 'restaurante_db';"
```

### Paso 3: Ejecutar Migraciones

```bash
cd /home/santimansilla-bkp/Escritorio/enst/restaurante-laravel

# Limpiar caché
php artisan config:clear
php artisan cache:clear

# Ejecutar migraciones
php artisan migrate

# Si hay errores, puedes usar fresh (elimina todo)
# php artisan migrate:fresh --seed
```

### Paso 4: Ejecutar Seeders

```bash
php artisan db:seed
```

### Paso 5: Crear Storage Link

```bash
php artisan storage:link
```

### Paso 6: Levantar el Servidor

```bash
php artisan serve
```

---

## 🔍 Verificación Rápida

Ejecuta estos comandos para verificar el estado:

```bash
# 1. Verificar extensiones PHP
php -m | grep -E "pdo_mysql|xml|dom"

# 2. Verificar conexión a MySQL
mysql -u user -ppassword -P 3308 -e "SELECT 1;" 2>&1

# 3. Verificar que la base de datos existe
mysql -u user -ppassword -P 3308 -e "SHOW DATABASES;" | grep restaurante_db

# 4. Verificar configuración de Laravel
cd /home/santimansilla-bkp/Escritorio/enst/restaurante-laravel
php artisan config:show database.connections.mysql
```

---

## 📋 Comandos Completos (Todo en Uno)

```bash
# 1. Instalar extensiones (requiere sudo)
sudo apt-get update
sudo apt-get install -y php8.3-mysql php8.3-xml php8.3-dom php8.3-mbstring php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath

# 2. Crear base de datos
mysql -u user -ppassword -P 3308 -e "CREATE DATABASE IF NOT EXISTS restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 3. Configurar Laravel
cd /home/santimansilla-bkp/Escritorio/enst/restaurante-laravel
php artisan config:clear
php artisan cache:clear

# 4. Migraciones y seeders
php artisan migrate
php artisan db:seed

# 5. Storage link
php artisan storage:link

# 6. Levantar servidor
php artisan serve
```

---

## 🎯 Estado Actual

- ✅ `.env` configurado correctamente
- ❌ Extensiones PHP faltantes (pdo_mysql, xml, dom)
- ❓ Base de datos (verificar si existe)
- ❌ Migraciones no ejecutadas (por falta de extensiones)
- ❌ Servidor no levantado (por errores previos)

---

## 📝 Notas

- Si no tienes permisos sudo, necesitarás que un administrador instale las extensiones PHP
- Si MySQL no está corriendo, inícialo con: `sudo systemctl start mysql`
- Si el puerto 3308 no es el correcto, verifica la configuración de MySQL
- Una vez resueltos estos problemas, el servidor debería levantarse sin problemas

---

**Después de resolver estos problemas, el servidor estará disponible en: http://localhost:8000**

