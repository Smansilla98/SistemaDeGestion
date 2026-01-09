# 👤 Crear Usuario MySQL

## ✅ Progreso

- ✅ Puerto corregido: Ahora usa 3306 (correcto)
- ✅ Conexión a MySQL funciona
- ❌ Usuario 'user' no existe o sin permisos

---

## 🔴 Error Actual

```
SQLSTATE[HY000] [1045] Access denied for user 'user'@'...' (using password: YES)
```

Esto significa que el usuario `user` no existe en MySQL o las credenciales son incorrectas.

---

## 🔧 Solución: Crear Usuario MySQL

### Opción 1: Conectar como root y crear usuario

```bash
# Conectar como root (usa tu contraseña de root)
mysql -u root -p
```

Luego ejecuta estos comandos SQL:

```sql
-- Crear usuario si no existe (para localhost)
CREATE USER IF NOT EXISTS 'user'@'localhost' IDENTIFIED BY 'password';

-- Crear usuario para 127.0.0.1
CREATE USER IF NOT EXISTS 'user'@'127.0.0.1' IDENTIFIED BY 'password';

-- Crear usuario para cualquier host (si es necesario)
CREATE USER IF NOT EXISTS 'user'@'%' IDENTIFIED BY 'password';

-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Dar permisos completos en la base de datos
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'user'@'localhost';
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'user'@'127.0.0.1';
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'user'@'%';

-- Aplicar cambios
FLUSH PRIVILEGES;

-- Verificar usuarios creados
SELECT User, Host FROM mysql.user WHERE User = 'user';

-- Verificar permisos
SHOW GRANTS FOR 'user'@'localhost';

EXIT;
```

### Opción 2: Script SQL completo

Crea un archivo `create_user.sql`:

```sql
-- Crear usuarios
CREATE USER IF NOT EXISTS 'user'@'localhost' IDENTIFIED BY 'password';
CREATE USER IF NOT EXISTS 'user'@'127.0.0.1' IDENTIFIED BY 'password';
CREATE USER IF NOT EXISTS 'user'@'%' IDENTIFIED BY 'password';

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Dar permisos
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'user'@'localhost';
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'user'@'127.0.0.1';
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'user'@'%';

FLUSH PRIVILEGES;
```

Ejecutar:
```bash
mysql -u root -p < create_user.sql
```

### Opción 3: Si no tienes contraseña de root

```bash
# Intentar sin contraseña
mysql -u root

# O con sudo (sin contraseña de MySQL)
sudo mysql
```

Luego ejecuta los comandos SQL de la Opción 1.

---

## ✅ Verificación

Después de crear el usuario, verifica:

```bash
# Probar conexión
mysql -u user -ppassword -h 127.0.0.1 -e "SELECT 1;"

# O desde Laravel
cd /home/santimansilla-bkp/Escritorio/enst/restaurante-laravel
php artisan tinker
>>> DB::connection()->getPdo();
>>> exit
```

---

## 🎯 Resumen

1. **Conectar como root**:
   ```bash
   mysql -u root -p
   ```

2. **Crear usuario y base de datos** (ejecutar los SQL de arriba)

3. **Probar conexión**:
   ```bash
   mysql -u user -ppassword -h 127.0.0.1 -e "SELECT 1;"
   ```

4. **Continuar con Laravel**:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

---

**Una vez creado el usuario, continúa con las migraciones!** 🚀

