# 🚀 Guía de Instalación Local

Esta guía te ayudará a instalar y ejecutar el Sistema de Gestión de Restaurante en tu entorno local.

---

## 📋 Requisitos Previos

- **PHP**: 8.1 o superior
- **Composer**: Última versión
- **MySQL/MariaDB**: 10.4 o superior
- **Node.js**: 16.x o superior (para assets frontend)
- **npm** o **yarn**: Para compilar assets

### Extensiones PHP Requeridas

```bash
php-xml
php-mbstring
php-curl
php-zip
php-gd
php-mysql
php-bcmath
php-dom
php-sockets  # Para impresoras de red (opcional)
```

**Ubuntu/Debian:**
```bash
sudo apt-get install php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-gd php8.3-mysql php8.3-bcmath php8.3-dom php8.3-sockets
```

---

## 🔧 Pasos de Instalación

### 1. Clonar/Copiar el Proyecto

Si ya tienes el proyecto en tu máquina, ve al directorio:

```bash
cd /home/santimansilla-bkp/Escritorio/enst/restaurante-laravel
```

### 2. Instalar Dependencias PHP

```bash
composer install
```

Si encuentras problemas con extensiones faltantes, puedes ignorarlas temporalmente:

```bash
composer install --ignore-platform-req=ext-xml --ignore-platform-req=ext-dom --ignore-platform-req=ext-xmlwriter
```

### 3. Configurar Variables de Entorno

Copia el archivo `.env.example` a `.env`:

```bash
cp .env.example .env
```

Edita el archivo `.env` y configura:

```env
APP_NAME="Sistema de Gestión de Restaurante"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=restaurante_db
DB_USERNAME=root
DB_PASSWORD=tu_password_mysql

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Para notificaciones en tiempo real (opcional)
# BROADCAST_DRIVER=pusher
# PUSHER_APP_ID=
# PUSHER_APP_KEY=
# PUSHER_APP_SECRET=
# PUSHER_APP_CLUSTER=mt1
```

### 4. Generar Key de la Aplicación

```bash
php artisan key:generate
```

### 5. Crear la Base de Datos

Crea la base de datos en MySQL:

```bash
mysql -u root -p
```

```sql
CREATE DATABASE restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

O si tienes contraseña configurada:

```bash
mysql -u root -p -e "CREATE DATABASE restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 6. Ejecutar Migraciones

```bash
php artisan migrate
```

### 7. Ejecutar Seeders (Datos de Prueba)

```bash
php artisan db:seed
```

Esto creará:
- 1 restaurante de ejemplo
- Usuarios de prueba (Admin, Mozo, Cocina, Cajero)
- Sectores y categorías
- Productos de ejemplo
- Mesas de ejemplo

### 8. Crear Enlace Simbólico para Storage

```bash
php artisan storage:link
```

### 9. Instalar y Compilar Assets Frontend (Opcional)

Si quieres usar Vite para desarrollo:

```bash
npm install
npm run dev
```

Para producción:

```bash
npm run build
```

**Nota**: Si no quieres usar Vite, el sistema funciona con Bootstrap 5.3 desde CDN (ya está configurado en las vistas).

---

## 🚀 Ejecutar el Servidor

### Opción 1: Servidor de Desarrollo de Laravel

```bash
php artisan serve
```

El servidor estará disponible en: `http://localhost:8000`

### Opción 2: Con Vite (para desarrollo con hot-reload)

En una terminal:

```bash
php artisan serve
```

En otra terminal:

```bash
npm run dev
```

### Opción 3: Con XAMPP/WAMP/MAMP

Si usas XAMPP, WAMP o MAMP:

1. Coloca el proyecto en `htdocs` (XAMPP/WAMP) o `Applications/MAMP/htdocs` (MAMP)
2. Configura el virtual host (opcional)
3. Accede a: `http://localhost/restaurante-laravel/public`

---

## 🔑 Credenciales de Acceso

Después de ejecutar los seeders, puedes iniciar sesión con:

### Administrador
- **Email**: admin@restaurante.com
- **Password**: admin123
- **Rol**: ADMIN

### Mozo
- **Email**: mozo@restaurante.com
- **Password**: mozo123
- **Rol**: MOZO

### Cocina
- **Email**: cocina@restaurante.com
- **Password**: cocina123
- **Rol**: COCINA

