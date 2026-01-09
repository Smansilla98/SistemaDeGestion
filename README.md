# 🍽️ Sistema de Gestión de Restaurante

Sistema completo de gestión gastronómica desarrollado con **Laravel 12**, similar a TapTapChef, orientado a uso real en restaurantes.

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## 🚀 Características Principales

### ✅ Módulos Implementados

- ✅ **Gestión de Mesas**: CRUD completo con layout visual configurable (drag & drop)
- ✅ **Toma de Pedidos**: Sistema completo con modificadores y observaciones
- ✅ **Vista de Cocina**: Panel exclusivo para cocina con actualización de estados
- ✅ **Caja y Facturación**: Apertura/cierre de caja, múltiples métodos de pago
- ✅ **Control de Stock**: Movimientos, alertas de stock mínimo, kardex
- ✅ **Reportes**: Ventas, productos más vendidos, estadísticas (con exportación a Excel)
- ✅ **Multi-sucursal**: Soporte para múltiples restaurantes
- ✅ **Roles y Permisos**: ADMIN, MOZO, COCINA, CAJERO
- ✅ **Auditoría**: Logs de todas las acciones del sistema
- ✅ **Impresión PDF**: Tickets de cocina, comandas, facturas
- ✅ **API REST**: Endpoints básicos con Laravel Sanctum
- ✅ **Notificaciones**: Sistema de eventos para notificaciones en tiempo real
- ✅ **Gestión de Impresoras**: Configuración de impresoras térmicas
- ✅ **Reservas de Mesas**: Sistema de reservas con confirmación

---

## 📋 Requisitos

- **PHP**: 8.2 o superior
- **Composer**: 2.x
- **MySQL**: 5.7+ / MariaDB 10.3+
- **Node.js**: 18+ y NPM (para assets frontend)
- **Extensiones PHP requeridas**:
  - `pdo_mysql`
  - `xml`
  - `dom`
  - `gd`
  - `bcmath`
  - `mbstring`
  - `curl`
  - `zip`
  - `sockets`

---

## 🔧 Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/Smansilla98/SistemaDeGestion.git
cd SistemaDeGestion/restaurante-laravel
```

### 2. Instalar dependencias

```bash
# Dependencias PHP
composer install

# Dependencias JavaScript
npm install
```

### 3. Configurar entorno

```bash
# Copiar archivo de configuración
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate
```

Editar `.env` y configurar la base de datos:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=restaurante_db
DB_USERNAME=user
DB_PASSWORD=password
```

### 4. Crear base de datos

```bash
# Opción 1: Usar el script SQL
mysql -u root -p < scripts/create_user_mysql.sql

# Opción 2: Si MySQL está en Docker
docker exec -i sql-dcac-db-1 mysql -u root -p < scripts/create_user_mysql.sql

# Opción 3: Crear manualmente
mysql -u root -p
CREATE DATABASE restaurante_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'user'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON restaurante_db.* TO 'user'@'localhost';
FLUSH PRIVILEGES;
```

### 5. Ejecutar migraciones y seeders

```bash
php artisan migrate
php artisan db:seed
```

### 6. Compilar assets (opcional para desarrollo)

```bash
# Desarrollo (con hot reload)
npm run dev

# Producción
npm run build
```

### 7. Iniciar servidor

```bash
php artisan serve
```

El sistema estará disponible en: **http://localhost:8000**

---

## 👤 Usuarios de Prueba

Después de ejecutar los seeders, puedes iniciar sesión con:

| Rol | Email | Contraseña |
|-----|-------|------------|
| **Admin** | `admin@restaurante.com` | `admin123` |
| **Mozo** | `mozo@restaurante.com` | `mozo123` |
| **Cocina** | `cocina@restaurante.com` | `cocina123` |
| **Caja** | `caja@restaurante.com` | `caja123` |

---

## 📁 Estructura del Proyecto

