# Conurbania — Design tokens

Fuente de verdad: `conurbania.css` (clase raíz `.conurbania-app`).

## Colores

| Token | Uso |
|-------|-----|
| `--t950` … `--t50` | Teal de marca (fondos oscuros, acentos, links activos) |
| `--g900` … `--g50` | Escala neutra (texto, bordes, fondos claros) |
| `--green-*` | Éxito / libre / ok |
| `--amber-*` | Alerta / pendiente |
| `--red-*` | Error / stock bajo / peligro |
| `--blue-*` | Info |

Preferí **tokens** (`var(--t500)`, clases `.bg-green`, `.badge-amber`) sobre utilidades Bootstrap sueltas (`text-white` + `bg-success`) en superficies nuevas.

## Radios y tipografía

| Token | Uso |
|-------|-----|
| `--rsm` / `--rmd` / `--rlg` / `--rxl` | 6 / 8 / 12 / 16 px |
| `--font` | Outfit (UI) |
| `--mono` | DM Mono (montos, códigos) |

## Componentes Blade

| Componente | Reemplaza |
|------------|-----------|
| `<x-card>` | `.sc` / `.sg` ad-hoc |
| `<x-badge>` | badges de estado sueltos |
| `<x-button>` | botones inconsistentes |
| `<x-modal>` | formularios en SweetAlert2 |
| `<x-spinner>` | loadings por vista |
| `<x-brand-logo>` | logo de marca / fallback genérico |

## Branding

| Variable | Uso |
|----------|-----|
| `APP_BRAND_NAME` | Nombre visible (sidebar, login). Default: `APP_NAME` |
| `APP_LOGO` | Ruta relativa a `public/` (ej. `logo.png`). Vacío = ícono + nombre genérico |

Prioridad del logo: settings del restaurante → `APP_LOGO` → fallback genérico.

## Mobile

Layout `layouts/mobile.blade.php` se mantiene **separado** de `app.blade.php`: bottom nav + viewport móvil + flujo PWA. Comparte tokens vía Vite (`app.css` + `mobile.css`).

## Archivos CSS

| Archivo | Contenido |
|---------|-----------|
| `conurbania.css` | Tokens + shell Conurbania (`.sidebar`, `.sc`, `.btn-p`, …) |
| `components/theme-fallbacks.css` | Defaults de `--conurbania-*` / `--mosaic-*` |
| `components/layout-base.css` | Reset body / fuentes del layout web |
| `components/sidebar.css` | Nav legacy `nova-sidebar*` |
| `components/topbar.css` | Header / main `nova-header*`, `nova-main` |
| `components/mosaic-ui.css` | Cards, botones, alerts, tablas, forms |
| `components/overlay.css` | Overlay móvil, scrollbar, modales |
| `components/layout-responsive.css` | Safe-area + breakpoints + touch |
| `components/mobile.css` | Shell mobile V2 (`.m-app`) |
| `components/spinner.css` | Loading compartido |

En `layouts/app.blade.php` solo quedan las **variables `:root` dinámicas** (colores/fuentes del restaurante). No volver a pegar bloques CSS grandes en Blade.

## Interactividad mobile

Patrón estándar: **Blade + JS** (Vite).  
`toma-pedido` (Livewire) se mantiene como excepción hasta migrarlo; no mezclar Livewire nuevo en `mobile/pedidos` ni `mobile/caja` sin unificar primero.

## Assets JS

Entrada única: `resources/js/app.js` (Vite → `public/build`).  
No agregar scripts nuevos en `public/js/`; los legacy ahí están deprecados.
