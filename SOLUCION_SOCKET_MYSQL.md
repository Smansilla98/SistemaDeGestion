# 🔧 Solución: Error de Socket MySQL

## 🔴 Error

```
ERROR 2002 (HY000): Can't connect to local MySQL server through socket '/var/run/mysqld/mysqld.sock' (2)
```

Este error ocurre cuando el cliente MySQL intenta conectarse usando un **socket Unix** que no existe, en lugar de usar **TCP/IP**.

---

## ✅ Solución: Usar TCP/IP en lugar del Socket

### Opción 1: Especificar host y puerto (Recomendado)

```bash
cd /home/santimansilla-bkp/Escritorio/enst/restaurante-laravel
mysql -u root -p -h 127.0.0.1 -P 3306 < scripts/create_user_mysql.sql
```

### Opción 2: Conectar manualmente y ejecutar comandos

```bash
mysql -u root -p -h 127.0.0.1 -P 3306
```

Luego copia y pega estos comandos:

```sql
CREATE USER IF NOT EXISTS 'user'@'localhost' IDENTIFIED BY 'password';
CREATE USER IF NOT EXISTS 'user'@'127.0.0.1' IDENTIFIED BY 'password';
CREATE USER IF NOT EXISTS 'user'@'%' IDENTIFIED BY 'password';
CREATE DATABASE IF NOT EXISTS restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'user'@'localhost';
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'user'@'127.0.0.1';
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'user'@'%';
FLUSH PRIVILEGES;
SELECT User, Host FROM mysql.user WHERE User = 'user';
SHOW DATABASES LIKE 'restaurante_db';
EXIT;
```

### Opción 3: Si MySQL está en Docker

Si MySQL está corriendo en Docker, puede que necesites:

```bash
# Verificar contenedores MySQL
docker ps | grep mysql

# Conectar usando el contenedor
docker exec -it <nombre_contenedor> mysql -u root -p < scripts/create_user_mysql.sql
```

---

## 🔍 Verificación

### 1. Verificar que MySQL está corriendo

```bash
# Ver estado del servicio
systemctl status mysql

# Ver puertos en uso
netstat -tlnp | grep 3306
# O
ss -tlnp | grep 3306
```

### 2. Probar conexión TCP

```bash
# Probar conexión con TCP
mysql -u root -p -h 127.0.0.1 -P 3306 -e "SELECT 1;"
```

### 3. Verificar después de crear usuario

```bash
# Probar con el nuevo usuario
mysql -u user -ppassword -h 127.0.0.1 -P 3306 -e "SELECT 1;"
```

---

## 📋 Comandos Completos

### Paso 1: Crear usuario y base de datos

```bash
cd /home/santimansilla-bkp/Escritorio/enst/restaurante-laravel
mysql -u root -p -h 127.0.0.1 -P 3306 < scripts/create_user_mysql.sql
```

**Nota**: Te pedirá la contraseña de root de MySQL. Ingresa la contraseña cuando se solicite.

### Paso 2: Verificar que funcionó

```bash
mysql -u user -ppassword -h 127.0.0.1 -P 3306 -e "SELECT 1;"
```

Si funciona, verás un `1` en la salida.

### Paso 3: Continuar con Laravel

```bash
cd /home/santimansilla-bkp/Escritorio/enst/restaurante-laravel
php artisan config:clear
php artisan cache:clear
php artisan migrate
php artisan db:seed
php artisan serve
```

---

## 🔧 Si el problema persiste

### Verificar ubicación del socket (alternativa)

Si prefieres usar el socket Unix, puedes encontrarlo:

```bash
# Buscar sockets MySQL
find /var/run -name "*.sock" 2>/dev/null | grep mysql
find /tmp -name "*.sock" 2>/dev/null | grep mysql

# O verificar configuración MySQL
mysql_config --socket
```

Si encuentras el socket en otra ubicación, puedes especificarlo:

```bash
mysql -u root -p --socket=/ruta/al/socket/mysqld.sock
```

**Pero la solución más simple es usar TCP con `-h 127.0.0.1 -P 3306`**

---

## 🎯 Resumen

**El problema**: El cliente MySQL intenta usar un socket Unix que no existe.

**La solución**: Usar TCP/IP especificando `-h 127.0.0.1 -P 3306`

**Comando correcto**:
```bash
mysql -u root -p -h 127.0.0.1 -P 3306 < scripts/create_user_mysql.sql
```

¡Esto debería funcionar! 🚀