```
restaurante-laravel/
├── app/
│   ├── Enums/              # Enumeraciones (OrderStatus, TableStatus, etc.)
│   ├── Events/              # Eventos para notificaciones
│   ├── Exports/             # Exportaciones a Excel
│   ├── Http/
│   │   ├── Controllers/     # Controladores organizados por módulo
│   │   └── Middleware/      # Middleware personalizado
│   ├── Models/              # Modelos Eloquent
│   ├── Observers/           # Observadores de modelos
│   ├── Policies/            # Políticas de autorización
│   ├── Services/            # Lógica de negocio
│   └── Providers/           # Service Providers
├── database/
│   ├── migrations/          # Migraciones de base de datos
│   ├── seeders/             # Seeders con datos de prueba
│   └── factories/           # Factories para testing
├── resources/
│   ├── views/               # Vistas Blade
│   ├── js/                  # JavaScript (Vite)
│   └── css/                 # Estilos CSS
├── routes/
│   ├── web.php              # Rutas web
│   └── api.php              # Rutas API
├── tests/                   # Tests PHPUnit
└── scripts/                 # Scripts de utilidad
```

Ver `ESTRUCTURA_PROYECTO.md` para más detalles.

---

## 🗄️ Base de Datos

### Tablas Principales

- `restaurants` - Restaurantes (multi-sucursal)
- `users` - Usuarios del sistema
- `sectors` - Sectores/Salones
- `tables` - Mesas
- `categories` - Categorías de productos
- `products` - Productos/Menú
- `orders` - Pedidos
- `order_items` - Items del pedido
- `stocks` - Stock de productos
- `cash_registers` - Cajas registradoras
- `cash_register_sessions` - Sesiones de caja
- `payments` - Pagos
- `audit_logs` - Logs de auditoría
- `printers` - Configuración de impresoras
- `table_reservations` - Reservas de mesas

Ver `DISENO_BASE_DATOS.md` para el diseño completo.

---

## 🔐 Roles y Permisos

### ADMIN
- ✅ Acceso completo al sistema
- ✅ Gestión de usuarios, productos, mesas
- ✅ Configuración del restaurante
- ✅ Gestión de impresoras
- ✅ Reportes completos

### MOZO
- ✅ Gestión de mesas
- ✅ Crear y editar pedidos
- ✅ Ver pedidos
- ✅ Gestión de reservas

### COCINA
- ✅ Ver pedidos enviados
- ✅ Actualizar estado de items
- ✅ Marcar pedidos como listos
- ✅ Vista exclusiva de cocina

### CAJERO
- ✅ Abrir/cerrar caja
- ✅ Registrar pagos
- ✅ Ver reportes de ventas
- ✅ Movimientos de caja

---

## 📊 Módulos Detallados

### 1. Gestión de Mesas
- ✅ CRUD completo de mesas
- ✅ Layout visual configurable por sector
- ✅ Drag & drop para reposicionar mesas
- ✅ Estados: LIBRE, OCUPADA, RESERVADA, CERRADA
- ✅ Asignación visual de mesas

### 2. Toma de Pedidos
- ✅ Creación de pedidos por mesa
- ✅ Agregar productos con modificadores
- ✅ Observaciones por item y pedido
- ✅ Estados: ABIERTO, ENVIADO, EN_PREPARACION, LISTO, ENTREGADO, CERRADO
- ✅ Cálculo automático de totales

### 3. Cocina/Producción
- ✅ Vista exclusiva para cocina
- ✅ Actualización de estados en tiempo real
- ✅ Filtros por sector (cocina/barra)
- ✅ Notificaciones de nuevos pedidos
- ✅ Impresión de tickets de cocina

### 4. Caja y Facturación
- ✅ Apertura de caja con monto inicial
- ✅ Registro de pagos (Efectivo, Débito, Crédito, Transferencia)
- ✅ Movimientos de caja (Ingresos/Egresos)
- ✅ Cierre de caja con arqueo
- ✅ Historial de cierres
- ✅ Generación de facturas PDF

### 5. Control de Stock
- ✅ Movimientos de stock (Entrada, Salida, Ajuste)
- ✅ Descuento automático por venta
- ✅ Alertas de stock mínimo
- ✅ Kardex completo
- ✅ Historial de movimientos

### 6. Reportes y Estadísticas
- ✅ Ventas diarias/mensuales
- ✅ Productos más vendidos
- ✅ Ventas por mozo
- ✅ Tiempo promedio por pedido
- ✅ Exportación a PDF
- ✅ Exportación a Excel (requiere `maatwebsite/excel`)

### 7. Impresión
- ✅ Tickets de cocina (PDF)
- ✅ Comandas por sector (PDF)
- ✅ Facturas simples (PDF)
- ✅ Tickets de venta (PDF)
- ✅ Configuración de impresoras térmicas

### 8. API REST
- ✅ Endpoints para Mesas
- ✅ Endpoints para Pedidos
- ✅ Endpoints para Productos
- ✅ Autenticación con Laravel Sanctum
- ✅ Documentación básica

