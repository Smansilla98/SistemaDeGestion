# 🌐 Guía de Despliegue Web - Sistema de Gestión de Restaurante

Guía completa para desplegar el proyecto Laravel en diferentes plataformas de hosting.

---

## 📋 Opciones de Hosting

### 🆓 Opciones Gratuitas (Para Pruebas/Desarrollo)

#### 1. **Render** ⭐ (Recomendado - Gratis)
- ✅ **URL**: https://render.com
- ✅ **Gratis**: Sí (con limitaciones)
- ✅ **Base de datos**: PostgreSQL gratis incluido
- ✅ **SSL**: Automático
- ✅ **Deploy**: Automático desde GitHub
- ✅ **PHP**: Soporte completo
- ⚠️ **Limitación**: Se duerme después de 15 min de inactividad

**Pasos**:
1. Conectar repositorio de GitHub
2. Seleccionar "Web Service"
3. Configurar build: `composer install && npm install && npm run build`
4. Configurar start: `php artisan serve --host=0.0.0.0 --port=$PORT`
5. Agregar variables de entorno
6. Conectar base de datos PostgreSQL

---

#### 2. **Supabase** ⭐⭐⭐ (Excelente Alternativa - Recomendado)
- ✅ **URL**: https://supabase.com
- ✅ **Gratis**: 500MB de base de datos, 1GB de storage
- ✅ **Base de datos**: PostgreSQL gestionado (muy fácil)
- ✅ **SSL**: Automático
- ✅ **Dashboard**: Interfaz web completa
- ✅ **Storage**: Almacenamiento de archivos incluido
- ✅ **Realtime**: Soporte para notificaciones en tiempo real
- ✅ **Compatible**: Funciona con cualquier hosting (Render, Railway, VPS, etc.)

**Ventajas sobre Railway**:
- ✅ Más fácil de configurar
- ✅ Dashboard visual muy completo
- ✅ Storage para archivos incluido
- ✅ Realtime subscriptions
- ✅ Autenticación integrada (opcional)
- ✅ Mejor para proyectos que necesitan más que solo base de datos

**Pasos**:
1. Crear cuenta en https://supabase.com
2. Crear nuevo proyecto
3. Obtener credenciales de conexión
4. Configurar en Laravel (ver guía abajo)

---

#### 3. **Railway** ⭐ (Muy Popular)
- ✅ **URL**: https://railway.app
- ✅ **Gratis**: $5 crédito mensual
- ✅ **Base de datos**: MySQL/PostgreSQL incluido
- ✅ **SSL**: Automático
- ✅ **Deploy**: Automático desde GitHub
- ✅ **PHP**: Soporte completo

**Pasos**:
1. Conectar repositorio de GitHub
2. Crear nuevo proyecto
3. Agregar servicio "Web Service"
4. Agregar servicio "MySQL" o "PostgreSQL"
5. Configurar variables de entorno
6. Deploy automático

---

#### 4. **Fly.io**
- ✅ **URL**: https://fly.io
- ✅ **Gratis**: 3 VMs pequeñas gratis
- ✅ **Base de datos**: MySQL/PostgreSQL
- ✅ **SSL**: Automático
- ✅ **Global**: Despliegue en múltiples regiones

---

#### 5. **Heroku** (Limitado - Ya no es tan gratuito)
- ✅ **URL**: https://heroku.com
- ⚠️ **Gratis**: Ya no ofrece plan gratuito
- ✅ **Base de datos**: PostgreSQL (addon)
- ✅ **SSL**: Automático
- ✅ **Deploy**: Git push

---

### 💰 Opciones de Pago (Producción)

#### 1. **DigitalOcean App Platform** ⭐
- ✅ **URL**: https://www.digitalocean.com/products/app-platform
- 💰 **Precio**: Desde $5/mes
- ✅ **Base de datos**: MySQL/PostgreSQL gestionado
- ✅ **SSL**: Automático
- ✅ **Escalable**: Fácil escalamiento
- ✅ **PHP**: Soporte completo Laravel

---

#### 2. **AWS (Amazon Web Services)**
- ✅ **URL**: https://aws.amazon.com
- 💰 **Precio**: Pay-as-you-go (puede ser económico)
- ✅ **Servicios**: EC2, RDS, Elastic Beanstalk
- ✅ **Escalable**: Altamente escalable
- ⚠️ **Complejidad**: Requiere más configuración

**Opciones AWS**:
- **EC2**: Servidor virtual (más control)
- **Elastic Beanstalk**: Despliegue simplificado
- **Lightsail**: Opción más simple ($3.50/mes)

