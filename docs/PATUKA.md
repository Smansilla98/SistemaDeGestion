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

## Panel pensado para el centro cultural

- **Menú:** Inicio → *Espacio cultural* (Agenda, Talleres, Eventos y shows, Actividades recurrentes) → *Comunidad* (Contactos, Formularios, Comunicaciones, Guía de comunicación, Diseño, Mensajes) → *Feria* → *Barra* (Mesas, Pedidos, Caja, Carta, Menús especiales, Stock) → *Administración* (Finanzas, Documentos, Usuarios y roles, Organización, Sedes, Configuración y web).
- **Inicio («Hoy en Patuka»):** lo primero es la **agenda de la semana**: clases, eventos, etapas, actividades, entregas y avisos de los próximos 7 días, agrupados por día. Después vienen:
  - el resumen cultural: talleres activos, inscriptos, lista de espera y eventos de los próximos 14 días;
  - las últimas inscripciones.
- **La barra queda al final, plegada,** en una sola línea: mesas ocupadas, pedidos en curso y caja abierta o cerrada. Para quien solo usa la barra (mozo, caja, cocina) aparece desplegada.

## Nodo: oferta de cada ciclo

En *Feria → Ciclos de pedidos* cada ciclo carga antes **qué productos ofrece**:

- Por producto se marca si entra, el **precio del ciclo** (puede diferir del de lista) y un **cupo** opcional (total que se puede pedir entre todos).
- Los pedidos solo se arman con esa oferta; si se pasa del cupo, avisa cuánto queda.
- Al crear un ciclo nuevo se puede **copiar la oferta del anterior** (viene elegido por defecto).
- No se puede sacar de la oferta un producto que ya tiene pedidos.

## Menús especiales (barra)

*Barra → Menús especiales* (`/menus-especiales`) arma una carta para una fecha: fecha patria, show, feria.

- **Fechas:** desde / hasta (inclusive). No se pueden superponer dos menús.
- **Productos:** se eligen de la carta, con **precio del menú** opcional (vacío = precio de carta) y **cantidad prevista**.
- **Exclusivo:** si se marca, mientras dura sólo se pueden vender sus productos (Mesas, Nuevo pedido, Caja y la toma de pedidos del celular muestran sólo esos, y el sistema rechaza otros). Si no, la carta sigue completa y los del menú usan su precio.
- **Stock para lo previsto:** con las recetas (insumos) o el stock del producto calcula qué hace falta, qué hay y qué falta comprar.
- **Agenda:** opcionalmente crea el aviso «Menú especial: …» como fecha especial, para que todo el equipo lo vea.
- Mientras rige, Mesas y Nuevo pedido muestran un aviso con el nombre del menú.
- Se puede copiar un menú anterior al crear uno nuevo.

## Guía de comunicación

*Comunidad → Guía de comunicación* (`/guia-de-comunicacion`) es el manual interno de marketing: qué publicar, cómo, cuándo y con qué formato.

- **Guía base:** `patuka:setup` la carga (o el botón *Cargar la guía base*). Diez secciones: para qué sirve, pilares de contenido, semana tipo, cómo escribir, formatos y medidas, fotos y video, profes y artistas, emprendedores, WhatsApp y checklist antes de publicar.
- **Por público:** cada sección es para todos, equipo, profes y artistas o emprendedores; cada rol ve lo suyo.
- **Edición:** quien tiene `guide.update` (gestión, coordinación, marketing) agrega, edita, ordena y borra secciones. El contenido usa formato simple (negrita, listas, casillas, tablas) y puede sugerir una plantilla de *Diseño*.
- *Restaurar secciones base que falten* vuelve a agregar las que se borraron sin tocar las editadas.

## Diseño (editor gráfico)

*Comunidad → Diseño* (`/diseno`) abre el editor OpenDesign (MIT), traído de chilinga-admin y adaptado:

- **Plantillas de Patuka:** flyer de taller, historia de show, post de feria, agenda de la semana, afiche A4, banner web y frase. Usan el logo de la organización.
- **Marca:** paleta, logos (blanco, negro, isotipo), kit de marca compartido, imágenes propias y las paletas de los emprendedores (cada emprendedor ve la suya).
- **Canva / IA:** descarga la página o lo seleccionado, abre Canva (en otra pestaña: Canva no se deja mostrar dentro de otra web) o ArtCraft (IA, dentro del editor), y trae el resultado como capa, fondo o reemplazo.
- **Permisos:** `design.view` (usar el editor; profes, artistas, emprendedores, marketing, coordinación) y `design.kit` (ver y editar los diseños de todos y manejar el kit; gestión, coordinación y marketing).
- Las imágenes se guardan en `storage/app/design/{organización}` y se sirven por `/diseno/medios/...` sólo a la misma organización.
- El Dockerfile compila el editor (`resources/opendesign` → `public/opendesign`).

## Registro con rol

En `/register` cada persona elige cómo participa. Puede elegir cualquier rol menos Administrador o Superadministrador.

- **Comunidad** (Alumno, Profesor, Artista, Emprendedor): la cuenta queda activa al instante.
  - Se crea o se vincula su contacto con la cuenta, así el profe ve sus talleres y el alumno sus clases.
  - Se avisa a la administración.
- **Emprendedor:** además se crea (o se vincula) su ficha de emprendedor con el nombre del emprendimiento, así ve su stock, consignaciones y manual de marca desde el primer ingreso.
- **Equipo** (Coordinador, Gerente, Encargado, Marketing, Inventario, Cajero, Mozo, Cocina, Supervisor): la cuenta queda **pendiente**.
  - Estos roles dan acceso a caja, contactos o finanzas, así que nadie entra sin aprobación.
  - Administración y gerencia reciben el aviso «Cuenta para aprobar». Para aprobarla, hay que marcarla como activa en *Usuarios*.
  - Si la persona intenta ingresar antes, ve un mensaje claro.
- **La migración `2026_10_29_000002`** suma los roles del espacio al rol propio de la cuenta (`users.role`); antes solo existían como membresías.
- **Desde *Usuarios*,** la administración también puede asignar estos roles, con su nombre en castellano.

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

## Railway

**Base de datos:** agregá al proyecto un servicio **MySQL**, desde *New → Database → Add MySQL*.

- El sistema está hecho y probado sobre MySQL 8 (utf8mb4).
- No uses PostgreSQL: hay migraciones y reparaciones con sintaxis propia de MySQL.
- El servicio MySQL trae su propio volumen persistente.

**Archivos:** documentos, imágenes del editor de Diseño e importaciones se guardan en `storage/app`. El contenedor se recrea en cada deploy, así que agregá un **volumen** al servicio web montado en `/var/www/html/storage/app` (*Settings → Volumes*).

**Variables del servicio web.** Las de la base se pueden referenciar desde el servicio MySQL. Si no las cargás, `docker/patuka-boot.sh` toma las `MYSQL*` del servicio MySQL.

```
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
APP_KEY=base64:...            # php artisan key:generate --show (una sola vez)
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<tu-dominio>
APP_NAME=Patuka
APP_BRAND_NAME=Patuka
QUEUE_CONNECTION=database
PATUKA_ADMIN_USER=patuka
PATUKA_ADMIN_PASSWORD=<una clave fuerte>
```

**Primera ejecución.** En cada arranque, el contenedor (`docker/entrypoint-railway.sh`, que es el que usa el Dockerfile) hace lo siguiente:

1. Espera la base de datos.
2. Corre las migraciones.
3. Ejecuta `patuka:setup --first-run`. Si todavía no hay un espacio cultural, crea «Patuka» con módulos, marca, categorías, reparto 70/30 y el admin de `PATUKA_ADMIN_*`. Si ya existe, no lo toca: solo crea el admin si falta.
4. Cachea la configuración y las rutas.
5. Levanta el worker de cola (avisos push y notificaciones) y el scheduler (recordatorios y sincronización de formularios). Se apagan con `QUEUE_WORKER_ENABLED=0` y `SCHEDULER_ENABLED=0`, respectivamente.

`start.sh` hace lo mismo cuando se arranca sin Docker.

**Después del primer deploy:**

- Ingresá con el usuario admin.
- Cambiá la contraseña.
- Completá *Configuración → Web pública*.
- Cargá los talleres en estado *Inscripción abierta*.
- Podés borrar `PATUKA_ADMIN_PASSWORD` de las variables.

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
