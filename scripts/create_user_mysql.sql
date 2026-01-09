    -- Script para crear usuario MySQL y base de datos
-- Ejecutar con: mysql -u root -p < scripts/create_user_mysql.sql

-- Crear usuarios (para diferentes hosts)
CREATE USER IF NOT EXISTS 'user'@'localhost' IDENTIFIED BY 'password';
CREATE USER IF NOT EXISTS 'user'@'127.0.0.1' IDENTIFIED BY 'password';
CREATE USER IF NOT EXISTS 'user'@'%' IDENTIFIED BY 'password';

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Dar permisos completos
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'user'@'localhost';
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'user'@'127.0.0.1';
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'user'@'%';

-- Aplicar cambios
FLUSH PRIVILEGES;

-- Verificar usuarios creados
SELECT User, Host FROM mysql.user WHERE User = 'user';

-- Verificar base de datos
SHOW DATABASES LIKE 'restaurante_db';

