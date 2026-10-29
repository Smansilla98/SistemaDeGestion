# Patuka Espacio Cultural (rama `Patuka`)

Esta rama convierte el sistema en la herramienta de un espacio cultural real: Patuka.
`main` sigue siendo el producto genérico.

## Marca

- **Paleta:** negro y blanco, tomada de los logos.
  - Primario `#111111`, secundario `#000000`, acento `#5c5c5c`.
  - Grises neutros de `#0a0a0a` a `#f7f7f7`.
  - Verde, ámbar y rojo quedan solo para estados (confirmado, en espera, error).
- **Tipografía:** Roboto en todo el sistema, la web pública y la configuración por defecto.
- **Logos en `public/brand/`:**
  - `patuka-logo-blanco.png`: para fondos oscuros (sidebar, web pública).
  - `patuka-logo-negro.png`: para fondos claros (login, tickets y PDF).
  - `patuka-icon.png`.
  - Los íconos de la app (`public/icons/`) y `favicon.ico` salen del mismo logo.
- **Configuración** (`config/app.php`):
  - `APP_LOGO` y `APP_LOGO_LIGHT` permiten reemplazar los logos; si quedan vacíos se usan los de Patuka.
  - Un logo subido en Configuración tiene prioridad.
- **Colores guardados de la marca anterior:** `Branding::palette()` los lee como negro, sin necesidad de migrar datos.

## Web pública (`/`)

- **Talleres:** aparecen los que están en estado *Inscripción abierta* o *En curso*. Cada tarjeta muestra:
  - horarios, profe, fecha de inicio y cuota;
  - los lugares que quedan, o el aviso de lista de espera.
- **Inscripción online** (`/taller/{id}`): el formulario pide nombre, teléfono, email (opcional), comentario y consentimiento de difusión.
  - Busca o crea el contacto como *Alumno* (`ContactService::findOrCreate`).
  - Inscribe con cupo o en lista de espera (`WorkshopService::enroll`).
  - Avisa al equipo con la notificación de nueva inscripción, con la vía «web de …».
  - Protecciones: un campo trampa contra bots y un límite de 6 envíos por minuto.
  - Si la persona ya está inscripta, ve un mensaje claro y no se duplica nada.
- **Agenda:** muestra eventos no cancelados y avisos de tipo *Feria* o *Fecha especial* de los próximos 90 días. Reuniones, recordatorios y entregas quedan internos.
- **Contacto:** se edita en *Configuración → Web pública*: presentación, dirección (con link a Maps), horarios, Instagram, WhatsApp, teléfono y email.
- **Organización que se muestra:** la de `PUBLIC_ORG_ID`; si no está definida, el primer espacio cultural activo.
- **Acceso del equipo:** con el botón «Equipo» → login. Si alguien ya inició sesión, `/` lo lleva al panel.

## Puesta en marcha

```bash
php artisan migrate --force
php artisan patuka:setup --admin-user=patuka --admin-password='********'
# o, para convertir una organización existente:
php artisan patuka:setup --org=2
```

`patuka:setup` se puede correr más de una vez: completa lo que falta y no borra nada. Hace lo siguiente:

- Marca la organización como *Espacio cultural*.
- Habilita todos los módulos: talleres, agenda, contactos, feria, documentos, finanzas, y también barra, caja y stock.
- Aplica la paleta y la tipografía.
- Crea las categorías Música, Teatro, Danza, Artes visuales, Oficios, Shows y Feria.
- Crea la regla de reparto *Talleres 70/30* (Docente 70 / Espacio 30, sobre neto).
- Crea el administrador.

## Variables de entorno

| Variable | Valor en Patuka |
|---|---|
| `APP_NAME`, `APP_BRAND_NAME` | `Patuka` |
| `DEMO_ENTRY_ENABLED` | `false` (por defecto en esta rama: es una instalación propia, no un demo) |
| `PUBLIC_ORG_ID` | ID de la organización Patuka (opcional) |
| `APP_LOGO`, `APP_LOGO_LIGHT` | vacíos (se usan los logos de `public/brand`) |

## Tests

`tests/Feature/Community/PatukaPublicSiteTest.php` cubre:

- qué se publica y qué no;
- la inscripción, la lista de espera y el aviso al equipo;
- los duplicados, los bots y los talleres cerrados o de otra organización;
- la configuración de la web pública;
- `patuka:setup`;
- la lectura de los colores anteriores.

`LandingPageTest` se actualizó para la nueva portada.