---

#### 3. **Google Cloud Platform (GCP)**
- ✅ **URL**: https://cloud.google.com
- 💰 **Precio**: $300 crédito gratis por 90 días
- ✅ **Servicios**: App Engine, Cloud Run, Compute Engine
- ✅ **Escalable**: Altamente escalable

---

#### 4. **Azure**
- ✅ **URL**: https://azure.microsoft.com
- 💰 **Precio**: $200 crédito gratis por 30 días
- ✅ **Servicios**: App Service, Virtual Machines
- ✅ **Escalable**: Altamente escalable

---

#### 5. **VPS Tradicionales** ⭐ (Más Control)
- **DigitalOcean Droplets**: Desde $4/mes
- **Linode**: Desde $5/mes
- **Vultr**: Desde $2.50/mes
- **Hetzner**: Desde €4.15/mes (muy económico)

**Ventajas**:
- Control total del servidor
- Puedes instalar lo que necesites
- Más económico para proyectos medianos/grandes

---

### 🇦🇷 Opciones Argentinas

#### 1. **DonWeb**
- ✅ **URL**: https://www.donweb.com
- 💰 **Precio**: Desde $1,500 ARS/mes
- ✅ **PHP**: Soporte completo
- ✅ **Base de datos**: MySQL incluido
- ✅ **SSL**: Incluido

---

#### 2. **Hosting.com.ar**
- ✅ **URL**: https://www.hosting.com.ar
- 💰 **Precio**: Desde $800 ARS/mes
- ✅ **PHP**: Soporte completo
- ✅ **Base de datos**: MySQL incluido

---

#### 3. **NubeAr**
- ✅ **URL**: https://www.nubear.com
- 💰 **Precio**: Desde $1,200 ARS/mes
- ✅ **PHP**: Soporte completo
- ✅ **Base de datos**: MySQL incluido

---

## 🚀 Guía de Despliegue: Render (Recomendado para Empezar)

### Paso 1: Preparar el Proyecto

Asegúrate de que tu proyecto esté en GitHub:
```bash
git add .
git commit -m "Preparado para producción"
git push origin main
```

### Paso 2: Crear Cuenta en Render

1. Ir a https://render.com
2. Registrarse con GitHub
3. Autorizar acceso al repositorio

### Paso 3: Crear Base de Datos

1. En Render Dashboard → "New +" → "PostgreSQL"
2. Nombre: `restaurante-db`
3. Región: Más cercana a ti
4. Plan: Free (para pruebas)
5. Crear base de datos
6. **Copiar la "Internal Database URL"** (la necesitarás)

### Paso 4: Crear Web Service

1. "New +" → "Web Service"
2. Conectar repositorio: `SistemaDeGestion`
3. Configurar:
   - **Name**: `restaurante-laravel`
   - **Region**: Misma que la base de datos
   - **Branch**: `main`
   - **Root Directory**: `restaurante-laravel`
   - **Environment**: `PHP`
   - **Build Command**:
     ```bash
     composer install --no-dev --optimize-autoloader && npm install && npm run build
     ```
   - **Start Command**:
     ```bash
     php artisan serve --host=0.0.0.0 --port=$PORT
     ```

### Paso 5: Configurar Variables de Entorno

En el Web Service → "Environment":

```env
APP_NAME="Sistema de Gestión de Restaurante"
APP_ENV=production
APP_KEY=base64:TU_CLAVE_AQUI
APP_DEBUG=false
APP_URL=https://tu-app.onrender.com

DB_CONNECTION=pgsql
DB_HOST=TU_HOST_POSTGRESQL
DB_PORT=5432
DB_DATABASE=TU_DATABASE
DB_USERNAME=TU_USUARIO
DB_PASSWORD=TU_PASSWORD

# Usar la Internal Database URL de Render
# Formato: postgresql://user:password@host:5432/database

LOG_CHANNEL=stack
LOG_LEVEL=error
```

**Generar APP_KEY**:
```bash
php artisan key:generate --show
# Copiar la clave generada
```

### Paso 6: Ejecutar Migraciones

En Render → Web Service → "Shell":

```bash
php artisan migrate --force
php artisan db:seed --force
```

### Paso 7: Configurar Storage

```bash
php artisan storage:link
```

### Paso 8: Verificar

Tu aplicación estará disponible en:
`https://tu-app.onrender.com`

---

## 🚀 Guía de Despliegue: Supabase (Recomendado) ⭐

### ¿Por qué Supabase?

