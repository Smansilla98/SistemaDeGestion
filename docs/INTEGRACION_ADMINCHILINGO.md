# Integración SistemaDeGestion ⇐ adminChilingo

Cómo SistemaDeGestion pasa de sistema gastronómico a plataforma modular para espacios culturales,
escuelas, talleres, emprendedores, mercados territoriales, nodos agroecológicos y eventos,
aprovechando lo que ya resolvió adminChilingo.

## Fase A — Auditoría comparativa

### Plataforma

| Aspecto | SistemaDeGestion (`main`) | adminChilingo (`main`) |
|---|---|---|
| Laravel / PHP | Laravel 10.48, PHP ^8.2 | Laravel 12, PHP ^8.2 |
| Composer relevantes | sanctum 3, firebase/php-jwt (API móvil), dompdf, maatwebsite/excel, livewire 4 | sanctum 4, **spatie/laravel-permission 7**, maatwebsite/excel, twilio, resend |
| Front | Blade + Bootstrap 5 + Tailwind 4 (Vite), Livewire en móvil | Blade + Bootstrap 5 + Tailwind 4, React/Vite (editor Diseño), Chart.js, VexFlow |
| Arquitectura | Controllers + `app/Services` + `app/Domain` (Money, Orders), policies por modelo | Controllers + `app/Domain/*` (servicios por dominio) |
| Multi-organización | `restaurants` = tenant; sucursales con `parent_id`; `BranchAccess` + sesión | Una sola escuela; alcance por sede/bloque (`Asignacion`) |
| Autenticación | Sesión web (username) + JWT/refresh para la app | Sesión web + Sanctum |
| Roles | Columna `users.role` (ENUM: SUPERADMIN…ENCARGADO) + `role_permissions`/`user_permissions` + `config/permissions.php` | Spatie + `Persona`/`Asignacion` con rol contextual por sede/bloque |
| Módulos | `config/sellable_modules.php` + `EnsureLicensedModule` por restaurante | `modulo:` middleware + visibilidad de menú |
| Dinero | Centavos (`Money`, `*_cents`) en pedidos/pagos | Decimales en cuotas/pagos |
| Notificaciones | Tabla `notifications` de Laravel + campana; push Expo; avisos "pedido listo" en caché | `NotificacionService` idempotente (clave de negocio) con canales interna/push/mail/WhatsApp |
| Tests | Pest, 133 tests (MySQL) | PHPUnit, 291 tests (MySQL) |
| Docs | `docs/` (móvil, testing, higiene Conurbania) | `docs/` extensa (deploy, migraciones, roles) |

**Conclusión de compatibilidad:** no se puede compartir código en tiempo de ejecución ni copiar
clases tal cual. Distinta versión de Laravel, distinto sistema de roles (Spatie vs. columna
propia) y distinto modelo de tenant (multi-restaurante vs. escuela única). Se reutilizan
**reglas de negocio, algoritmos y patrones**, reescritos sobre la arquitectura de
SistemaDeGestion. No se agrega Spatie: duplicaría el sistema de roles existente.

### Matriz funcional

Estado verificado leyendo el flujo completo (ruta → validación → autorización → servicio →
persistencia → vista) y, donde se indica, con la suite de tests de cada proyecto.

