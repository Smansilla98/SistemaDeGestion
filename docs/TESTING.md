# Cómo probar Al Toque

## Requisitos

- PHP 8.2+ con `pdo_mysql`, `mbstring`, `bcmath`, `intl`, `gd`, `zip`.
- **MySQL 8 o MariaDB 10.11** con una base vacía para tests. Las migraciones usan SQL
  propio de MySQL (`ALTER TABLE … MODIFY … ENUM`): **SQLite no sirve** para esta suite.
- Node 20 si vas a compilar assets.

## Base de test

La suite usa `RefreshDatabase`: borra y recrea las tablas. **Nunca** la apuntes a la base
de desarrollo ni a producción. Creá una aparte:

```sql
CREATE DATABASE testing;
CREATE USER 'test'@'127.0.0.1' IDENTIFIED BY 'test';
GRANT ALL ON testing.* TO 'test'@'127.0.0.1';
```

Y pasá la conexión por variables de entorno (pisan al `.env`):

```bash
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3306 \
DB_DATABASE=testing DB_USERNAME=test DB_PASSWORD=test \
php artisan test
```

Con Docker: `docker compose up -d mysql` y usá el puerto `33060`.

## Lo mismo que corre el CI

```bash
vendor/bin/pint --test                                  # formato
vendor/bin/phpstan analyse --memory-limit=2G            # análisis estático (nivel 5 + baseline)
php artisan test                                        # suite (MySQL)
composer audit                                          # dependencias con vulnerabilidades
```

`composer ci` corre formato, análisis y tests juntos.

## Qué cubre la suite

| Área | Archivos |
|---|---|
| API JWT (login, refresh, `me`, RBAC) | `tests/Feature/ApiJwt*` |
| Operación (mesas, caja, dispositivos) | `tests/Feature/ApiOpsEndpointsTest.php` |
| Medios de cobro por restaurante | `tests/Feature/PaymentMethodConfigurationTest.php` |
| Pedidos, numeración, integridad, centavos | `tests/Unit/Order*`, `tests/Domain/*` |
| Stock | `tests/Feature/StockTest.php`, `tests/Unit/StockServiceTest.php` |
| Módulos licenciados | `tests/Feature/ModuleLicenseTest.php` |
| PDF (dompdf) | `tests/Feature/PdfGenerationTest.php` |

## Notas

- Los repositorios PDO (`app/Repositories`) usan la misma conexión que Eloquent
  (`App\Core\Database`), así que participan de las transacciones de los tests.
- Admin y superadmin eligen "demo o módulos" al entrar (`DEMO_ENTRY_ENABLED`). En tests
  de pantallas web, poné `withSession(['entry_mode' => 'demo'])`.
