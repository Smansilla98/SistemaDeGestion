# Restaurante Laravel - Guia de Implementacion

Este documento describe una implementacion recomendada para ejecutar el sistema de forma estable en desarrollo y produccion.

## 1) Requisitos

- PHP 8.2+ con extensiones comunes de Laravel (`mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`)
- Composer 2+
- Node.js 18+ y npm
- MySQL/MariaDB

## 2) Instalacion inicial

1. Clonar el proyecto y entrar a la carpeta:
   - `git clone <repo>`
   - `cd restaurante-laravel`
2. Instalar dependencias backend:
   - `composer install`
3. Instalar dependencias frontend:
   - `npm install`
4. Crear archivo de entorno:
   - `cp .env.example .env`
5. Generar clave de aplicacion:
   - `php artisan key:generate`
6. Configurar base de datos en `.env`:
   - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
7. Ejecutar migraciones y seeders:
   - `php artisan migrate --seed`

## 3) Ejecucion en desarrollo

Abrir dos terminales:

- Terminal A:
  - `php artisan serve`
- Terminal B:
  - `npm run dev`

Acceder a la URL local mostrada por Laravel.

## 4) Flujo funcional recomendado (pedidos)

El flujo operativo simplificado definido actualmente es:

1. **Se toma el pedido**
2. **Se entrega el producto**
3. **Se cierra la mesa**

Notas:

- Los estados tecnicos intermedios del sistema pueden existir por compatibilidad, pero en interfaz se muestran agrupados bajo los 3 pasos anteriores.
- El cambio de estado de pedido desde la vista operativa pasa directamente a entregado.

## 5) Caja: eliminacion de movimientos

- Solo usuarios con rol **ADMIN** pueden eliminar movimientos manuales de caja.
- La accion esta disponible en:
  - sesion de caja
  - reporte de ventas (detalle de movimientos por caja)
- Se elimina con confirmacion y envio `DELETE`.

## 6) Verificaciones post implementacion

Ejecutar antes de desplegar:

- `php artisan optimize:clear`
- `php artisan migrate --force` (solo en servidor)
- `php artisan test`
- `npm run build`

Pruebas funcionales minimas:

1. Crear pedido y validar que aparezca como **Se toma el pedido**.
2. Marcar pedido como entregado y validar **Se entrega el producto**.
3. Cerrar pedido/mesa y validar **Se cierra la mesa**.
4. Como ADMIN, eliminar un movimiento manual de caja y verificar mensaje de exito.

## 7) Despliegue (produccion)

Secuencia sugerida:

1. `composer install --no-dev --optimize-autoloader`
2. `npm ci && npm run build`
3. `php artisan migrate --force`
4. `php artisan config:cache`
5. `php artisan route:cache`
6. `php artisan view:cache`

Permisos recomendados:

- `storage/` y `bootstrap/cache/` con permisos de escritura para el usuario del servidor web.

## 8) Solucion de problemas comunes

- **Error 500 al iniciar**: revisar `.env`, credenciales DB y `php artisan key:generate`.
- **Assets sin estilos**: ejecutar `npm run dev` (local) o `npm run build` (produccion).
- **No aplican cambios de DB**: confirmar migraciones con `php artisan migrate:status`.
- **Cache desactualizada**: `php artisan optimize:clear`.

---

Si se modifica el flujo de estados o reglas de roles, actualizar este README para mantener la operacion consistente.
