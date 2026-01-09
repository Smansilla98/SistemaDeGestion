# ✅ Verificación de Funcionamiento

Este documento lista los puntos críticos a verificar antes de usar el sistema en producción.

---

## 🔍 Checklist de Verificación

### 1. ✅ Configuración Base

- [ ] `.env` configurado correctamente
- [ ] `APP_KEY` generado
- [ ] Base de datos creada y conectada
- [ ] Migraciones ejecutadas sin errores
- [ ] Seeders ejecutados correctamente

### 2. ✅ Autenticación

- [ ] Puedes iniciar sesión con usuarios del seeder
- [ ] Los roles funcionan correctamente (ADMIN, MOZO, COCINA, CAJERO)
- [ ] El middleware de autenticación funciona
- [ ] El middleware de roles funciona

### 3. ✅ Gestión de Mesas

- [ ] Puedes ver la lista de mesas
- [ ] Puedes crear una nueva mesa
- [ ] Puedes editar una mesa
- [ ] Puedes eliminar una mesa
- [ ] El layout visual funciona (drag & drop)
- [ ] Puedes guardar posiciones de mesas

### 4. ✅ Sistema de Pedidos

- [ ] Puedes crear un nuevo pedido
- [ ] Puedes agregar items a un pedido
- [ ] Puedes ver el detalle de un pedido
- [ ] Puedes enviar un pedido a cocina
- [ ] Puedes cerrar un pedido
- [ ] Los totales se calculan correctamente

### 5. ✅ Vista de Cocina

- [ ] La vista de cocina muestra pedidos enviados
- [ ] Puedes actualizar el estado de items
- [ ] Puedes marcar un pedido como listo
- [ ] Los filtros por estado funcionan

### 6. ✅ Caja y Facturación

- [ ] Puedes abrir una sesión de caja
- [ ] Puedes procesar un pago
- [ ] Puedes registrar movimientos de caja
- [ ] Puedes cerrar una sesión de caja
- [ ] El arqueo funciona correctamente

### 7. ✅ Gestión de Productos

- [ ] Puedes ver la lista de productos
- [ ] Puedes crear un producto
- [ ] Puedes editar un producto
- [ ] Puedes eliminar un producto
- [ ] La búsqueda funciona
- [ ] Los filtros por categoría funcionan

### 8. ✅ Gestión de Categorías

- [ ] Puedes ver la lista de categorías
- [ ] Puedes crear una categoría
- [ ] Puedes editar una categoría
- [ ] Puedes eliminar una categoría

### 9. ✅ Control de Stock

- [ ] Puedes ver el stock de productos
- [ ] Puedes registrar movimientos de stock
- [ ] Las alertas de bajo stock funcionan
- [ ] El Kardex funciona

### 10. ✅ Reportes

- [ ] Puedes ver reportes de ventas
- [ ] Puedes ver productos más vendidos
- [ ] Puedes ver ventas por mozo
- [ ] Puedes exportar reportes a Excel

### 11. ✅ Impresión PDF

- [ ] Puedes generar ticket de cocina
- [ ] Puedes generar comanda
- [ ] Puedes generar factura
- [ ] Puedes generar ticket simple
- [ ] Los PDFs se generan correctamente

### 12. ✅ Impresoras (Nuevo)

- [ ] Puedes acceder a la gestión de impresoras (solo ADMIN)
- [ ] Puedes crear una impresora
- [ ] Puedes editar una impresora
- [ ] Puedes probar una impresora
- [ ] La impresión automática funciona (si está configurada)

### 13. ✅ Permisos y Políticas

- [ ] Los usuarios solo ven lo que tienen permisos
- [ ] Los roles limitan correctamente el acceso
- [ ] Las policies funcionan correctamente

### 14. ✅ API REST (Opcional)

- [ ] Puedes autenticarte con Sanctum
- [ ] Los endpoints API responden correctamente
- [ ] Los tokens funcionan

---

## ⚠️ Problemas Conocidos y Soluciones

### 1. OrderService - Dependencia de PrintService

**Problema**: `OrderService` tiene `PrintService` como dependencia, pero no todos los controladores lo necesitan.

**Estado**: ✅ **Resuelto** - La inyección de dependencias de Laravel maneja esto automáticamente. Si `PrintService` no está disponible, Laravel lanzará un error claro.

**Solución si hay problema**: Asegúrate de que `PrintService` esté correctamente registrado (no requiere registro manual, Laravel lo detecta automáticamente).

### 2. Eventos y Broadcasting

**Problema**: Los eventos están configurados pero requieren Pusher/Laravel Echo para funcionar completamente.

**Estado**: ⚠️ **Funcional sin Pusher** - Los eventos se disparan, pero las notificaciones en tiempo real requieren configuración adicional.

**Solución**: Para notificaciones en tiempo real, instala y configura Pusher o Laravel WebSockets.