| Funcionalidad | SistemaDeGestion | adminChilingo | Estrategia |
|---|---|---|---|
| Autenticación | Completa: web + JWT para la app, tests `AuthTest`, `ApiJwtAuthTest` | Completa (web + Sanctum) | **REUTILIZAR** la propia |
| Usuarios y permisos | Rol único por usuario + overrides por rol/usuario; un usuario = un local (+ sucursales para ADMIN) | Persona con roles contextuales por sede/bloque (Spatie) | **EXTENDER**: membresías por organización con rol propio (idea de `Asignacion`), sin Spatie |
| Organizaciones | `restaurants` como tenant, sucursales | No aplica (una escuela) | **EXTENDER**: tipo de organización |
| Alumnos y profesores | No existe | Completo (Alumno, Profesor, Persona, ficha, importación) | **ADAPTAR**: alumnos/profes como *contactos* con cuenta opcional |
| Sedes y actividades | Sectores (salón) y actividades recurrentes simples | Sedes, bloques, horarios | **ADAPTAR**: talleres con horarios por organización |
| Inscripciones | No existe | Registro de alumno a bloque, sin cupos ni lista de espera | **DESARROLLAR** cupos + lista de espera; **ADAPTAR** inscripción |
| Calendario y clases | Eventos (fecha/hora) + actividades recurrentes calculadas al vuelo, sin excepciones | Calendario de bloques y eventos/shows | **EXTENDER**: clases materializadas con excepciones + agenda unificada |
| Asistencia y calificaciones | No existe | Asistencia completa (tipos presente/tarde/justificada/feriado), detección de faltas seguidas; observaciones pedagógicas; **sin calificaciones** | **ADAPTAR** asistencia y alerta de faltas; **DESARROLLAR** evaluaciones |
| Notificaciones | Infra de Laravel (`notifications`) + campana + push Expo; 3 notificaciones puntuales | Servicio central idempotente multicanal | **ADAPTAR** el patrón idempotente sobre la infra existente + preferencias |
| Mensajes entre usuarios | No existe | No existe | **DESARROLLAR** |
| Contactos y marketing | `clients` (clientes del local) y `suppliers` | WhatsApp/Twilio a alumnos, sin agenda de contactos | **DESARROLLAR** contactos/etiquetas/grupos/difusión; `clients` se mantiene |
| Stock y consignaciones | Stock de productos del menú con movimientos auditados | Inventario de instrumentos (no comercial) | **DESARROLLAR** stock de emprendedores y consignaciones (entidad distinta del menú) |
| Pedidos por ciclos (nodo) | Pedidos de mesa (otro dominio) | No existe | **DESARROLLAR** |
| Ingresos y gastos | Gastos/ingresos fijos (proyección), caja | Cuotas, pagos, gastos por sede, reportes | **EXTENDER**: libro de ingresos/gastos por taller/evento |
| Distribuciones (70/30) | No existe | Pagos a docentes (`PagosDocenteService`) con reglas fijas de la escuela | **DESARROLLAR** reglas configurables |
| Eventos y pagos | Eventos con productos, sin presupuesto ni pagos | Shows/eventos sin finanzas por etapa | **EXTENDER**: etapas, responsables, presupuesto, pagos parciales |
| Documentos descargables | Tutoriales PDF (archivo suelto) | Biblioteca (archivos + etiquetas + visibilidad), manual de marca (Diseño) | **ADAPTAR** biblioteca → documentos con versiones y permisos |
| Manuales de marca | No existe | Manual de marca estático + editor de diseño | **DESARROLLAR** generación PDF con dompdf (ya instalado) |
| Backups | No | Backup diario SQL (`Respaldo`) | Pendiente (ver Fase G) |
| Partituras, archivo histórico, Villa Gesell | — | Específicos de La Chilinga | **No se migran** (regla de negocio exclusiva) |

## Fase B — Arquitectura elegida

1. **Organización = `restaurants`.** No se renombra la tabla (rompería API, app y datos). Se agrega
   `restaurants.kind` (RESTAURANTE por defecto, ESPACIO_CULTURAL, ESCUELA, MERCADO,
   NODO_AGROECOLOGICO, EVENTOS, OTRO).
2. **Membresías** (`organization_memberships`): una cuenta participa en otras organizaciones con
   un rol distinto en cada una. `BranchAccess` las suma a las organizaciones elegibles y, al
   operar una, `User::role` devuelve el rol de esa membresía sólo durante la request (mismo
   mecanismo que ya usa `useRestaurant()`); la fila del usuario no cambia.
   Roles nuevos sólo por membresía: COORDINADOR, PROFESOR, ALUMNO, EMPRENDEDOR, ARTISTA,
   MARKETING, INVENTARIO (el ENUM de `users.role` no se toca).
3. **Contactos como registro único de personas** de cada organización, con `user_id` opcional:
   alumnos, profes, artistas, emprendedores, clientes, proveedores y público sin necesidad de
   cuenta, sin duplicar datos de usuarios.
4. **Módulos nuevos licenciables** con `default => false`: una organización existente no ve nada
   nuevo hasta que el superadmin lo habilita en *Monitor de módulos*.
5. **Notificaciones centralizadas** (`App\Services\Notifier`): clave idempotente (patrón de
   adminChilingo), preferencias por categoría, tabla `notifications` existente, campana existente.
6. **Dinero en centavos** como el resto del sistema.
7. **Stock comercial separado del stock del menú**: `market_products` + `market_stock_movements`.
   Propiedad (de qué emprendedor es) y dinero adeudado (liquidaciones) se modelan aparte del stock físico.

## Resultado (28/10/2026)

### Fase C — Fundamentos

