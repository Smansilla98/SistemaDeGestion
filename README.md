# Sistema de gestión de restaurante (Laravel)

Aplicación web para operación de restaurante: mesas, pedidos, cocina, caja, stock, reportes y permisos por rol. El backend sigue evolucionando hacia una **arquitectura por capas** (controladores delgados, servicios de dominio, repositorios PDO para la API REST, validación en Form Requests).

## Arquitectura

| Capa | Ubicación | Responsabilidad |
|------|-----------|-----------------|
| **Controladores HTTP (API)** | `app/Controllers/Api/` | Request/response JSON, autorización (`Gate` / policies), delegación al servicio |
| **Controladores web (legado gradual)** | `app/Http/Controllers/` | Vistas Blade, Livewire; se documentan puntos de entrada hacia la nueva API |
| **Servicios** | `app/Services/` | Reglas de negocio; `ProductService`, `UserService`, `ClientService`, `OrderRestService` orquestan repositorios y, en pedidos, reutilizan `OrderService` / Eloquent para no romper observers y notificaciones |
| **Repositorios** | `app/Repositories/` | Acceso a datos con **PDO** y **sentencias preparadas** |
| **Modelos** | `app/Models/` | Entidades Eloquent (persistencia web y compatibilidad) |
| **Requests** | `app/Requests/Api/` | Validación y saneo de entrada API |
| **Núcleo** | `app/Core/` | `Database` (singleton PDO), `Logger`, `Container` (DI mínima), `ApiRouter` (tabla método+path) |

```
routes/api.php
    → middleware auth:sanctum
    → ApiRouter::sharedRestRoutes()   (products, orders, clients)
    → role:ADMIN,GERENTE → ApiRouter::adminUserRestRoutes()
```

### Seguridad

- Consultas parametrizadas en repositorios (mitigación de inyección SQL).
- Contraseñas de usuarios API: `password_hash` / verificación compatible con el stack Laravel.
- Validación estricta en `app/Requests/Api/*`.
- Sanitización en servicios (`strip_tags`, `filter_var` en emails donde aplica).

## Requisitos

- PHP 8.2+ con extensiones habituales de Laravel (`mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`)
- Composer 2+
- Node.js 18+ y npm
- MySQL 8 / MariaDB

## Instalación local

1. `git clone <repo> && cd restaurante-laravel`
2. `composer install`
3. `npm install`
4. `cp .env.example .env` (o copiar desde tu plantilla; configurar `APP_KEY`, DB, etc.)
5. `php artisan key:generate`
6. Configurar base de datos en `.env`
7. `php artisan migrate --seed`
8. En desarrollo: `php artisan serve` y en otra terminal `npm run dev`

## Docker

Servicios: **nginx** (puerto 8080), **PHP-FPM** (`app`), **MySQL 8**.

```bash
docker compose up -d --build
```

Dentro del contenedor `app` (o en el host con la misma `.env`):

```bash
composer install
cp .env.example .env   # ajustar DB_HOST=mysql, DB_DATABASE=restaurante, etc.
php artisan key:generate
php artisan migrate --seed
```

- Aplicación: `http://localhost:8080`
- MySQL expuesto en el host: puerto `33060` (usuario `laravel` / contraseña `secret` según `docker-compose.yml`).

Variables útiles en compose (ya definidas en `docker-compose.yml` para el servicio `app`):

- `DB_HOST=mysql`
- `DB_DATABASE=restaurante`
- `DB_USERNAME=laravel`
- `DB_PASSWORD=secret`

## API REST (JSON)

Autenticación: **Laravel Sanctum** (token Bearer o cookie según configuración SPA).

Cabecera recomendada: `Accept: application/json`.

### Recursos principales (prefijo `/api`)

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/products` | Lista productos del restaurante |
| GET | `/api/products/{id}` | Detalle |
| POST | `/api/products` | Alta (políticas: rol con permiso de creación) |
| PUT/PATCH | `/api/products/{id}` | Actualización |
| DELETE | `/api/products/{id}` | Baja (no permite si hay ítems en pedidos) |
| GET/POST | `/api/orders` | Lista / crear (crear usa `OrderService` interno) |
| GET/PUT/PATCH/DELETE | `/api/orders/{id}` | CRUD acotado por policies |
| GET/POST | `/api/clients` | Clientes CRM (`clients`); requiere migración `2026_03_29_000001` |
| GET/PUT/PATCH/DELETE | `/api/clients/{id}` | |
| GET/POST | `/api/users` | Solo **ADMIN** y **GERENTE** (middleware `role`) |
| GET/PUT/PATCH/DELETE | `/api/users/{id}` | |

Respuesta típica: `{ "success": true, "data": ... }` o errores de validación 422.

### Compatibilidad `/api/v1/*`

Se mantienen rutas anteriores (`tables`, `orders` parcial, `products` lectura extendida) bajo `/api/v1/...`.

### Usuario actual

- `GET /api/user` (Sanctum)

## Logging

- Archivo dedicado: `storage/logs/app.log` (clase `App\Core\Logger`).
- Laravel sigue escribiendo en `storage/logs/laravel.log`.

## Tests

- Tests de la capa nueva (sin bootstrap completo de BD en algunos casos):  
  `./vendor/bin/phpunit tests/Unit/ProductServiceTest.php tests/Unit/UserServiceTest.php`
- Suite completa: `php artisan test` (requiere extensión PDO adecuada y base `testing` configurada en `.env` / `phpunit.xml`).

Se añadió `tests/CreatesApplication.php` para cumplir el contrato de `Illuminate\Foundation\Testing\TestCase`.

## Calidad de código

- Formato **PSR-12** con Laravel Pint: `./vendor/bin/pint`

## Flujo operativo (recordatorio)

1. Se toma el pedido  
2. Se entrega el producto  
3. Se cierra la mesa  

## Screenshots

Añadí la carpeta `docs/screenshots/` para que puedas versionar capturas (dashboard, pedidos, caja, respuestas de la API en Postman/Insomnia). Sustituí los placeholders por imágenes reales cuando quieras publicar la documentación.

## Solución de problemas

- **500 al iniciar**: `.env`, permisos de `storage/` y `bootstrap/cache/`, `php artisan key:generate`.
- **API 401**: token Sanctum inválido o sesión no aplicable al grupo `api`.
- **Migración `clients`**: ejecutar `php artisan migrate` tras actualizar el código.

---

Si cambian reglas de roles o flujos de pedido, actualizá policies (`app/Policies`) y este README para mantener coherencia entre web y API.
