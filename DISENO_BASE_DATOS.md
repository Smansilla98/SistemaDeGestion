# Diseño de Base de Datos - Sistema de Gestión de Restaurante

## Diagrama de Entidad-Relación

### Tablas Principales

#### 1. **restaurants** (Multi-sucursal)
- id (PK)
- name (string)
- address (text, nullable)
- phone (string, nullable)
- email (string, nullable)
- settings (json, nullable) - Configuraciones específicas
- is_active (boolean, default: true)
- created_at, updated_at

#### 2. **users** (Usuarios del sistema)
- id (PK)
- restaurant_id (FK, nullable) - NULL si es super admin
- name (string)
- email (string, unique)
- password (string)
- role (enum: ADMIN, CAJERO, MOZO, COCINA)
- is_active (boolean, default: true)
- last_login_at (timestamp, nullable)
- created_at, updated_at

#### 3. **sectors** (Sectores/Salones)
- id (PK)
- restaurant_id (FK)
- name (string)
- description (text, nullable)
- layout_config (json, nullable) - Configuración del layout
- is_active (boolean, default: true)
- created_at, updated_at

#### 4. **tables** (Mesas)
- id (PK)
- restaurant_id (FK)
- sector_id (FK)
- number (string) - Número de mesa (ej: "Mesa 1", "1", "A1")
- capacity (integer) - Capacidad de personas
- position_x (integer, nullable) - Posición X en layout
- position_y (integer, nullable) - Posición Y en layout
- status (enum: LIBRE, OCUPADA, RESERVADA, CERRADA)
- current_order_id (FK, nullable) - Pedido actual activo
- created_at, updated_at

#### 5. **categories** (Categorías de productos)
- id (PK)
- restaurant_id (FK)
- name (string)
- description (text, nullable)
- display_order (integer, default: 0)
- is_active (boolean, default: true)
- created_at, updated_at

#### 6. **products** (Productos/Menú)
- id (PK)
- restaurant_id (FK)
- category_id (FK)
- name (string)
- description (text, nullable)
- price (decimal 10,2)
- image (string, nullable)
- has_stock (boolean, default: false)
- stock_minimum (integer, default: 0)
- is_active (boolean, default: true)
- created_at, updated_at

#### 7. **product_modifiers** (Modificadores de productos - ej: sin sal, extra queso)
- id (PK)
- product_id (FK)
- name (string)
- price_modifier (decimal 10,2, default: 0) - Puede ser negativo o positivo
- is_active (boolean, default: true)
- created_at, updated_at

#### 8. **stocks** (Stock de productos)
- id (PK)
- restaurant_id (FK)
- product_id (FK)
- quantity (integer)
- created_at, updated_at

#### 9. **stock_movements** (Movimientos de stock - Kardex)
- id (PK)
- restaurant_id (FK)
- product_id (FK)
- user_id (FK) - Usuario que realizó el movimiento
- type (enum: ENTRADA, SALIDA, AJUSTE)
- quantity (integer)
- previous_stock (integer)
- new_stock (integer)
- reason (string, nullable)
- reference (string, nullable) - Referencia externa (ej: order_id)
- created_at, updated_at

#### 10. **orders** (Pedidos)
- id (PK)
- restaurant_id (FK)
- table_id (FK)
- user_id (FK) - Mozo que toma el pedido
- number (string, unique) - Número de pedido (ej: "ORD-2024-001")
- status (enum: ABIERTO, ENVIADO, EN_PREPARACION, LISTO, ENTREGADO, CERRADO, CANCELADO)
- subtotal (decimal 10,2)
- discount (decimal 10,2, default: 0)
- total (decimal 10,2)
- observations (text, nullable) - Observaciones generales del pedido
- sent_at (timestamp, nullable)
- closed_at (timestamp, nullable)
- created_at, updated_at

#### 11. **order_items** (Items del pedido)
- id (PK)
- order_id (FK)
- product_id (FK)
- quantity (integer)
- unit_price (decimal 10,2) - Precio al momento de la venta
- subtotal (decimal 10,2)
- observations (text, nullable) - Observaciones del item
- status (enum: PENDIENTE, EN_PREPARACION, LISTO, ENTREGADO)
- created_at, updated_at

#### 12. **order_item_modifiers** (Modificadores aplicados a items)
- id (PK)
- order_item_id (FK)
- product_modifier_id (FK)
- name (string) - Nombre del modificador (snapshot)
- price_modifier (decimal 10,2) - Precio del modificador (snapshot)
- created_at, updated_at

#### 13. **cash_registers** (Cajas registradoras)
- id (PK)
- restaurant_id (FK)
- name (string)
- is_active (boolean, default: true)
- created_at, updated_at

#### 14. **cash_register_sessions** (Sesiones de caja - Apertura/Cierre)
- id (PK)
- restaurant_id (FK)
- cash_register_id (FK)
- user_id (FK) - Usuario que abre/cierra
- initial_amount (decimal 10,2) - Monto inicial
- final_amount (decimal 10,2, nullable) - Monto final calculado
- expected_amount (decimal 10,2, nullable) - Monto esperado según ventas
- difference (decimal 10,2, nullable) - Diferencia entre final y esperado
- opened_at (timestamp)
- closed_at (timestamp, nullable)
- status (enum: ABIERTA, CERRADA)
- notes (text, nullable)
- created_at, updated_at

#### 15. **payments** (Pagos/Facturación)
- id (PK)
- restaurant_id (FK)
- order_id (FK)
- cash_register_session_id (FK, nullable)
- user_id (FK) - Usuario que procesa el pago
- payment_method (enum: EFECTIVO, DEBITO, CREDITO, TRANSFERENCIA)
- amount (decimal 10,2)
- reference (string, nullable) - Referencia de pago (ej: número de tarjeta parcial)
- notes (text, nullable)
- created_at, updated_at

#### 16. **cash_movements** (Movimientos de caja - Ingresos/Egresos)
- id (PK)
- restaurant_id (FK)
- cash_register_session_id (FK)
- user_id (FK)
- type (enum: INGRESO, EGRESO)
- amount (decimal 10,2)
- description (string)
- reference (string, nullable)
- created_at, updated_at

#### 17. **audit_logs** (Logs de auditoría)
- id (PK)
- restaurant_id (FK, nullable)
- user_id (FK, nullable)
- action (string) - Acción realizada
- model_type (string, nullable) - Modelo afectado
- model_id (integer, nullable) - ID del modelo
- changes (json, nullable) - Cambios realizados
- ip_address (string, nullable)
- user_agent (string, nullable)
- created_at

## Relaciones Principales

- restaurant -> hasMany: users, sectors, tables, categories, products, orders, etc.
- sector -> belongsTo: restaurant | hasMany: tables
- table -> belongsTo: restaurant, sector, currentOrder | hasMany: orders
- category -> belongsTo: restaurant | hasMany: products
- product -> belongsTo: restaurant, category | hasMany: modifiers, orderItems, stocks, stockMovements
- order -> belongsTo: restaurant, table, user | hasMany: items, payments
- order_item -> belongsTo: order, product | hasMany: modifiers
- cash_register_session -> belongsTo: restaurant, cashRegister, user | hasMany: payments, cashMovements

## Índices Recomendados

- users: email (unique), restaurant_id
- tables: restaurant_id, sector_id, status, number
- products: restaurant_id, category_id, is_active
- orders: restaurant_id, table_id, status, number (unique), created_at
- order_items: order_id, product_id, status
- payments: restaurant_id, order_id, created_at
- stock_movements: restaurant_id, product_id, created_at
- audit_logs: restaurant_id, user_id, created_at