Supabase es una excelente alternativa a Railway porque:
- ✅ **Más fácil de configurar**: Dashboard visual muy intuitivo
- ✅ **Gratis generoso**: 500MB de base de datos, 1GB de storage
- ✅ **Storage incluido**: Para subir imágenes y archivos
- ✅ **Realtime**: Soporte para notificaciones en tiempo real
- ✅ **Compatible con cualquier hosting**: Puedes usar Supabase con Render, Railway, VPS, etc.
- ✅ **PostgreSQL gestionado**: Base de datos robusta y escalable

### Paso 1: Crear Proyecto en Supabase

1. Ir a https://supabase.com
2. Hacer clic en "Start your project"
3. Registrarse con GitHub (recomendado) o email
4. Crear nuevo proyecto:
   - **Name**: `restaurante-laravel`
   - **Database Password**: Generar una contraseña segura (¡guardarla!)
   - **Region**: Seleccionar la más cercana (ej: South America)
   - **Pricing Plan**: Free (para empezar)

### Paso 2: Obtener Credenciales de Conexión

1. En el Dashboard de Supabase, ir a **Settings** → **Database**
2. Buscar la sección **Connection string**
3. Copiar la **Connection string** (URI) o usar los valores individuales:
   - **Host**: `db.xxxxx.supabase.co`
   - **Port**: `5432`
   - **Database**: `postgres`
   - **User**: `postgres`
   - **Password**: La que configuraste al crear el proyecto

**Ejemplo de Connection String**:
```
postgresql://postgres:[TU_PASSWORD]@db.xxxxx.supabase.co:5432/postgres
```

### Paso 3: Configurar Laravel para Supabase

#### Opción A: Usar Connection String (Recomendado)

En tu `.env` (o variables de entorno en tu hosting):

```env
DB_CONNECTION=pgsql
DB_URL=postgresql://postgres:[TU_PASSWORD]@db.xxxxx.supabase.co:5432/postgres
```

Laravel detectará automáticamente los valores desde `DB_URL`.

#### Opción B: Configuración Individual

```env
DB_CONNECTION=pgsql
DB_HOST=db.xxxxx.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=tu_password_seguro
```

**Importante**: Para Supabase, necesitas habilitar SSL:

```env
DB_SSLMODE=require
```

O en `config/database.php`, modificar la conexión PostgreSQL:

```php
'pgsql' => [
    'driver' => 'pgsql',
    'url' => env('DB_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'laravel'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => env('DB_CHARSET', 'utf8'),
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => 'public',
    'sslmode' => env('DB_SSLMODE', 'require'), // Requerido para Supabase
],
```

### Paso 4: Instalar Extensión PostgreSQL en PHP

Si estás usando un VPS o servidor propio, asegúrate de tener la extensión:

```bash
# Ubuntu/Debian
sudo apt install php-pgsql

# Verificar
php -m | grep pgsql
```

### Paso 5: Ejecutar Migraciones

```bash
# Probar conexión
php artisan tinker
>>> DB::connection()->getPdo();
# Debe mostrar: PDO connection

# Ejecutar migraciones
php artisan migrate

# Ejecutar seeders (opcional)
php artisan db:seed
```

### Paso 6: Usar Supabase con Render/Railway/VPS

Supabase funciona perfectamente con cualquier hosting. Solo necesitas:

1. **En Render/Railway**: Agregar las variables de entorno de Supabase
2. **En VPS**: Configurar el `.env` con las credenciales de Supabase

**Ejemplo para Render**:
- En el Web Service → Environment, agregar:
  ```env
  DB_CONNECTION=pgsql
  DB_URL=postgresql://postgres:password@db.xxxxx.supabase.co:5432/postgres
  DB_SSLMODE=require
  ```

### Paso 7: Usar Storage de Supabase (Opcional)

Supabase también ofrece almacenamiento de archivos. Para usarlo:

1. En Supabase Dashboard → **Storage**
2. Crear un bucket (ej: `restaurante-uploads`)
3. Configurar políticas de acceso
4. Usar el SDK de Supabase o la API REST

**Nota**: Laravel ya tiene su propio sistema de storage. Puedes seguir usando `storage/app/public` y solo usar Supabase Storage si necesitas CDN o acceso público directo.

### Paso 8: Verificar Conexión

En Supabase Dashboard → **Table Editor**, deberías ver tus tablas después de ejecutar las migraciones.

### Ventajas de Supabase sobre Railway

| Característica | Supabase | Railway |
|----------------|----------|---------|
| **Facilidad de uso** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ |
| **Dashboard visual** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ |
| **Storage incluido** | ✅ Sí | ❌ No |
| **Realtime** | ✅ Sí | ❌ No |
| **Gratis** | 500MB DB | $5 crédito |
| **Configuración** | Muy fácil | Fácil |
| **Documentación** | Excelente | Buena |

