# 🔐 Crear Usuario MySQL Sin Contraseña de Root

## 🔴 Problema Actual

El error de socket está resuelto, pero ahora tienes un problema de autenticación:
```
Access denied for user 'root'@'...' (using password: YES)
```

Esto significa que la contraseña de root es incorrecta o root no tiene acceso desde tu IP.

---

## ✅ Soluciones

### Opción 1: Usar sudo (Sin contraseña de MySQL)

Si tu sistema permite acceso a MySQL con sudo sin contraseña:

```bash
cd /home/santimansilla-bkp/Escritorio/enst/restaurante-laravel

# Crear usuario usando sudo (sin contraseña de MySQL)
sudo mysql < scripts/create_user_mysql.sql
```

O ejecutar comandos manualmente:

```bash
sudo mysql
```

Luego en MySQL:
```sql
CREATE USER IF NOT EXISTS 'user'@'localhost' IDENTIFIED BY 'password';
CREATE USER IF NOT EXISTS 'user'@'127.0.0.1' IDENTIFIED BY 'password';
CREATE USER IF NOT EXISTS 'user'@'%' IDENTIFIED BY 'password';
CREATE DATABASE IF NOT EXISTS restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'user'@'localhost';
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'user'@'127.0.0.1';
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'user'@'%';
FLUSH PRIVILEGES;
EXIT;
```

### Opción 2: Intentar sin contraseña

```bash
mysql -u root -h 127.0.0.1 -P 3306 < scripts/create_user_mysql.sql
```

(Sin el `-p`, presiona Enter cuando pida contraseña)

### Opción 3: Si conoces otra contraseña de root

Prueba con diferentes contraseñas comunes:
- (vacío, presiona Enter)
- `root`
- `password`
- La contraseña que configuraste al instalar MySQL

### Opción 4: Si MySQL está en Docker

Si MySQL está corriendo en Docker, puedes acceder directamente:

```bash
# Ver contenedores
docker ps | grep mysql

# Conectar al contenedor (sin contraseña generalmente)
docker exec -it <nombre_contenedor> mysql -u root < scripts/create_user_mysql.sql
```

O copiar el script al contenedor:

```bash
docker cp scripts/create_user_mysql.sql <nombre_contenedor>:/tmp/
docker exec -it <nombre_contenedor> mysql -u root < /tmp/create_user_mysql.sql
```

### Opción 5: Resetear contraseña de root (Última opción)

Si nada funciona y tienes acceso al servidor:

```bash
# Detener MySQL
sudo systemctl stop mysql

# Iniciar MySQL en modo seguro
sudo mysqld_safe --skip-grant-tables &

# Conectar sin contraseña
mysql -u root

# Cambiar contraseña
USE mysql;
ALTER USER 'root'@'localhost' IDENTIFIED BY 'nueva_password';
FLUSH PRIVILEGES;
EXIT;

# Reiniciar MySQL normalmente
sudo systemctl restart mysql
```

---

## 🎯 Recomendación

**Prueba en este orden:**

1. **Primero**: `sudo mysql` (más común en sistemas Ubuntu/Debian modernos)
2. **Segundo**: `mysql -u root` (sin contraseña)
3. **Tercero**: Si está en Docker, usa `docker exec`

---

## ✅ Después de Crear el Usuario

Una vez que logres crear el usuario, verifica:

```bash
# Probar conexión con el nuevo usuario
mysql -u user -ppassword -h 127.0.0.1 -P 3306 -e "SELECT 1;"
```

Si funciona, continúa con Laravel:

```bash
cd /home/santimansilla-bkp/Escritorio/enst/restaurante-laravel
php artisan config:clear
php artisan cache:clear
php artisan migrate
php artisan db:seed
php artisan serve
```

---

**¡Intenta primero con `sudo mysql`!** 🚀

