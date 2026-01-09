# Estructura del Proyecto - Sistema de Gestión de Restaurante

## 📁 Organización de Carpetas

```
restaurante-laravel/
├── app/
│   ├── Enums/                    # Enumeraciones (OrderStatus, TableStatus, etc.)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/              # Controladores API REST
│   │   │   ├── Auth/             # Autenticación
│   │   │   ├── Table/            # Gestión de mesas
│   │   │   ├── Order/            # Gestión de pedidos
│   │   │   ├── Kitchen/          # Vista de cocina
│   │   │   ├── CashRegister/     # Módulo de caja
│   │   │   ├── Stock/            # Control de stock
│   │   │   ├── Report/           # Reportes y estadísticas
│   │   │   └── Product/           # Gestión de productos
│   │   └── Middleware/
│   │       └── CheckRole.php      # Middleware de roles
│   ├── Models/                   # Modelos Eloquent
│   ├── Policies/                 # Políticas de autorización
│   ├── Repositories/             # Repositorios (si se implementan)
│   ├── Services/                 # Lógica de negocio
│   │   ├── OrderService.php
│   │   ├── StockService.php
│   │   ├── CashRegisterService.php
│   │   └── AuditService.php
│   └── Providers/
├── database/
│   ├── migrations/               # Migraciones de base de datos
│   └── seeders/                 # Seeders con datos de ejemplo
├── resources/
│   ├── views/                   # Vistas Blade
│   │   ├── auth/
│   │   ├── tables/
│   │   ├── orders/
│   │   ├── kitchen/
│   │   ├── cash-register/
│   │   ├── products/
│   │   ├── stock/
│   │   └── reports/
│   ├── css/
│   └── js/
├── routes/
│   ├── web.php                  # Rutas web
│   └── api.php                  # Rutas API REST
├── public/                      # Archivos públicos
└── storage/                     # Archivos de almacenamiento
```

## 🏗️ Arquitectura

### Patrón MVC + Servicios

- **Modelos**: Representan las entidades de la base de datos
- **Vistas**: Templates Blade para la interfaz
- **Controladores**: Manejan las peticiones HTTP
- **Servicios**: Contienen la lógica de negocio compleja
- **Policies**: Controlan los permisos de acceso

### Flujo de Datos

1. **Request** → Middleware (autenticación, roles)
2. **Controller** → Valida datos, llama a Services
3. **Service** → Ejecuta lógica de negocio, interactúa con Models
4. **Model** → Accede a la base de datos
5. **Response** → Vista o JSON

## 🔐 Sistema de Autenticación

- **Autenticación**: Sesión de Laravel
- **Roles**: ADMIN, MOZO, COCINA, CAJERO
- **Permisos**: Implementados con Policies y Gates
- **Middleware**: `CheckRole` para proteger rutas por rol

## 📊 Base de Datos

Ver `DISENO_BASE_DATOS.md` para el diseño completo.

### Tablas Principales

- restaurants
- users
- sectors
- tables
- categories
- products
- orders
- order_items
- stocks
- stock_movements
- cash_registers
- cash_register_sessions
- payments
- cash_movements
- audit_logs

## 🎯 Módulos del Sistema

### 1. Gestión de Mesas
- CRUD de mesas
- Layout visual configurable
- Estados: LIBRE, OCUPADA, RESERVADA, CERRADA

### 2. Toma de Pedidos
- Creación de pedidos
- Agregar items con modificadores
- Estados del pedido
- Cálculo automático de totales

### 3. Cocina/Producción
- Vista exclusiva para cocina
- Actualización de estados de items
- Filtros por sector

### 4. Caja y Facturación
- Apertura/cierre de caja
- Registro de pagos
- Movimientos de caja
- Arqueo

### 5. Control de Stock
- Movimientos de stock
- Alertas de stock mínimo
- Kardex

### 6. Reportes
- Ventas diarias/mensuales
- Productos más vendidos
- Estadísticas por mozo
- Exportación a PDF

## 🔧 Tecnologías

- **Backend**: Laravel 12 (PHP 8.2+)
- **Base de datos**: MySQL/MariaDB
- **Frontend**: Blade + JavaScript (Vue.js opcional)
- **PDF**: DomPDF
- **Autenticación**: Sesión de Laravel

## 📝 Convenciones

- **Nombres**: camelCase para métodos, PascalCase para clases
- **Rutas**: kebab-case (ej: `/cash-register`)
- **Modelos**: Singular (Order, Table)
- **Tablas**: Plural (orders, tables)
- **Servicios**: Sufijo "Service" (OrderService)
- **Policies**: Sufijo "Policy" (OrderPolicy)