### Solución de Problemas

#### Error: "SSL connection required"
```env
DB_SSLMODE=require
```

#### Error: "Connection refused"
- Verificar que el host sea correcto: `db.xxxxx.supabase.co`
- Verificar que el puerto sea `5432`
- Verificar firewall/red

#### Error: "Authentication failed"
- Verificar usuario: debe ser `postgres`
- Verificar contraseña: copiar exactamente desde Supabase
- Verificar que la contraseña no tenga caracteres especiales que necesiten escape

#### Error: "Database does not exist"
- En Supabase, la base de datos siempre se llama `postgres`
- No necesitas crear una base de datos nueva

### Migrar desde MySQL a PostgreSQL (Supabase)

Si tu proyecto usa MySQL y quieres migrar a Supabase:

1. **Cambiar migraciones**: Laravel es compatible, pero revisa:
   - Tipos de datos (ej: `TEXT` en MySQL vs PostgreSQL)
   - Auto-increment (MySQL usa `AUTO_INCREMENT`, PostgreSQL usa `SERIAL` o `BIGSERIAL`)

2. **Laravel maneja esto automáticamente**, pero verifica:
   ```bash
   php artisan migrate:fresh
   ```

3. **Exportar datos** (si tienes datos existentes):
   ```bash
   # Exportar desde MySQL
   mysqldump -u user -p restaurante_db > backup.sql
   
   # Convertir a PostgreSQL (requiere herramienta de conversión)
   # O migrar manualmente
   ```

---

## 🚀 Guía de Despliegue: Railway

### Paso 1: Crear Proyecto

1. Ir a https://railway.app
2. "New Project" → "Deploy from GitHub repo"
3. Seleccionar `SistemaDeGestion`

### Paso 2: Agregar Base de Datos

1. "New" → "Database" → "MySQL" o "PostgreSQL"
2. Railway generará automáticamente las variables de entorno

### Paso 3: Configurar Web Service

1. Railway detectará automáticamente que es PHP
2. Configurar:
   - **Root Directory**: `restaurante-laravel`
   - **Build Command**: `composer install && npm install && npm run build`
   - **Start Command**: `php artisan serve --host=0.0.0.0 --port=$PORT`

### Paso 4: Variables de Entorno

Railway detectará automáticamente las variables de la base de datos. Solo necesitas agregar:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:TU_CLAVE
```

### Paso 5: Deploy

Railway desplegará automáticamente. Luego ejecuta migraciones:

```bash
# En Railway → Deployments → View Logs → Run Command
php artisan migrate --force
php artisan db:seed --force
```

---

## 🚀 Guía de Despliegue: VPS (DigitalOcean/Linode)

### Paso 1: Crear Droplet/VPS

1. Crear cuenta en DigitalOcean/Linode
2. Crear nuevo Droplet/VPS:
   - **OS**: Ubuntu 22.04 LTS
   - **Plan**: $4-6/mes (1GB RAM mínimo)
   - **Region**: Más cercana

### Paso 2: Conectar por SSH

```bash
ssh root@TU_IP_SERVIDOR
```

### Paso 3: Instalar LAMP/LEMP

```bash
# Actualizar sistema
apt update && apt upgrade -y

# Instalar Nginx
apt install nginx -y

# Instalar PHP 8.2 y extensiones
apt install software-properties-common -y
add-apt-repository ppa:ondrej/php -y
apt update
apt install php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath -y

# Instalar MySQL
apt install mysql-server -y

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Instalar Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install -y nodejs
```

### Paso 4: Clonar Proyecto

```bash
cd /var/www
git clone https://github.com/Smansilla98/SistemaDeGestion.git
cd SistemaDeGestion/restaurante-laravel
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

### Paso 5: Configurar Base de Datos

```bash
mysql -u root -p
```

```sql
CREATE DATABASE restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'restaurante_user'@'localhost' IDENTIFIED BY 'password_seguro';
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'restaurante_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Paso 6: Configurar Laravel

```bash
cp .env.example .env
nano .env
```

Configurar:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://TU_DOMINIO.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=restaurante_db
DB_USERNAME=restaurante_user
DB_PASSWORD=password_seguro
```

