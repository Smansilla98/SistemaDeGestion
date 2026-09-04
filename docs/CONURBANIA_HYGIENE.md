# Conurbania — higiene CI

## Local

```bash
composer ci              # pint app/ + phpstan + tests Domain
composer pint            # formatea app/
composer analyse         # Larastan nivel 5 + baseline
composer test:parallel
composer migrate:verify  # fresh --seed + reset + migrate + repair
```

## CI

`.github/workflows/ci.yml`: Pint → PHPStan → migrate verify → Domain coverage ≥50% → suite completa.

MySQL 8 con `STRICT_ALL_TABLES`.

## Pendiente real

- Cobertura global Codecov (hoy mínimo solo Domain)
- Quitar columnas decimal legacy cuando el dual-write esté maduro en prod
- Cobro/caja desde mobile (hoy toma pedido operativa)
