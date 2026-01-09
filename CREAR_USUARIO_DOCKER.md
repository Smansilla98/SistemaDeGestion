# 🐳 Crear Usuario MySQL en Docker

## ✅ Identificado: MySQL está en Docker

El contenedor es: **`sql-dcac-db-1`** (MariaDB 10.2)

---

## 🔧 Crear Usuario y Base de Datos

### Opción 1: Usar el Script SQL (Recomendado)

```bash
cd /home/santimansilla-bkp/Escritorio/enst/restaurante-laravel
docker exec -i sql-dcac-db-1 mysql -u root -p < scripts/create_user_mysql.sql
```

**Nota**: Te pedirá la contraseña de root de MySQL. Si no la conoces, prueba sin contraseña (Opción 2).

### Opción 2: Sin Contraseña (Si está configurado así)

```bash
cd /home/santimansilla-bkp/Escritorio/enst/restaurante-laravel
docker exec -i sql-dcac-db-1 mysql -u root < scripts/create_user_mysql.sql
```

### Opción 3: Ejecutar Comandos Manualmente

```bash
# Conectar al contenedor MySQL
docker exec -it sql-dcac-db-1 mysql -u root -p
```

Luego ejecuta estos comandos SQL:

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

### Opción 4: Si la contraseña está en variables de entorno

Si el contenedor tiene variables de entorno para la contraseña de root, puedes verlas:

```bash
docker exec sql-dcac-db-1 env | grep -i mysql
```

O verificar el docker-compose.yml si existe.

---

## ✅ Verificación

Después de crear el usuario, verifica:

```bash
# Probar conexión desde el host
mysql -u user -ppassword -h 127.0.0.1 -P 3306 -e "SELECT 1;"

# O desde dentro del contenedor
docker exec -it sql-dcac-db-1 mysql -u user -ppassword -e "SELECT 1;"
```

---

## 🔍 Información del Contenedor

```bash
# Ver detalles del contenedor
docker inspect sql-dcac-db-1 | grep -i env

# Ver logs del contenedor
docker logs sql-dcac-db-1 | tail -20

# Ver variables de entorno
docker exec sql-dcac-db-1 env | grep -i mariadb
```

---

## 📋 Comandos Completos

### Paso 1: Crear usuario y base de datos

```bash
cd /home/santimansilla-bkp/Escritorio/enst/restaurante-laravel

# Intentar con contraseña
docker exec -i sql-dcac-db-1 mysql -u root -p < scripts/create_user_mysql.sql

# O sin contraseña
docker exec -i sql-dcac-db-1 mysql -u root < scripts/create_user_mysql.sql
```

### Paso 2: Verificar

```bash
# Verificar desde el host
mysql -u user -ppassword -h 127.0.0.1 -P 3306 -e "SELECT 1;"
```

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

## 🎯 Resumen

**Contenedor**: `sql-dcac-db-1` (MariaDB 10.2)  
**Puerto**: 3306 (mapeado al host)  
**Comando**: `docker exec -i sql-dcac-db-1 mysql -u root -p < scripts/create_user_mysql.sql`

¡Prueba primero sin contraseña, y si pide contraseña, ingresa la que configuraste! 🚀