```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Paso 7: Configurar Nginx

```bash
nano /etc/nginx/sites-available/restaurante
```

```nginx
server {
    listen 80;
    server_name TU_DOMINIO.com www.TU_DOMINIO.com;
    root /var/www/SistemaDeGestion/restaurante-laravel/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
ln -s /etc/nginx/sites-available/restaurante /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

### Paso 8: Configurar Permisos

```bash
chown -R www-data:www-data /var/www/SistemaDeGestion/restaurante-laravel
chmod -R 755 /var/www/SistemaDeGestion/restaurante-laravel
chmod -R 775 /var/www/SistemaDeGestion/restaurante-laravel/storage
chmod -R 775 /var/www/SistemaDeGestion/restaurante-laravel/bootstrap/cache
```

### Paso 9: Configurar SSL (Let's Encrypt)

```bash
apt install certbot python3-certbot-nginx -y
certbot --nginx -d TU_DOMINIO.com -d www.TU_DOMINIO.com
```

### Paso 10: Configurar Supervisor (Opcional - para Queue)

```bash
apt install supervisor -y
nano /etc/supervisor/conf.d/laravel-worker.conf
```

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/SistemaDeGestion/restaurante-laravel/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/SistemaDeGestion/restaurante-laravel/storage/logs/worker.log
```

```bash
supervisorctl reread
supervisorctl update
supervisorctl start laravel-worker:*
```

---

## 🔒 Configuración de Seguridad para Producción

### 1. Variables de Entorno

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

# Cambiar estas en producción
DB_PASSWORD=password_muy_seguro
APP_KEY=base64:clave_generada_segura
```

### 2. Optimizaciones

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 3. Permisos

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 4. Firewall

```bash
ufw allow 22
ufw allow 80
ufw allow 443
ufw enable
```

---

## 📊 Comparación Rápida

| Plataforma | Precio | Facilidad | Escalabilidad | Recomendado Para |
|------------|--------|-----------|---------------|------------------|
| **Render** | Gratis* | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | Pruebas/Desarrollo |
| **Supabase** | Gratis (500MB) | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐ **Recomendado** |
| **Railway** | $5 crédito | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | Proyectos pequeños |
| **VPS** | $4-6/mes | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | Producción |
| **AWS** | Variable | ⭐⭐ | ⭐⭐⭐⭐⭐ | Empresas |
| **DigitalOcean** | $5/mes | ⭐⭐⭐⭐ | ⭐⭐⭐⭐ | Producción |

### Comparación: Supabase vs Railway (Base de Datos)

| Característica | Supabase | Railway |
|----------------|----------|---------|
| **Gratis** | 500MB DB | $5 crédito |
| **Dashboard** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ |
| **Storage** | ✅ Incluido | ❌ No |
| **Realtime** | ✅ Sí | ❌ No |
| **Facilidad** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ |
| **Recomendado** | ⭐ **Sí** | Sí |

---

## 🎯 Recomendación

### Para Base de Datos:
**Supabase** ⭐ - La mejor opción gratuita, fácil de configurar, incluye storage y realtime

### Para Hosting (Aplicación):
- **Render** - Para empezar (gratis, fácil)
- **Railway** - Alternativa a Render
- **VPS (DigitalOcean/Linode)** - Para producción (control total, $4-6/mes)

### Combinación Recomendada:
**Render/Railway (hosting) + Supabase (base de datos)** ⭐⭐⭐⭐⭐
- Render/Railway para la aplicación Laravel
- Supabase para PostgreSQL + Storage
- Lo mejor de ambos mundos: fácil, gratis, escalable

### Para Producción (Grande):
**VPS (DigitalOcean/Linode) + Supabase** o **AWS/GCP/Azure** - Altamente escalable

---

## 📝 Checklist de Despliegue

- [ ] Proyecto en GitHub
- [ ] Base de datos creada
- [ ] Variables de entorno configuradas
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` generado
- [ ] Migraciones ejecutadas
- [ ] Seeders ejecutados (opcional)
- [ ] Storage link creado
- [ ] Permisos configurados
- [ ] SSL configurado (Let's Encrypt)
- [ ] Optimizaciones de cache ejecutadas
- [ ] Firewall configurado
- [ ] Dominio apuntando al servidor

---

## 🆘 Solución de Problemas

### Error 500
- Verificar permisos de `storage/` y `bootstrap/cache/`
- Verificar `.env` configurado correctamente
- Ver logs: `tail -f storage/logs/laravel.log`

### Error de Base de Datos
- Verificar credenciales en `.env`
- Verificar que la base de datos existe
- Verificar conexión: `php artisan tinker` → `DB::connection()->getPdo();`

### Assets no cargan
- Ejecutar `npm run build`
- Verificar que `public/build/` existe
- Limpiar cache: `php artisan cache:clear`

---

**¡Listo para desplegar! 🚀**