### 9. Notificaciones
- ✅ Eventos de creación de pedidos
- ✅ Eventos de cambio de estado
- ✅ Sistema de broadcasting (Pusher/WebSockets)
- ✅ Notificaciones en tiempo real

---

## 🖨️ Impresión PDF

El sistema incluye generación de PDFs para:
- ✅ Tickets de cocina
- ✅ Comandas por sector
- ✅ Facturas simples
- ✅ Tickets de venta
- ✅ Reportes

Usa **DomPDF** para la generación de PDFs.

---

## 🧪 Testing

```bash
# Ejecutar todos los tests
php artisan test

# Tests específicos
php artisan test --filter OrderTest
php artisan test --filter AuthTest
```

### Tests Implementados

- ✅ Tests de autenticación
- ✅ Tests de pedidos (feature)
- ✅ Tests unitarios de servicios
- ✅ Tests de stock

---

## 📝 Convenciones de Código

- **PSR-12**: Estándar de codificación PHP
- **Nombres**: camelCase para métodos, PascalCase para clases
- **Rutas**: kebab-case
- **Modelos**: Singular (Order, Table)
- **Tablas**: Plural (orders, tables)

---

## 🔄 Flujo de Trabajo Típico

1. **Mozo** crea un pedido para una mesa
2. **Mozo** agrega items al pedido
3. **Mozo** envía el pedido a cocina
4. **Cocina** actualiza el estado de los items
5. **Cocina** marca el pedido como listo
6. **Mozo** entrega el pedido
7. **Cajero** procesa el pago
8. **Cajero** cierra el pedido

---

## 🐳 Docker (Opcional)

Si MySQL está en Docker:

```bash
# Crear usuario y base de datos
docker exec -i sql-dcac-db-1 mysql -u root -p < scripts/create_user_mysql.sql
```

Ver `CREAR_USUARIO_DOCKER.md` para más detalles.

---

## 📚 Documentación Adicional

- `DISENO_BASE_DATOS.md` - Diseño completo de la base de datos
- `ESTRUCTURA_PROYECTO.md` - Estructura y arquitectura del proyecto
- `GUIA_INSTALACION_LOCAL.md` - Guía detallada de instalación local
- `TAREAS_OPCIONALES_COMPLETADAS.md` - Funcionalidades adicionales implementadas
- `NOTIFICACIONES_IMPRESORAS.md` - Sistema de notificaciones e impresoras
- `PROBLEMAS_SETUP.md` - Solución de problemas comunes

---

## 🛠️ Tecnologías Utilizadas

### Backend
- **Laravel 12** - Framework PHP
- **MySQL/MariaDB** - Base de datos
- **DomPDF** - Generación de PDFs
- **Laravel Sanctum** - Autenticación API
- **PHPUnit** - Testing

### Frontend
- **Blade Templates** - Motor de plantillas
- **Bootstrap 5.3** - Framework CSS
- **Bootstrap Icons** - Iconografía
- **Vite** - Build tool
- **Interact.js** - Drag & drop
- **Axios** - Peticiones HTTP

---

## 📦 Paquetes Adicionales (Opcionales)

Para funcionalidades completas, instalar:

```bash
# Exportación a Excel
composer require maatwebsite/excel

# Laravel Sanctum (si no está instalado)
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

---

## 🤝 Contribuir

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

---

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

---

## 👨‍💻 Autor

**Santiago Mansilla**

- GitHub: [@Smansilla98](https://github.com/Smansilla98)

---

## ⚠️ Notas Importantes

- Este sistema está diseñado para uso en producción
- Asegúrate de configurar correctamente las variables de entorno
- Realiza las pruebas necesarias antes de desplegar
- Configura correctamente los permisos de archivos y directorios
- Para producción, usa un servidor web real (Apache/Nginx) en lugar de `php artisan serve`

---

## 🐛 Solución de Problemas

### Error de conexión a MySQL
Ver `SOLUCION_CONEXION_MYSQL.md` y `CREAR_USUARIO_MYSQL.md`

### Extensiones PHP faltantes
Ver `INSTALAR_EXTENSIONES.md` y ejecutar `scripts/install_extensions.sh`

### Problemas de instalación
Ver `PROBLEMAS_SETUP.md` para soluciones comunes

---

**¡Disfruta usando el Sistema de Gestión de Restaurante! 🍽️**
