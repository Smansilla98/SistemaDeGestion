# OpenDesign en Patuka

Este directorio contiene el cliente de **OpenDesign** (https://github.com/clawnify/OpenDesign,
commit `a0f8cbb`, licencia MIT, © 2026 Clawnify — ver `LICENSE`), integrado como editor del
módulo Diseño del panel. Se trajo desde la integración hecha para chilinga-admin.

Cambios respecto del original:

- El backend (Hono + Cloudflare D1/R2) se reemplazó por Laravel:
  `App\Http\Controllers\Design\DesignEditorController` sobre `designs`, `design_pages` y `design_assets`,
  separado por organización. `api.ts` usa la sesión del panel y el token CSRF; `config.ts` recibe la
  configuración de la vista Blade (`resources/views/design/editor.blade.php`).
- Rutas bajo `/diseno` (`hooks/use-router.ts`); sin la navegación embebida de Clawnify.
- Interfaz en español, colores de Patuka (negro, blanco y grises), Roboto.
- Pestaña **Marca** (`components/marca-panel.tsx`): paleta, logos, kit de marca, imágenes propias y
  paletas de los emprendedores.
- Pestaña **Canva / IA** (`components/artcraft-panel.tsx`): lleva el diseño a Canva (otra pestaña) o a
  ArtCraft (dentro del editor) y trae el resultado como capa, fondo o reemplazo.
- Miniatura del diseño al guardar (`getThumbnailForPage`).

Se compila con `npm --prefix resources/opendesign ci && npm --prefix resources/opendesign run build`
(lo hace el Dockerfile); la salida va a `public/opendesign`.