| | |
|---|---|
| **Reutilizado** | `BranchAccess` + sesión de sucursal + `useRestaurant()` (mecanismo de tenant activo), `PermissionService` y `config/permissions.php`, `ModuleLicenseService`, trait `BelongsToRestaurant` (scope por organización), `CheckRole`. |
| **Adaptado de adminChilingo** | Idea de `Asignacion` (rol contextual) → `organization_memberships`: rol por organización, aplicado sólo durante la request. |
| **Desarrollado** | `restaurants.kind`, 7 roles nuevos (sólo por membresía), pantalla *Organización y miembros*, middleware `perm:`, módulos licenciables con `default => false`. |

### Fase D — Educación y comunicación

| | |
|---|---|
| **Adaptado de adminChilingo** | Detección de faltas seguidas (`AusenciasSeguidas`: justificadas no cuentan, presente/tarde corta la racha), tipos de asistencia, avisos idempotentes con clave de negocio (`NotificacionService` → `Notifier`), link de WhatsApp para contactar. |
| **Reutilizado** | Tabla `notifications` y campana existentes; `RecurringActivity` y `Event` aparecen en la agenda unificada sin cambios. |
| **Desarrollado** | Contactos (cuenta opcional), talleres con cupo y lista de espera con promoción automática, clases materializadas con reprogramación/suspensión sin perder historial, asistencia, evaluaciones, agenda unificada (semana/mes) con avisos y fechas especiales, mensajes entre usuarios, preferencias de avisos por categoría, recordatorios diarios (`community:reminders`). |

### Fase E — Comercial y documentos

| | |
|---|---|
| **Adaptado de adminChilingo** | Biblioteca (archivos con visibilidad) → documentos con versiones, estados y descarga autorizada. |
| **Desarrollado** | Emprendedores y grupos, productos de mercado, stock con movimientos auditados (bloqueo de fila, saldo resultante, sin negativos), consignaciones (entregado/vendido/devuelto/pendiente; ventas FIFO; comisión; liquidaciones), ciclos de pedidos con consolidado por productor y entrega que descuenta stock, alertas de stock bajo, manual de marca en PDF (dompdf ya instalado) como versión de documento. Contactos: etiquetas, grupos, importación/exportación CSV, comunicaciones de difusión sólo a quienes aceptaron. |

### Fase F — Economía y eventos

| | |
|---|---|
| **Reutilizado** | `Money`/centavos, `Event`. |
| **Desarrollado** | Reglas de reparto configurables (partes y %, base bruta o neta), libro de ingresos/gastos por taller o evento (se anula, no se borra), saldo por alumno contra el arancel (pagos parciales), pagos de repartos que no pueden superar lo pendiente, reparto por resto mayor (no se pierden centavos), etapas de evento con responsable, presupuesto, pagado y saldo. |

### Migraciones agregadas (todas reversibles; probado up → down → up en MySQL)

- `2026_10_28_000001_organizations_kind_and_memberships`
- `2026_10_28_000002_create_contacts_and_communications_tables`
- `2026_10_28_000003_create_workshops_tables`
- `2026_10_28_000004_create_market_tables`
- `2026_10_28_000005_create_documents_and_activity_finance_tables` (agrega `distribution_rule_id` nullable a `events`; no toca datos existentes)

### Pruebas (Fase G)

- Suite completa en MySQL: **159 tests pasan** (133 previos + 26 nuevos en `tests/Feature/Community`).
- Cubren: membresías y rol por organización, aislamiento entre organizaciones, módulos apagados por defecto, cupo/lista de espera/promoción, clases y reprogramación sin duplicar, faltas seguidas, profesor y alumno con alcance propio, preferencias e idempotencia de avisos, importación/exportación, difusión con consentimiento, mensajes, consignaciones y liquidación, stock sin negativos con rollback, ciclos de pedidos, documentos y descargas, manual de marca PDF, reparto 70/30, etapas de evento, recordatorios.
- Se actualizó `ModuleLicenseTest`: ahora sin licencia guardada los módulos originales siguen prendidos y los nuevos no (cambio buscado).
- Pint y PHPStan (nivel y baseline existentes) sin errores; se quitaron 2 entradas del baseline que dejaron de aplicar.
- Revisión visual con Chromium (escritorio y 390 px) de talleres, agenda, finanzas, consignaciones y ciclos.

### Configuración para usar los módulos

