-- Script para crear la base de datos del Sistema de Gestión de Restaurante
-- Ejecutar con: mysql -u user -ppassword -P 3308 < scripts/create_database.sql

CREATE DATABASE IF NOT EXISTS restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE restaurante_db;

-- Verificar que la base de datos se creó correctamente
SHOW DATABASES LIKE 'restaurante_db';

