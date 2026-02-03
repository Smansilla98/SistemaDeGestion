# 📦 MÓDULO DE REGISTRO DE MOVIMIENTOS DE INVENTARIO CON COMPRAS

**Fecha de Implementación:** 2026-02-02  
**Estado:** ✅ Completado  
**Objetivo:** Registrar compras (entradas) con información financiera y de proveedor para análisis de rentabilidad

---

## ✅ IMPLEMENTACIÓN COMPLETA

### 1. Base de Datos

#### Tabla: `suppliers` (Proveedores)
- ✅ `id` (PK)
- ✅ `restaurant_id` (FK)
- ✅ `name` (string) - Nombre del proveedor
- ✅ `contact_name` (string, nullable) - Nombre del contacto
- ✅ `phone` (string, nullable) - Teléfono
- ✅ `email` (string, nullable) - Email
- ✅ `address` (text, nullable) - Dirección
- ✅ `notes` (text, nullable) - Notas
- ✅ `is_active` (boolean) - Estado activo/inactivo
- ✅ `timestamps`

**Migración:** `2026_02_02_000005_create_suppliers_table.php`

#### Tabla: `purchases` (Compras a Proveedores)
- ✅ `id` (PK)
- ✅ `stock_movement_id` (FK) - Relación con movimiento de stock
- ✅ `supplier_id` (FK) - Proveedor
- ✅ `purchase_date` (date) - Fecha de compra
- ✅ `unit_cost` (decimal 10,2) - Costo unitario
- ✅ `total_cost` (decimal 10,2) - Costo total (calculado)
- ✅ `invoice_number` (string, nullable) - Número de factura/remito
- ✅ `notes` (text, nullable) - Notas adicionales
- ✅ `timestamps`

**Migración:** `2026_02_02_000006_create_purchases_table.php`

---

### 2. Modelos

#### `Supplier` (Proveedor)
- ✅ Relación `restaurant()` - Pertenece a un restaurante
- ✅ Relación `purchases()` - Tiene muchas compras
- ✅ Scope `active()` - Solo proveedores activos

#### `Purchase` (Compra)
- ✅ Relación `stockMovement()` - Pertenece a un movimiento de stock
- ✅ Relación `supplier()` - Pertenece a un proveedor
- ✅ Casts para `purchase_date`, `unit_cost`, `total_cost`

#### `StockMovement` (Actualizado)
- ✅ Relación `purchase()` - Tiene una compra (solo para ENTRADAS)
- ✅ Métodos `isEntry()` e `isExit()` - Helpers para verificar tipo

---

### 3. Controlador Mejorado

#### `StockController`
- ✅ Método `createMovement()` - Muestra formulario de registro
- ✅ Método `storeMovement()` mejorado:
  - Validación condicional para ENTRADAS
  - Creación automática de proveedores si se requiere
  - Validación de fecha de compra (no futura)
  - Validación de costo unitario (no negativo)
  - Integración con `StockService` para registrar compras
  - Auditoría de movimientos

**Validaciones Implementadas:**
- ✅ Producto obligatorio
- ✅ Tipo de movimiento obligatorio
- ✅ Cantidad > 0
- ✅ Para ENTRADAS:
  - ✅ Proveedor obligatorio (o crear nuevo)
  - ✅ Costo unitario obligatorio y >= 0
  - ✅ Fecha de compra obligatoria y <= hoy
  - ✅ Número de factura opcional
  - ✅ Notas opcionales

---

### 4. Servicio Actualizado

#### `StockService`
- ✅ Método `recordMovement()` mejorado:
  - Registra movimiento de stock
  - Si es ENTRADA y tiene `purchase_data`, crea registro de compra
  - Calcula automáticamente `total_cost` (cantidad × costo unitario)
  - Actualiza stock del producto

---

### 5. Vistas

#### `stock/create-movement.blade.php` (NUEVA)
- ✅ Formulario completo de registro de movimiento
- ✅ Campos dinámicos que aparecen solo para ENTRADAS:
  - Selección de proveedor existente
  - Botón "Nuevo proveedor" con formulario inline
  - Costo unitario con validación
  - Fecha de compra con validación (no futura)
  - Número de factura/remito
  - Notas de compra
- ✅ Cálculo automático de costo total en tiempo real
- ✅ Validación JavaScript antes de enviar
- ✅ Mensajes de error claros
- ✅ Información contextual en sidebar

#### `stock/movements.blade.php` (MEJORADA)
- ✅ Nueva columna "Proveedor/Costo" que muestra:
  - Nombre del proveedor
  - Costo unitario
  - Costo total
  - Fecha de compra
- ✅ Botón "Registrar Movimiento" en el header
- ✅ Eager loading de relaciones (`purchase.supplier`)

---

### 6. Rutas

- ✅ `GET /stock/movements/create` - Mostrar formulario
- ✅ `POST /stock/movements` - Registrar movimiento (mejorado)

---

## 🎯 FUNCIONALIDADES IMPLEMENTADAS