1. `php artisan migrate` (en Railway lo hace `start.sh`).
2. Superadmin → *Monitor de módulos* → habilitar en la organización: Talleres y agenda, Contactos y difusión, Emprendedores y mercado, Documentos, Finanzas de actividades.
3. Admin de la organización → *Organización y miembros*: tipo de organización y sumar personas con su cuenta existente.
4. Scheduler: `start.sh` ahora levanta `schedule:work` (desactivable con `SCHEDULER_ENABLED=0`, dejarlo en una sola réplica). Corre `community:reminders` a las 09:00.
5. Correo (opcional): con `MAIL_*` configurado se envían los avisos por mail a quien lo elija en *Preferencias de avisos* y las comunicaciones marcadas «también por correo».
6. Los documentos se guardan en el disco `local` (`storage/app/private`): en Railway necesita volumen persistente, como los demás archivos subidos.

### Portada y demo (29/10/2026)

- La portada (`/`) tiene la sección «Centros culturales» con los módulos, cómo se gestiona y qué ve cada rol.
- En la entrada de la demo hay una opción **Centro cultural**: habilita sólo los módulos de gestión
  (talleres, agenda, contactos, mercado, documentos, finanzas, eventos y gastos fijos) y carga una vez
  datos de ejemplo (`App\Support\DemoCommunityData`, marcados «(ejemplo)») para empezar a crear
  talleres, eventos, ciclos y emprendedores desde la agenda. «Configurar módulos» lista también este grupo.
  Los ejemplos sólo se cargan con `DEMO_ENTRY_ENABLED` activo (nunca en una instalación de cliente).
- La agenda tiene accesos para crear taller, evento, ciclo de pedidos o emprendedor según permisos.
- `EventPolicy` suma a Coordinación (crear/editar) y a Artista y Marketing (ver).

### Formularios de Google / Excel y categorías (29/10/2026)

- **Formularios** (Comunidad → Formularios, módulo Contactos, permiso `contacts.create` para administrar):
  cada organización conecta sus Google Forms a través de la planilla de respuestas (link de Google Sheets
  compartido «cualquier persona con el enlace» o publicado como CSV) o sube el Excel/CSV descargado.
  Destinos: **inscripciones a talleres** (crea/actualiza el contacto y lo inscribe en el taller fijo o en los
  elegidos en el form, respetando cupo y lista de espera), **propuestas de talleristas** (contacto PROFESOR
  con etiqueta «Postulante» + taller en estado PROPUESTA), **contactos** y **actividades de agenda**.
- El sistema propone qué columna es cada dato (nombre, apellido, email, WhatsApp, taller, categoría,
  fecha, hora, consentimiento…) y la organización lo ajusta. Cada respuesta se guarda con un hash: no se
  duplica al volver a sincronizar; las que no se pudieron importar quedan «para revisar» y se reintentan.
  Los contactos existentes (mismo email o teléfono) se completan sin pisar lo cargado.
- `forms:sync` corre cada 30 minutos para los formularios con sincronización automática. Sólo se aceptan
  links `https://docs.google.com/spreadsheets/…` (sin OAuth ni credenciales de Google).
- **Categorías de actividades** (Agenda → Categorías): nombre y color; se asignan a talleres, eventos y
  avisos, y los formularios pueden crearlas. La agenda se filtra por tipo (clases, eventos, etapas,
  actividades, entregas, avisos) y por categoría; talleres se filtran por categoría.
- Migración `2026_10_29_000001_create_form_sources_and_activity_categories` (reversible).
- Se agregó `guzzlehttp/guzzle`: el cliente HTTP de Laravel lo necesita y faltaba en el proyecto
  (también dejaba sin funcionar el push de Expo ya existente).
- `composer audit` reporta avisos de seguridad previos en `laravel/framework` 10 y `livewire/livewire`
  (no relacionados con este cambio): conviene planificar la actualización.

### Pendiente / fuera de esta etapa

- **App móvil y API JWT**: los módulos nuevos son web. `AuthenticateJwt` ya aplica el rol de membresía al elegir sucursal, pero no hay endpoints nuevos.
- **Push (Expo)** para los avisos nuevos: hoy son en la app y por mail. Se puede sumar en `Notifier` reutilizando `SendExpoPushNotification`.
- **WhatsApp automático** (adminChilingo usa Twilio): acá sólo hay enlaces `wa.me` desde contactos y comunicaciones.
- **Inscripción pública** (formulario sin cuenta) y **autogestión de pedidos** del vecino en un ciclo: hoy los carga la gestión.
- **Manual de uso** (`app/Support/UserManual.php`): sumar secciones para los roles nuevos.
- **Backups** automáticos como el de adminChilingo.
- La matriz de permisos editable (`/permissions`) no lista todavía los roles nuevos (sus valores por defecto están en `config/permissions.php`).