### Cajero
- **Email**: caja@restaurante.com
- **Password**: caja123
- **Rol**: CAJERO

---

## ✅ Verificar Instalación

### 1. Verificar Rutas

```bash
php artisan route:list
```

Deberías ver todas las rutas del sistema.

### 2. Verificar Base de Datos

```bash
php artisan tinker
```

```php
\App\Models\User::count();
\App\Models\Restaurant::count();
\App\Models\Product::count();
exit
```

### 3. Acceder al Sistema

1. Abre tu navegador
2. Ve a: `http://localhost:8000`
3. Inicia sesión con las credenciales de administrador
4. Deberías ver el Dashboard

---

## 🧪 Ejecutar Tests (Opcional)

```bash
php artisan test
```

O con PHPUnit directamente:

```bash
./vendor/bin/phpunit
```

---

## 🔧 Solución de Problemas Comunes

### Error: "Class 'DOMDocument' not found"

Instala la extensión PHP XML:

```bash
sudo apt-get install php8.3-xml php8.3-dom
```

O ignora temporalmente:

```bash
composer install --ignore-platform-req=ext-xml --ignore-platform-req=ext-dom
```

### Error: "SQLSTATE[HY000] [1045] Access denied"

Verifica las credenciales de MySQL en `.env`:

```env
DB_USERNAME=root
DB_PASSWORD=tu_password_correcto
```

### Error: "SQLSTATE[42000] Syntax error or access violation"

Asegúrate de que MySQL/MariaDB esté ejecutándose:

```bash
sudo service mysql start  # Ubuntu/Debian
# o
brew services start mysql  # macOS
```

### Error: "500 Internal Server Error"

1. Verifica los permisos:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache  # Linux
```

2. Limpia la caché:

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

3. Verifica los logs:

```bash
tail -f storage/logs/laravel.log
```

### Error al ejecutar migraciones

Si hay errores de migraciones:

1. Revierte todas las migraciones:

```bash
php artisan migrate:reset
```

2. Ejecuta nuevamente:

```bash
php artisan migrate --seed
```

### Assets no cargan (CSS/JS)

Si usas Vite:

1. Verifica que Vite esté ejecutándose: `npm run dev`
2. O compila para producción: `npm run build`

Si no usas Vite, el sistema usa CDN de Bootstrap, debería funcionar sin problemas.

---

## 📁 Estructura de Directorios Importante

```
restaurante-laravel/
├── app/
│   ├── Http/Controllers/    # Controladores
│   ├── Models/              # Modelos Eloquent
│   ├── Services/            # Lógica de negocio
│   ├── Policies/            # Políticas de autorización
│   ├── Events/              # Eventos
│   └── ...
├── database/
│   ├── migrations/          # Migraciones
│   └── seeders/             # Seeders
├── resources/
│   ├── views/               # Vistas Blade
│   ├── css/                 # Estilos
│   └── js/                  # JavaScript
├── routes/
│   ├── web.php              # Rutas web
│   └── api.php              # Rutas API
├── public/                  # Archivos públicos
├── storage/                 # Archivos de almacenamiento
└── .env                     # Variables de entorno
```

---

## 🎯 Próximos Pasos

1. **Explorar el sistema**: Inicia sesión y navega por las diferentes secciones
2. **Crear datos de prueba**: Crea productos, mesas, etc.
3. **Configurar impresoras**: Ve a "Impresoras" y configura una impresora (opcional)
4. **Configurar notificaciones**: Si quieres notificaciones en tiempo real, configura Pusher (opcional)

---

## 📝 Notas Importantes

- El sistema está configurado para desarrollo local (`APP_ENV=local`, `APP_DEBUG=true`)
- Para producción, cambia `APP_ENV=production` y `APP_DEBUG=false`
- Los seeders crean datos de ejemplo, puedes modificarlos según necesites
- Las contraseñas por defecto son "password" (cámbialas en producción)
- El sistema funciona sin Vite si prefieres usar solo CDN

---

## 🆘 Soporte

Si encuentras problemas:

1. Revisa los logs: `storage/logs/laravel.log`
2. Verifica la documentación: `README.md`, `TAREAS_PENDIENTES.md`
3. Revisa la configuración de `.env`
4. Asegúrate de tener todas las extensiones PHP requeridas

---

**¡Listo para usar!** 🎉

