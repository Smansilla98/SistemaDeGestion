# 🚀 Instrucciones para Levantar el Sistema

## Configuración de Base de Datos

**Parámetros configurados:**
- **Usuario**: `user`
- **Password**: `password`
- **Puerto**: `3308`
- **Base de datos**: `restaurante_db`

---

## 📋 Pasos para Levantar el Sistema

### 1. Crear la Base de Datos

**Opción A: Usando MySQL Client (recomendado)**

```bash
mysql -u user -ppassword -P 3308
```

Luego ejecuta:
```sql
CREATE DATABASE IF NOT EXISTS restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

**Opción B: Usando el script SQL**

```bash
mysql -u user -ppassword -P 3308 < scripts/create_database.sql
```

**Opción C: Desde línea de comando**

```bash
mysql -u user -ppassword -P 3308 -e "CREATE DATABASE IF NOT EXISTS restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 2. Verificar Configuración de .env

El archivo `.env` ya está configurado con:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3308
DB_DATABASE=restaurante_db
DB_USERNAME=user
DB_PASSWORD=password
```

### 3. Verificar Extensiones PHP Requeridas

Asegúrate de tener estas extensiones instaladas:

```bash
php -m | grep -E "pdo_mysql|xml|dom|mbstring|curl|zip|gd|bcmath"
```

Si faltan, instálalas:
```bash
sudo apt-get install php8.3-mysql php8.3-xml php8.3-dom php8.3-mbstring php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath
```

### 4. Generar APP_KEY (si no existe)

```bash
php artisan key:generate
```

### 5. Limpiar Caché

```bash
php artisan config:clear
php artisan cache:clear
```

### 6. Ejecutar Migraciones

```bash
php artisan migrate
```

### 7. Ejecutar Seeders (Datos de Prueba)

```bash
php artisan db:seed
```

### 8. Crear Enlace Simbólico de Storage

```bash
php artisan storage:link
```

### 9. Iniciar el Servidor

```bash
php artisan serve
```

El servidor estará disponible en: **http://localhost:8000**

---

## 🔑 Credenciales de Acceso

Después de ejecutar los seeders:

- **Admin**: `admin@restaurante.com` / `admin123`
- **Mozo**: `mozo@restaurante.com` / `mozo123`
- **Cocina**: `cocina@restaurante.com` / `cocina123`
- **Cajero**: `caja@restaurante.com` / `caja123`

---

## ⚠️ Solución de Problemas

### Error: "could not find driver"

**Causa**: Extensión PHP MySQL no instalada.

**Solución**:
```bash
sudo apt-get install php8.3-mysql
sudo systemctl restart php8.3-fpm  # Si usas PHP-FPM
# O reinicia el servidor web
```

### Error: "Class 'DOMDocument' not found"

**Causa**: Extensión PHP XML no instalada.

**Solución**:
```bash
sudo apt-get install php8.3-xml php8.3-dom
```

### Error: "Access denied for user"

**Causa**: Credenciales incorrectas o usuario sin permisos.

**Solución**:
1. Verifica las credenciales en `.env`
2. Verifica que el usuario `user` tenga permisos:
```sql
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'user'@'localhost' IDENTIFIED BY 'password';
FLUSH PRIVILEGES;
```

### Error: "Can't connect to MySQL server"

**Causa**: MySQL no está corriendo o el puerto es incorrecto.

**Solución**:
1. Verifica que MySQL esté corriendo:
```bash
sudo systemctl status mysql
# O
sudo service mysql status
```

2. Verifica que el puerto 3308 esté disponible:
```bash
netstat -tlnp | grep 3308
# O
ss -tlnp | grep 3308
```

### Error al ejecutar migraciones

Si hay errores, puedes reiniciar desde cero:

```bash
php artisan migrate:fresh --seed
```

**⚠️ ADVERTENCIA**: Esto eliminará todos los datos existentes.

---

## ✅ Verificación Rápida

Ejecuta estos comandos para verificar que todo esté bien:

```bash
# 1. Verificar conexión a base de datos
php artisan tinker
>>> DB::connection()->getPdo();
>>> exit

# 2. Verificar rutas
php artisan route:list | head -20

# 3. Verificar que el servidor responde
curl http://localhost:8000
```

---

## 🎯 Comando Completo (Todo en Uno)

Si todo está configurado correctamente, puedes ejecutar:

```bash
# Crear base de datos
mysql -u user -ppassword -P 3308 -e "CREATE DATABASE IF NOT EXISTS restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Configurar Laravel
php artisan key:generate
php artisan config:clear
php artisan cache:clear

# Migraciones y datos
php artisan migrate
php artisan db:seed

# Enlace de storage
php artisan storage:link

# Iniciar servidor
php artisan serve
```

---

## 📝 Notas

- El servidor se ejecutará en `http://127.0.0.1:8000` o `http://localhost:8000`
- Para detener el servidor, presiona `Ctrl+C`
- Para ejecutar en segundo plano: `php artisan serve &`
- Para cambiar el puerto: `php artisan serve --port=8080`

---

**¡Listo para usar!** 🚀

