# Pruebas en el navegador (Playwright)

Prueban lo que los tests de PHP no ven: que el login entre en la pantalla, que nada se
corra de costado en el celular, que el menú abra, que no haya errores de JavaScript.

Usan una base sembrada con `DatabaseSeeder` (usuario `admin` / `admin123`) y levantan solas
`php artisan serve`. Las dependencias van aparte del `package.json` raíz para no tocar el
lockfile del deploy.

```bash
# base vacía y sembrada
DB_DATABASE=restaurante_e2e php artisan migrate:fresh --seed --force

cd tests/e2e
npm ci
npx playwright install chromium          # o usar el del sistema: E2E_CHROMIUM=/snap/bin/chromium
DB_DATABASE=restaurante_e2e DEMO_ENTRY_ENABLED=false npx playwright test
```

`base.spec.js` es del producto base; cada rama suma su propio archivo (por ejemplo
`patuka.spec.js`) para no chocar al mezclar.
