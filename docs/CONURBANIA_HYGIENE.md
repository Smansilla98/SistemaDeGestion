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

## Mobile nativo

Ver [`docs/MOBILE_PLATFORM.md`](MOBILE_PLATFORM.md) y carpeta `mobile/` (Expo).

```bash
cd mobile && npm ci && npm run typecheck
```

CI job `mobile` corre typecheck en push.
