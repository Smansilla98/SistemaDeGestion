# 🔧 Instalación de Extensiones PHP

## ⚠️ Requiere Permisos de Administrador

La instalación de extensiones PHP requiere permisos `sudo`. 

---

## 🚀 Método Rápido (Recomendado)

Ejecuta este comando desde el directorio del proyecto:

```bash
cd /home/santimansilla-bkp/Escritorio/enst/restaurante-laravel
sudo bash scripts/install_extensions.sh
```

---

## 📋 Instalación Manual

Si prefieres instalar manualmente, ejecuta estos comandos:

```bash
# 1. Actualizar lista de paquetes
sudo apt-get update

# 2. Instalar extensiones PHP
sudo apt-get install -y \
    php8.3-mysql \
    php8.3-xml \
    php8.3-dom \
    php8.3-mbstring \
    php8.3-curl \
    php8.3-zip \
    php8.3-gd \
    php8.3-bcmath
```

---

## ✅ Verificación

Después de instalar, verifica que las extensiones estén cargadas:

```bash
php -m | grep -E "pdo_mysql|xml|dom|mbstring|curl|zip|gd|bcmath"
```

Deberías ver:
```
bcmath
curl
dom
gd
mbstring
pdo_mysql
xml
zip
```

---

## 🔍 Extensiones Requeridas

### Críticas (sin estas no funciona)
- ✅ **pdo_mysql** - Conexión a MySQL/MariaDB
- ✅ **xml** - Parsing XML
- ✅ **dom** - DOMDocument (necesario para artisan)

### Importantes (recomendadas)
- ✅ **mbstring** - Manejo de strings multi-byte
- ✅ **curl** - Cliente HTTP
- ✅ **zip** - Compresión de archivos
- ✅ **gd** - Procesamiento de imágenes
- ✅ **bcmath** - Cálculos matemáticos de precisión

---

## 📊 Estado Actual

### Extensiones Instaladas
- ✅ curl
- ✅ mbstring
- ✅ zip
- ✅ libxml (parcial)

### Extensiones Faltantes
- ❌ **pdo_mysql** (CRÍTICA)
- ❌ **dom** (CRÍTICA)
- ❌ gd
- ❌ bcmath

---

## ⚡ Después de Instalar

Una vez instaladas las extensiones:

1. **Verificar instalación**:
   ```bash
   php -m | grep -E "pdo_mysql|dom"
   ```

2. **Limpiar caché de Laravel**:
   ```bash
   cd /home/santimansilla-bkp/Escritorio/enst/restaurante-laravel
   php artisan config:clear
   php artisan cache:clear
   ```

3. **Crear base de datos** (si no existe):
   ```bash
   mysql -u user -ppassword -P 3308 -e "CREATE DATABASE IF NOT EXISTS restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

4. **Ejecutar migraciones**:
   ```bash
   php artisan migrate
   ```

5. **Ejecutar seeders**:
   ```bash
   php artisan db:seed
   ```

6. **Levantar servidor**:
   ```bash
   php artisan serve
   ```

---

## 🆘 Solución de Problemas

### Si algunas extensiones no se instalan

Verifica la versión de PHP:
```bash
php --version
```

Si usas una versión diferente (ej: 8.2, 8.1), ajusta los nombres:
```bash
# Para PHP 8.2
sudo apt-get install php8.2-mysql php8.2-xml php8.2-dom ...

# Para PHP 8.1
sudo apt-get install php8.1-mysql php8.1-xml php8.1-dom ...
```

### Si necesitas reiniciar servicios

Después de instalar extensiones, puede ser necesario reiniciar:
```bash
# Si usas PHP-FPM
sudo systemctl restart php8.3-fpm

# Si usas Apache
sudo systemctl restart apache2

# Si usas Nginx + PHP-FPM
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm
```

---

**¡Ejecuta el script y continúa con la instalación!** 🚀

