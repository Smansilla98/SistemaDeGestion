# Checklist de tareas (restaurante-laravel)

## ALTA PRIORIDAD

### TASK 1 — Autenticación y middleware de roles ✅
- **CheckRole** en `app/Http/Middleware/CheckRole.php`: restringe por rol (admin, mozo, cocina, cajero, etc.).
- Registrado en `app/Http/Kernel.php` como alias `'role' => \App\Http\Middleware\CheckRole::class`.
- Rutas en `routes/web.php` usan `->middleware('role:ADMIN')`, `->middleware('role:MOZO,ADMIN')`, etc., agrupadas por contexto.
- Tabla `users` con columna `role` (enum/string). No hay tabla `roles` separada.

### TASK 2 — Validaciones en FormRequests (parcial) ✅
- **Creados:** `App\Http\Requests\Auth\RegisterRequest`, `LoginRequest`; `App\Http\Requests\Category\StoreCategoryRequest`, `UpdateCategoryRequest`.
- **Controllers actualizados:** `AuthController` (register, login) y `CategoryController` (store, update) usan estos FormRequests.
- Vistas `auth/register`, `auth/login` y `categories/create`, `categories/edit` ya usan `@error('campo')` y `{{ $message }}`.
- **Pendiente:** Extraer el resto de validaciones inline de otros controllers (Order, Table, Product, User, etc.) siguiendo el mismo patrón: crear `App\Http\Requests\<Entidad>\StoreXRequest` / `UpdateXRequest`, inyectar en el método del controller y usar `$request->validated()`.

### TASK 3 — Migraciones / integridad (revisión) ✅
- Las migraciones existentes ya definen `foreignId()->constrained()->onDelete('cascade')` (o `set null` donde corresponde) en la mayoría de relaciones.
- Tablas principales (`orders`, `tables`, `order_items`, `payments`, etc.) tienen índices en `restaurant_id`, `status`, `created_at`, `table_id` donde aplica.
- **Recomendación:** Revisar cada migración en `database/migrations/` y, si falta, agregar índices en columnas usadas en WHERE frecuentes (ej: `orders.table_session_id`, `payments.table_session_id`) con una migración nueva tipo `add_indexes_for_performance`.

---

## PRIORIDAD MEDIA

### TASK 4 — Refactor lógica a Services ✅
- **TableService** creado: `getConsolidatedReceiptData(Table)` y `processTablePayment(Table, validated, userId)`. Toda la lógica de cierre de mesa y recibo consolidado se movió al service. `TableController::processPayment` y `getConsolidatedReceiptData` delegan en él.

### TASK 5 — Exportación Excel/PDF ✅
- **maatwebsite/excel** instalado. Clases en `app/Exports/`: `OrdersExport`, `ProductsExport`, `SalesExport`.
- Rutas: `reports.sales.export`, `reports.sales.export-pdf`, `reports.products.export`, `reports.orders.export`. Botones en vistas de reportes (Ventas: Excel + PDF + Pedidos Excel; Productos: Exportar catálogo Excel). Vista `reports/sales-pdf.blade.php` para descarga PDF.

### TASK 6 — Paginación y filtros ✅
- **StockController::index**: `->paginate(20)` + filtro por `search`; vista con `{{ $products->links() }}` y formulario de búsqueda con valor persistido.
- **PrinterController::index**: `->paginate(20)`; vista con `{{ $printers->links() }}`.

---

## BAJA PRIORIDAD

### TASK 7 — Notificaciones Laravel ✅
- Migración `create_notifications_table`. Notificaciones: `LowStockNotification`, `OrderCreatedNotification`, `OrderDispatchedNotification` (canal `database`). Envío desde StockService (stock bajo), OrderService (pedido creado), KitchenController (pedido listo). Campana en layout con conteo y dropdown; rutas y vista `notifications/index`.

### TASK 8 — Tests Feature ✅
- **AuthTest**: login con username, redirect por rol, logout. **OrderTest**: listado, crear pedido, validación items. **StockTest**: listado, movimiento SALIDA. **RestaurantFactory** y **UserFactory** actualizados. Ejecutar con `php artisan test`.

### TASK 9 — Scripts y Dockerfile ✅
- **start.sh:** Incluye migrate, storage:link, optimize:clear. Se puede agregar `php artisan config:cache` antes de `serve` en producción.
- **Dockerfile:** Usa PHP 8.2, no copia `.env` (correcto). Comandos de arranque delegados a `start.sh`.
- Revisar `.gitignore` para que `.env` y `.env.production` no estén commiteados.