### Registro de Entrada (Compra)
1. ✅ Seleccionar producto
2. ✅ Seleccionar tipo "ENTRADA"
3. ✅ Ingresar cantidad
4. ✅ **Se muestran campos de compra automáticamente:**
   - Seleccionar proveedor existente O crear nuevo
   - Ingresar costo unitario
   - Seleccionar fecha de compra (no futura)
   - Ingresar número de factura (opcional)
   - Agregar notas (opcional)
5. ✅ El sistema calcula automáticamente el costo total
6. ✅ Se actualiza el stock del producto
7. ✅ Se registra la compra en `purchases`
8. ✅ Se registra el movimiento en `stock_movements`

### Registro de Salida
1. ✅ Seleccionar producto
2. ✅ Seleccionar tipo "SALIDA"
3. ✅ Ingresar cantidad
4. ✅ Los campos de compra se ocultan automáticamente
5. ✅ Se reduce el stock del producto
6. ✅ Se registra el movimiento (sin compra)

### Creación de Proveedor Inline
1. ✅ Botón "Nuevo proveedor" en el formulario
2. ✅ Formulario expandible con campos:
   - Nombre (obligatorio)
   - Contacto (opcional)
   - Teléfono (opcional)
   - Email (opcional)
3. ✅ El proveedor se crea automáticamente al registrar la compra
4. ✅ Auditoría del proveedor creado

---

## 📊 ESTRUCTURA DE DATOS

### Flujo de Datos para ENTRADA:

```
Usuario → Formulario
  ↓
StockController::storeMovement()
  ↓
Validación de datos
  ↓ (si es ENTRADA)
Crear/Seleccionar Proveedor
  ↓
StockService::recordMovement()
  ↓
1. Actualizar Stock
2. Crear StockMovement
3. Crear Purchase (con costo y proveedor)
  ↓
Auditoría
  ↓
Respuesta al usuario
```

---

## ✅ CRITERIOS DE ACEPTACIÓN CUMPLIDOS

- ✅ Puedo registrar una compra de "Quilmes Cerveza" con:
  - Cantidad: 24
  - Costo unitario: 800
  - Proveedor: Distribuidora X (o crear nuevo)
  - Fecha de compra: 01/02/2026
- ✅ El stock aumenta correctamente
- ✅ El movimiento queda visible en el historial
- ✅ Puedo calcular costo total y margen (datos disponibles)

---

## 🔍 VALIDACIONES IMPLEMENTADAS

- ✅ No permite costo unitario negativo
- ✅ No permite cantidad negativa o cero
- ✅ No permite fecha de compra futura
- ✅ Si Tipo = Entrada y no hay proveedor → error claro
- ✅ Mensajes de error amigables
- ✅ Validación tanto en frontend (JavaScript) como backend (Laravel)

---

## 📈 PREPARADO PARA REPORTES FUTUROS

Los datos registrados permiten calcular:

1. **Costo promedio por producto:**
   ```sql
   SELECT AVG(unit_cost) FROM purchases 
   WHERE stock_movement_id IN (
     SELECT id FROM stock_movements WHERE product_id = X
   )
   ```

2. **Margen de rentabilidad por producto:**
   - Precio de venta (en `products.price`)
   - Costo promedio (de `purchases`)
   - Margen = Precio - Costo

3. **Rentabilidad por período:**
   - Compras en un rango de fechas
   - Ventas en el mismo período
   - Comparación de costos vs ingresos

4. **Comparativa de proveedores:**
   - Costo promedio por proveedor
   - Cantidad comprada por proveedor
   - Análisis de precios

---

## 📝 ARCHIVOS CREADOS/MODIFICADOS

### Nuevos Archivos:
- ✅ `database/migrations/2026_02_02_000005_create_suppliers_table.php`
- ✅ `database/migrations/2026_02_02_000006_create_purchases_table.php`
- ✅ `app/Models/Supplier.php`
- ✅ `app/Models/Purchase.php`
- ✅ `resources/views/stock/create-movement.blade.php`

### Archivos Modificados:
- ✅ `app/Models/StockMovement.php` - Agregada relación `purchase()`
- ✅ `app/Services/StockService.php` - Lógica de compras
- ✅ `app/Http/Controllers/Stock/StockController.php` - Validaciones y creación
- ✅ `resources/views/stock/movements.blade.php` - Columna de compras
- ✅ `routes/web.php` - Ruta para crear movimiento

---

## 🚀 PRÓXIMOS PASOS SUGERIDOS

1. **Reportes de Rentabilidad:**
   - Vista de costo promedio por producto
   - Cálculo de margen de ganancia
   - Análisis por período

2. **Gestión de Proveedores:**
   - CRUD completo de proveedores
   - Historial de compras por proveedor
   - Comparativa de precios

3. **Alertas de Costos:**
   - Notificación cuando el costo aumenta significativamente
   - Comparación de costos entre proveedores

4. **Exportación:**
   - Exportar historial de compras a Excel/PDF
   - Reportes de rentabilidad exportables

---

**✅ Módulo completamente funcional y listo para uso en producción.**

