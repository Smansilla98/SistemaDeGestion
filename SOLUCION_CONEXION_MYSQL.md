# 🔧 Solución: Error de Conexión a MySQL

## 🔴 Error

```
SQLSTATE[HY000] [2002] Connection refused
```

Este error indica que **MySQL está rechazando la conexión**. Las posibles causas son:

---

## 🔍 Diagnóstico

### 1. Verificar que MySQL esté corriendo

```bash
# Opción 1: systemd
sudo systemctl status mysql

# Opción 2: service
sudo service mysql status

# Opción 3: procesos
ps aux | grep mysql
```

### 2. Verificar puertos MySQL

```bash
# Ver qué puertos está usando MySQL
sudo netstat -tlnp | grep mysql
# O
sudo ss -tlnp | grep mysql

# Buscar específicamente el puerto 3308
netstat -tlnp | grep 3308
```

### 3. Probar conexión manual

```bash
# Probar con 127.0.0.1
mysql -u user -ppassword -P 3308 -h 127.0.0.1 -e "SELECT 1;"

# Probar con localhost
mysql -u user -ppassword -P 3308 -h localhost -e "SELECT 1;"

# Probar puerto por defecto (3306)
mysql -u user -ppassword -h 127.0.0.1 -e "SELECT 1;"
```

---

## ✅ Soluciones

### Solución 1: MySQL no está corriendo

**Síntoma**: `Connection refused` y MySQL no está activo

**Solución**:
```bash
# Iniciar MySQL
sudo systemctl start mysql
# O
sudo service mysql start

# Verificar que esté corriendo
sudo systemctl status mysql
```

### Solución 2: Puerto incorrecto

**Síntoma**: MySQL está corriendo pero en otro puerto (ej: 3306)

**Solución A**: Cambiar el puerto en `.env`

Si MySQL está en el puerto 3306 (por defecto), edita `.env`:
```env
DB_PORT=3306
```

**Solución B**: Configurar MySQL para usar el puerto 3308

Edita `/etc/mysql/mysql.conf.d/mysqld.cnf`:
```ini
[mysqld]
port = 3308
```

Luego reinicia MySQL:
```bash
sudo systemctl restart mysql
```

### Solución 3: Host incorrecto

**Síntoma**: Conecta con `localhost` pero no con `127.0.0.1` (o viceversa)

**Solución**: Cambiar `DB_HOST` en `.env`

Prueba estas opciones:
```env
# Opción 1: localhost (usa socket Unix)
DB_HOST=localhost

# Opción 2: 127.0.0.1 (usa TCP)
DB_HOST=127.0.0.1
```

### Solución 4: MySQL solo escucha en IPv6 o socket Unix

**Síntoma**: MySQL está activo pero no acepta conexiones TCP

**Solución**: Verificar configuración de MySQL

Edita `/etc/mysql/mysql.conf.d/mysqld.cnf`:
```ini
[mysqld]
bind-address = 127.0.0.1
port = 3308
```

Reinicia MySQL:
```bash
sudo systemctl restart mysql
```

### Solución 5: Usuario sin permisos o credenciales incorrectas

**Síntoma**: Conexión rechazada específicamente para el usuario

**Solución**: Verificar y crear usuario

```sql
-- Conectar como root
mysql -u root -p

-- Crear usuario si no existe
CREATE USER IF NOT EXISTS 'user'@'localhost' IDENTIFIED BY 'password';
CREATE USER IF NOT EXISTS 'user'@'127.0.0.1' IDENTIFIED BY 'password';

-- Dar permisos
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'user'@'localhost';
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'user'@'127.0.0.1';

-- Aplicar cambios
FLUSH PRIVILEGES;

-- Verificar
SELECT User, Host FROM mysql.user WHERE User = 'user';

EXIT;
```

---

## 🔧 Pasos Recomendados

### 1. Verificar estado de MySQL

```bash
sudo systemctl status mysql
```

Si no está corriendo:
```bash
sudo systemctl start mysql
```

### 2. Verificar puerto

```bash
sudo netstat -tlnp | grep mysql
```

Si está en 3306 en lugar de 3308, cambia `.env`:
```bash
cd /home/santimansilla-bkp/Escritorio/enst/restaurante-laravel
sed -i 's/^DB_PORT=.*/DB_PORT=3306/' .env
```

### 3. Probar conexión manual

```bash
# Probar con la configuración actual
mysql -u user -ppassword -P 3308 -h 127.0.0.1 -e "SELECT 1;"

# Si falla, probar puerto por defecto
mysql -u user -ppassword -h 127.0.0.1 -e "SELECT 1;"
```

### 4. Ajustar .env según resultados

Edita `.env` según lo que funcione:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1  # o localhost si funciona mejor
DB_PORT=3308       # o 3306 si ese es el puerto correcto
DB_DATABASE=restaurante_db
DB_USERNAME=user
DB_PASSWORD=password
```

### 5. Limpiar caché de Laravel

```bash
cd /home/santimansilla-bkp/Escritorio/enst/restaurante-laravel
php artisan config:clear
php artisan cache:clear
```

### 6. Probar conexión desde Laravel

```bash
php artisan tinker
>>> DB::connection()->getPdo();
>>> exit
```

---

## 📋 Checklist de Diagnóstico

- [ ] MySQL está corriendo (`systemctl status mysql`)
- [ ] El puerto es correcto (`netstat -tlnp | grep mysql`)
- [ ] La conexión manual funciona (`mysql -u user -p ...`)
- [ ] El usuario tiene permisos (verificar con `mysql.user`)
- [ ] La base de datos existe (`SHOW DATABASES`)
- [ ] `.env` tiene la configuración correcta
- [ ] Caché de Laravel limpiada (`php artisan config:clear`)

---

## 🎯 Comandos Rápidos

```bash
# 1. Iniciar MySQL (si no está corriendo)
sudo systemctl start mysql

# 2. Ver puerto MySQL
sudo netstat -tlnp | grep mysql

# 3. Probar conexión
mysql -u user -ppassword -P 3308 -h 127.0.0.1 -e "SELECT 1;"

# 4. Si el puerto es 3306, cambiar .env
cd /home/santimansilla-bkp/Escritorio/enst/restaurante-laravel
sed -i 's/^DB_PORT=.*/DB_PORT=3306/' .env

# 5. Limpiar caché
php artisan config:clear
php artisan cache:clear

# 6. Probar desde Laravel
php artisan tinker
>>> DB::connection()->getPdo();
```

---

**Una vez resuelto, continúa con las migraciones: `php artisan migrate`**

