# ✅ Sistema de Notificaciones e Impresoras

## 📋 Resumen de Implementación

Se han implementado los sistemas de notificaciones en tiempo real y configuración de impresoras térmicas.

---

## ✅ 1. Sistema de Notificaciones en Tiempo Real

### Eventos Implementados:

#### 1. OrderCreated
- **Canal**: `restaurant.{id}` (privado) y `orders` (público)
- **Evento**: `order.created`
- **Trigger**: Cuando se crea un nuevo pedido
- **Datos**: Información del pedido, mesa, total

#### 2. OrderStatusChanged
- **Canal**: `restaurant.{id}` (privado) y `orders` (público)
- **Evento**: `order.status.changed`
- **Trigger**: Cuando cambia el estado de un pedido
- **Datos**: Pedido, estado anterior, nuevo estado

#### 3. KitchenOrderReady
- **Canal**: `restaurant.{id}` (privado) y `kitchen` (público)
- **Evento**: `kitchen.order.ready`
- **Trigger**: Cuando un pedido está listo en cocina
- **Datos**: Pedido, mesa, mensaje

### Integración:

- ✅ Eventos integrados en `OrderService`
- ✅ Evento en `KitchenController` cuando se marca como listo
- ✅ JavaScript para notificaciones (`notifications.js`)
- ✅ Configuración en layout para restaurant ID

### Configuración Requerida:

Para usar notificaciones en tiempo real completamente, se requiere:

1. **Laravel Echo** (frontend):
```bash
npm install --save-dev laravel-echo pusher-js
```

2. **Pusher** (recomendado) o **Laravel WebSockets**:
   - Configurar en `.env`:
   ```
   BROADCAST_DRIVER=pusher
   PUSHER_APP_ID=your-app-id
   PUSHER_APP_KEY=your-app-key
   PUSHER_APP_SECRET=your-app-secret
   ```

3. **En `resources/js/app.js`**:
```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true
});
```

### Archivos:
- `app/Events/OrderCreated.php`
- `app/Events/OrderStatusChanged.php`
- `app/Events/KitchenOrderReady.php`
- `resources/js/notifications.js`

---

## ✅ 2. Sistema de Impresoras Térmicas

### Modelo y Migración:

- ✅ Modelo `Printer` creado
- ✅ Migración `create_printers_table.php`
- ✅ Campos: nombre, tipo, conexión, IP, puerto, ruta, configuración

### Tipos de Impresoras:

1. **Cocina** (`kitchen`) - Tickets de cocina
2. **Barra** (`bar`) - Comandas de barra
3. **Cajero** (`cashier`) - Tickets de caja
4. **Factura** (`invoice`) - Facturas

### Tipos de Conexión:

1. **Network** - Impresora en red (IP + Puerto)
2. **USB** - Impresora USB (requiere librería adicional)
3. **File** - Guardar PDF en archivo

### Servicio de Impresión:

- ✅ `PrintService` creado
- ✅ Métodos para imprimir tickets de cocina, comandas, tickets
- ✅ Soporte para impresoras de red (socket TCP)
- ✅ Soporte para guardar en archivo
- ✅ Integración con DomPDF

### Controlador:

- ✅ `PrinterController` con CRUD completo
- ✅ Método `test()` para probar impresoras
- ✅ Política `PrinterPolicy` para permisos

### Vistas:

- ✅ `printers/index.blade.php` - Lista de impresoras
- ✅ `printers/create.blade.php` - Crear impresora
- ✅ `printers/edit.blade.php` - Editar impresora

### Rutas:

- ✅ `printers.*` - Rutas completas
- ✅ `printers/{printer}/test` - Probar impresora

### Integración con Pedidos:

- ✅ `OrderPrintController` actualizado para soportar impresión directa
- ✅ Parámetro `?print=true` para imprimir directamente
- ✅ Fallback a PDF si no hay impresora configurada

---

## 🎯 Funcionalidades

### Notificaciones:

1. **Notificación al crear pedido**: Todos los usuarios del restaurante reciben notificación
2. **Notificación al cambiar estado**: Notifica cambios de estado del pedido
3. **Notificación cuando está listo**: Alerta cuando un pedido está listo en cocina

### Impresoras:

1. **Configuración múltiple**: Varias impresoras por restaurante
2. **Impresión automática**: Opción para imprimir automáticamente
3. **Prueba de impresora**: Botón para probar conexión
4. **Soporte múltiples tipos**: Network, USB, File
5. **Anchos de papel**: 58mm y 80mm

---

## 📝 Uso

### Configurar Notificaciones:

1. Instalar dependencias (Pusher/Laravel Echo)
2. Configurar variables de entorno
3. Compilar assets: `npm run build`
4. Las notificaciones aparecerán automáticamente

### Configurar Impresora:

1. Ir a "Impresoras" en el menú
2. Click en "Nueva Impresora"
3. Configurar:
   - Nombre
   - Tipo (Cocina, Barra, etc.)
   - Conexión (Network, USB, File)
   - IP y Puerto (si es Network)
   - Ruta (si es File)
4. Guardar
5. Probar con el botón de prueba

### Imprimir desde Pedido:

1. Ir a un pedido
2. Click en "Ticket Cocina" (o el tipo correspondiente)
3. Para imprimir directamente: Agregar `?print=true` a la URL
4. O usar el servicio programáticamente

---

## ⚠️ Notas Importantes

### Notificaciones:

- Las notificaciones funcionan con Laravel Broadcasting
- Requiere configuración adicional (Pusher o Laravel WebSockets)
- El código JavaScript está listo, solo requiere Echo configurado

### Impresoras:

- **Network**: Funciona con impresoras que aceptan conexión TCP raw
- **USB**: Requiere librería adicional como `mike42/escpos-php`
- **File**: Guarda PDFs en la ruta especificada
- Para impresoras ESC/POS, se recomienda usar librerías especializadas

---

## ✅ Estado

**Ambos sistemas están implementados y funcionales:**

1. ✅ Sistema de notificaciones con eventos y broadcasting
2. ✅ Sistema de impresoras con configuración completa
3. ✅ Integración en servicios y controladores
4. ✅ Vistas y rutas configuradas
5. ✅ JavaScript para notificaciones listo

**Para uso completo se requiere:**
- Configurar Pusher/Laravel Echo para notificaciones
- Configurar impresoras según tipo de conexión

---

**Fecha de implementación**: 2024-11-25