### 3. Impresoras - Dependencias Externas

**Problema**: Las impresoras de red requieren sockets PHP, USB requiere librerías adicionales.

**Estado**: ✅ **Funcional con limitaciones** - Las impresoras de red funcionan si PHP tiene `sockets` habilitado. USB requiere `mike42/escpos-php` (no instalado).

**Solución**: 
- Para red: Asegúrate de tener `php-sockets` instalado
- Para USB: Instala `composer require mike42/escpos-php`

### 4. Extensiones PHP Faltantes

**Problema**: Algunas extensiones pueden no estar instaladas.

**Estado**: ⚠️ **Varía por sistema**

**Solución**: Revisa la sección "Requisitos Previos" en `GUIA_INSTALACION_LOCAL.md`.

---

## 🧪 Tests de Funcionalidad Básica

Ejecuta estos comandos para verificar:

### 1. Verificar Rutas

```bash
php artisan route:list
```

Deberías ver todas las rutas sin errores.

### 2. Verificar Base de Datos

```bash
php artisan tinker
```

```php
// Verificar usuarios
\App\Models\User::count(); // Debería ser 4

// Verificar restaurante
\App\Models\Restaurant::count(); // Debería ser 1

// Verificar productos
\App\Models\Product::count(); // Debería ser 12

// Verificar mesas
\App\Models\Table::count(); // Debería ser 10

exit
```

### 3. Verificar Servicios

```bash
php artisan tinker
```

```php
// Verificar que OrderService funciona
$service = app(\App\Services\OrderService::class);
echo get_class($service); // Debería mostrar: App\Services\OrderService

// Verificar que PrintService funciona
$printService = app(\App\Services\PrintService::class);
echo get_class($printService); // Debería mostrar: App\Services\PrintService

exit
```

### 4. Verificar Eventos

```bash
php artisan tinker
```

```php
// Verificar que los eventos existen
class_exists('App\Events\OrderCreated'); // true
class_exists('App\Events\OrderStatusChanged'); // true
class_exists('App\Events\KitchenOrderReady'); // true

exit
```

---

## ✅ Correcciones Realizadas

### Error de Sintaxis en OrderPrintController
**Problema**: Había un punto y coma incorrecto que causaba error de parse.
**Estado**: ✅ **Corregido** - El método `kitchenTicket` ahora está correctamente formateado.

---

## 🚨 Errores Comunes y Soluciones

### Error: "Target class [App\Services\PrintService] does not exist"

**Causa**: El servicio no está siendo encontrado.

**Solución**: 
1. Verifica que el archivo existe: `app/Services/PrintService.php`
2. Limpia la caché: `php artisan config:clear && php artisan cache:clear`

### Error: "Class 'DOMDocument' not found"

**Causa**: Extensión PHP XML no instalada.

**Solución**: 
```bash
sudo apt-get install php8.3-xml php8.3-dom
```

### Error: "Call to undefined method"

**Causa**: Método no existe en la clase.

**Solución**: Verifica que el método esté definido en la clase correspondiente.

### Error: "Route [X] not defined"

**Causa**: La ruta no está registrada.

**Solución**: 
1. Limpia la caché de rutas: `php artisan route:clear`
2. Verifica que la ruta esté en `routes/web.php`

### Error: "Permission denied" en storage o cache

**Causa**: Permisos incorrectos.

**Solución**:
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache  # Linux
```

---

## ✅ Checklist Rápido de Inicio

Antes de usar el sistema, verifica:

1. ✅ Base de datos creada y configurada
2. ✅ Migraciones ejecutadas
3. ✅ Seeders ejecutados
4. ✅ `APP_KEY` generado
5. ✅ Servidor ejecutándose (`php artisan serve`)
6. ✅ Puedes iniciar sesión
7. ✅ Puedes ver el dashboard
8. ✅ Puedes crear un pedido básico

---

## 📝 Próximos Pasos Después de Verificación

1. **Configurar producción**: Cambiar `APP_ENV=production` y `APP_DEBUG=false`
2. **Configurar dominio**: Actualizar `APP_URL` en `.env`
3. **Configurar SSL**: Para HTTPS
4. **Configurar email**: Para notificaciones
5. **Configurar backup**: Para base de datos
6. **Configurar monitoreo**: Para logs y errores
7. **Configurar Pusher** (opcional): Para notificaciones en tiempo real

---

## 🎯 Conclusión

El sistema está **funcionalmente completo** y listo para pruebas. Todos los módulos principales están implementados y deberían funcionar correctamente después de una instalación adecuada.

Si encuentras algún problema específico, consulta:
- `GUIA_INSTALACION_LOCAL.md` - Para problemas de instalación
- `TAREAS_PENDIENTES.md` - Para estado del proyecto
- `storage/logs/laravel.log` - Para errores específicos

---

**Última actualización**: 2024-11-25

