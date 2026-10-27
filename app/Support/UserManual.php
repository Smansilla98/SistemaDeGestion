<?php

namespace App\Support;

/**
 * Contenido del manual de uso, por rol.
 *
 * Cada rol arma su manual con secciones de una biblioteca común, así una misma
 * tarea (por ejemplo, cobrar una mesa) se explica igual para todos los que la hacen.
 */
class UserManual
{
    /**
     * @return array<string, array{label: string, icon: string, summary: string, day: list<string>, sections: list<string>}>
     */
    public static function roles(): array
    {
        return [
            'MOZO' => [
                'label' => 'Mozo',
                'icon' => 'bi-person-walking',
                'summary' => 'Atendés las mesas: tomás pedidos, los mandás a cocina, entregás y, si te lo permiten, cobrás.',
                'day' => [
                    'Entrá con tu usuario y abrí Mesas.',
                    'Cuando se sienta gente, tocá «Tomar pedido» en la mesa: se ocupa sola y queda a tu nombre.',
                    'Cargá los productos, confirmá y el ticket sale en cocina.',
                    'Cuando la cocina termina te llega el aviso «Pedido listo»: llevalo y marcá «Se entrega el producto».',
                    'Si piden algo más, «Agregar al pedido». Al final, «Cobrar» o avisá a la caja.',
                ],
                'sections' => ['ingreso', 'mesas_ver', 'tomar_pedido', 'agregar', 'entregar', 'plano', 'ocupar_reservar', 'cobrar_mesa', 'pedido_rapido', 'insumos_mozo', 'notificaciones', 'estados'],
            ],
            'COCINA' => [
                'label' => 'Cocina',
                'icon' => 'bi-egg-fried',
                'summary' => 'Ves en pantalla todo lo que entra para preparar, en orden de llegada y con el tiempo de cada pedido.',
                'day' => [
                    'Entrá con tu usuario: se abre directo la pantalla de Cocina.',
                    'Activá el sonido (botón de la campana) y, si querés, la pantalla completa.',
                    'Prepará en orden: arriba a la izquierda está lo más viejo.',
                    'Mirá el color de cada tarjeta: amarilla a los 10 minutos, roja a los 15.',
                    'Leé siempre las notas de cada ítem («sin sal», «bien cocido»).',
                ],
                'sections' => ['ingreso', 'cocina_pantalla', 'cocina_tickets', 'estados'],
            ],
            'CAJERO' => [
                'label' => 'Cajero',
                'icon' => 'bi-cash-coin',
                'summary' => 'Abrís y cerrás la caja, cobrás mesas y pedidos rápidos y registrás los movimientos de plata.',
                'day' => [
                    'Al empezar, abrí la sesión de caja con el efectivo inicial.',
                    'Cobrá las mesas desde Mesas → «Cobrar», con uno o varios medios de pago.',
                    'Cargá los ingresos y egresos de efectivo que no son ventas (cambio, compras chicas).',
                    'Para mostrador o para llevar, usá Pedido Rápido.',
                    'Al terminar, contá el efectivo y cerrá la sesión: el sistema te muestra cuánto debería haber.',
                ],
                'sections' => ['ingreso', 'caja_abrir', 'cobrar_mesa', 'pedido_rapido', 'caja_movimientos', 'caja_cerrar', 'mesas_ver', 'stock_ver', 'reportes', 'notificaciones', 'estados'],
            ],
            'ENCARGADO' => [
                'label' => 'Encargado',
                'icon' => 'bi-person-badge',
                'summary' => 'Coordinás el salón: mesas, mozos, pedidos y stock del turno. También podés tomar pedidos y cobrar.',
                'day' => [
                    'Antes de abrir, revisá el plano y las reservas del día.',
                    'Durante el servicio, mirá Mesas: filtrá por ocupadas y fijate los tiempos en rojo.',
                    'Asigná mozos al ocupar mesas y ayudá a tomar pedidos cuando haga falta.',
                    'Controlá el stock bajo y registrá lo que entra.',
                    'Si algo quedó trabado al cierre, usá Puesta a punto.',
                ],
                'sections' => ['ingreso', 'mesas_ver', 'tomar_pedido', 'agregar', 'entregar', 'ocupar_reservar', 'plano', 'plano_editar', 'mesas_crear', 'cobrar_mesa', 'stock_ver', 'puesta_a_punto', 'notificaciones', 'estados'],
            ],
            'SUPERVISOR' => [
                'label' => 'Supervisor',
                'icon' => 'bi-binoculars',
                'summary' => 'Seguís la operación: estado del salón, pedidos, caja y stock, y podés cambiar el estado de las mesas.',
                'day' => [
                    'Abrí el tablero (Página principal) para ver ventas y pedidos en curso.',
                    'En Mesas, filtrá por ocupadas para ver tiempos y totales.',
                    'Revisá Pedidos para detectar demoras.',
                    'Mirá el stock bajo para avisar a tiempo.',
                ],
                'sections' => ['ingreso', 'tablero', 'mesas_ver', 'plano', 'ocupar_reservar', 'cobrar_mesa', 'stock_ver', 'notificaciones', 'estados'],
            ],
            'GERENTE' => [
                'label' => 'Gerente',
                'icon' => 'bi-briefcase',
                'summary' => 'Gestionás el local: salón, carta, stock, caja, usuarios y medios de cobro.',
                'day' => [
                    'Revisá el tablero y la caja abierta.',
                    'Mantené la carta al día: productos, precios y variantes.',
                    'Controlá el stock y cargá las compras.',
                    'Ajustá el plano del salón y las mesas cuando cambie la distribución.',
                    'Gestioná los usuarios del equipo.',
                ],
                'sections' => ['ingreso', 'tablero', 'mesas_ver', 'tomar_pedido', 'plano_editar', 'mesas_crear', 'cobrar_mesa', 'caja_abrir', 'caja_movimientos', 'caja_cerrar', 'productos', 'stock_ver', 'puesta_a_punto', 'usuarios', 'medios_cobro', 'eventos', 'notificaciones', 'estados'],
            ],
            'ADMIN' => [
                'label' => 'Administrador',
                'icon' => 'bi-shield-check',
                'summary' => 'Configurás todo el sistema: sectores, carta, cajas, impresoras, usuarios, sucursales y reportes.',
                'day' => [
                    'Al arrancar con el sistema: sectores y mesas, categorías y productos, cajas, impresoras y usuarios.',
                    'Todos los días: tablero, caja y stock bajo.',
                    'Todas las semanas: reportes de ventas, productos y personal.',
                    'Cuando haga falta: descuentos, gastos fijos, sucursales y permisos.',
                ],
                'sections' => ['ingreso', 'tablero', 'sectores', 'mesas_crear', 'plano_editar', 'mesas_ver', 'tomar_pedido', 'cobrar_mesa', 'categorias', 'productos', 'descuentos', 'stock_ver', 'puesta_a_punto', 'caja_admin', 'caja_abrir', 'caja_cerrar', 'impresoras', 'usuarios', 'sucursales', 'reportes', 'gastos', 'eventos', 'notificaciones', 'estados'],
            ],
            'SUPERADMIN' => [
                'label' => 'Superadmin',
                'icon' => 'bi-stars',
                'summary' => 'Todo lo del administrador, más el monitor de módulos y las acciones críticas.',
                'day' => [
                    'Revisá el monitor de módulos para ver qué usa cada local.',
                    'Encendé o apagá módulos según lo contratado.',
                    'Ayudá a los administradores con permisos y sucursales.',
                ],
                'sections' => ['ingreso', 'monitor', 'sectores', 'mesas_crear', 'plano_editar', 'productos', 'usuarios', 'sucursales', 'reportes', 'caja_admin', 'notificaciones', 'estados'],
            ],
        ];
    }

    /**
     * Biblioteca de secciones. `route` es la pantalla a la que lleva el botón «Ir».
     *
     * @return array<string, array{title: string, icon: string, route?: string, intro?: string, steps?: list<string>, tips?: list<string>}>
     */
    public static function sections(): array
    {
        return [
            'ingreso' => [
                'title' => 'Entrar y salir del sistema',
                'icon' => 'bi-box-arrow-in-right',
                'intro' => 'Cada persona tiene su propio usuario. Lo crea el administrador o el gerente.',
                'steps' => [
                    'Escribí tu usuario y tu contraseña y tocá «Ingresar».',
                    'Si el negocio tiene varias sucursales y sos administrador, elegí en cuál vas a trabajar. El resto entra directo a la suya.',
                    'Para cambiar de sucursal, usá el botón de la tienda arriba a la derecha.',
                    'Para salir, tocá tu nombre arriba a la derecha → «Cerrar sesión». Hacelo siempre en equipos compartidos.',
                ],
                'tips' => [
                    'El menú de la izquierda muestra solo lo que tu rol puede usar. En celular se abre con el botón ☰.',
                    'Si olvidaste la contraseña, pedile al administrador que te genere una nueva.',
                ],
            ],
            'tablero' => [
                'title' => 'Página principal (tablero)',
                'icon' => 'bi-speedometer2',
                'route' => 'dashboard',
                'steps' => [
                    'Arriba ves los números del día: pedidos en proceso, ventas de la sesión de caja y lo que requiere atención.',
                    'La lista de pedidos recientes muestra mesa, mozo, estado y total. «Ver todos» abre Pedidos.',
                    'Las ventas por medio de pago muestran cuánto entró en efectivo, débito, crédito, transferencia y QR.',
                    'Las alertas de stock avisan qué productos están por agotarse.',
                ],
            ],
            'mesas_ver' => [
                'title' => 'Ver el salón (Mesas)',
                'icon' => 'bi-grid-3x3-gap',
                'route' => 'tables.index',
                'intro' => 'Mesas es la pantalla principal del servicio. Se actualiza sola cada 30 segundos.',
                'steps' => [
                    'Arriba están los contadores: Todas, Libres, Ocupadas y Reservadas. Tocá uno para ver solo esas mesas.',
                    'Usá el buscador para encontrar una mesa por número o por el nombre del mozo.',
                    'Si hay varios sectores (salón, patio, barra), tocá el sector para ver solo ese.',
                    'Cada mesa muestra su color de estado, los lugares y, si está ocupada, el mozo, el tiempo y el total.',
                    'El tiempo se pone en rojo después de 90 minutos. «Listo para servir» significa que la cocina terminó el pedido.',
                    'Los tres puntos (⋮) de cada mesa abren el resto de las acciones: ocupar, reservar, cambiar estado o editar.',
                ],
                'tips' => [
                    'El filtro y el sector que elegís quedan guardados para la próxima vez.',
                    'Los mozos ven las mesas libres y las propias. El resto de los roles ve todas.',
                ],
            ],
            'tomar_pedido' => [
                'title' => 'Tomar un pedido',
                'icon' => 'bi-plus-circle',
                'route' => 'tables.index',
                'steps' => [
                    'En Mesas, tocá «Tomar pedido» en la mesa. Si estaba libre, se ocupa sola y queda a tu nombre.',
                    'Buscá el producto por nombre o elegí la categoría (Bebidas, Platos, Postres…).',
                    'Tocá un producto para sumarlo. El número en la esquina dice cuántos llevás.',
                    'Si el producto tiene variantes (por ejemplo, sabores de pizza), elegí una en la ventanita que aparece.',
                    'En el panel «Pedido» ajustá cantidades con − y +. Con «Nota» agregás un pedido especial para ese ítem.',
                    'Si hace falta, escribí una nota para todo el pedido.',
                    'Dejá prendido «Enviar a cocina al confirmar» y tocá «Confirmar pedido». Sale el ticket en cocina.',
                ],
                'tips' => [
                    'En el celular, la barra de abajo muestra cuántos ítems llevás y el total. Tocala para ver y editar el pedido.',
                    'Lo que no tiene stock aparece gris y no se puede sumar. «Quedan 3» avisa que queda poco.',
                    'Si la impresora falla, el aviso lo dice y podés reimprimir el ticket desde ahí mismo.',
                ],
            ],
            'agregar' => [
                'title' => 'Agregar algo a una mesa ocupada',
                'icon' => 'bi-bag-plus',
                'route' => 'tables.index',
                'steps' => [
                    'Tocá «Agregar al pedido» en la mesa ocupada.',
                    'Sumá los productos nuevos y confirmá.',
                    'Los ítems se suman al mismo pedido de la mesa. A cocina sale solo lo nuevo.',
                ],
            ],
            'entregar' => [
                'title' => 'Entregar un pedido',
                'icon' => 'bi-check2-circle',
                'steps' => [
                    'Cuando la cocina termina, te llega el aviso «Pedido listo» y la mesa muestra «Listo para servir».',
                    'Tocá «Ver pedidos» en el aviso o «Pedidos» en la mesa y abrí el pedido.',
                    'Después de llevarlo, tocá «Se entrega el producto».',
                ],
                'tips' => ['El aviso de pedido listo aparece mientras tenés abierta la pantalla de Mesas.'],
            ],
            'plano' => [
                'title' => 'Usar el plano del salón',
                'icon' => 'bi-diagram-3',
                'route' => 'tables.layout',
                'intro' => 'El plano muestra las mesas como están en el local, con el mismo color de estado.',
                'steps' => [
                    'En Mesas tocá «Plano». Arriba elegís el sector.',
                    'Tocá una mesa para ver sus acciones: tomar o agregar pedido, ver pedidos, cobrar, cambiar estado.',
                    'En las barras con banquetas, tocá un asiento libre u ocupado para tomarle el pedido.',
                    'Con «Ver como lista» volvés a la vista de tarjetas.',
                ],
                'tips' => ['En el celular, deslizá el plano con el dedo para recorrer todo el salón.'],
            ],
            'plano_editar' => [
                'title' => 'Armar el plano del salón',
                'icon' => 'bi-arrows-move',
                'route' => 'tables.layout',
                'steps' => [
                    'Abrí Mesas → Plano y elegí el sector.',
                    'Tocá «Editar plano». Aparece la grilla.',
                    'Arrastrá cada mesa a su lugar real. También se mueven las barras y el escenario.',
                    'Las mesas nuevas que todavía no tienen lugar se ubican solas y se ven con borde punteado.',
                    '«Ordenar automáticamente» las acomoda en filas por número, como punto de partida.',
                    'Tocá «Guardar plano». «Cancelar» descarta los cambios.',
                ],
                'tips' => ['Mientras editás, tocar una mesa no abre sus acciones, así no se ocupa una mesa sin querer.'],
            ],
            'ocupar_reservar' => [
                'title' => 'Ocupar, reservar o liberar una mesa',
                'icon' => 'bi-calendar-check',
                'route' => 'tables.index',
                'steps' => [
                    'Ocupar sin pedir todavía: ⋮ → «Ocupar y asignar mozo». Indicá cuántas personas son y quién las atiende.',
                    'Reservar: ⋮ → «Reservar». Cargá nombre, teléfono, fecha, hora y cantidad de comensales.',
                    'Cuando llega una reserva: tocá «Tomar pedido» o ⋮ → «Cambiar estado» → Ocupada.',
                    'Si la gente se fue sin consumir: ⋮ → «Liberar sin cobrar».',
                ],
                'tips' => ['Si la mesa tiene consumo, no la liberes: cobrala con «Cobrar» así queda registrada la venta.'],
            ],
            'cobrar_mesa' => [
                'title' => 'Cobrar y cerrar una mesa',
                'icon' => 'bi-cash-coin',
                'route' => 'tables.index',
                'intro' => 'Para cobrar tiene que haber una sesión de caja abierta.',
                'steps' => [
                    'En Mesas, tocá «Cobrar» en la mesa (o ⋮ → «Cobrar y cerrar»).',
                    'Vas a ver todo lo consumido en la mesa, sumando todos sus pedidos.',
                    'Si el cliente lo pide, imprimí el recibo antes de cobrar.',
                    'Si corresponde, elegí un tipo de descuento.',
                    'Agregá el medio de pago: efectivo, débito, crédito, transferencia o QR. Podés combinar varios hasta cubrir el total.',
                    'En transferencia aparecen el alias y el CVU para copiar y pasarle al cliente.',
                    'Confirmá. La mesa queda libre y el cobro entra en la caja abierta.',
                ],
                'tips' => ['Si aparece «No hay una caja abierta», pedile al cajero o al encargado que abra la sesión de caja.'],
            ],
            'pedido_rapido' => [
                'title' => 'Pedido rápido (mostrador o para llevar)',
                'icon' => 'bi-lightning',
                'route' => 'orders.quick.index',
                'intro' => 'Sirve para consumos sin mesa. Necesita una sesión de caja abierta.',
                'steps' => [
                    'Abrí Pedidos → «Pedido Rápido».',
                    'Sumá los productos y, si querés, el nombre del cliente y una observación.',
                    'Confirmá. Si está prendido «Enviar a cocina», sale el ticket.',
                    'En la lista de pedidos rápidos activos, cobralo y cerralo cuando se entrega.',
                ],
            ],
            'insumos_mozo' => [
                'title' => 'Registrar insumos que llegan',
                'icon' => 'bi-box-seam',
                'route' => 'stock.mozo-insumos.create',
                'steps' => [
                    'Abrí «Insumos» en el menú.',
                    'Elegí el producto, el tipo de movimiento (Entrada si es una compra) y la cantidad.',
                    'En una compra, completá el proveedor (o crealo ahí mismo), el costo unitario, la fecha y el número de factura o remito.',
                    'Guardá. El stock se actualiza al instante.',
                ],
            ],
            'cocina_pantalla' => [
                'title' => 'La pantalla de cocina',
                'icon' => 'bi-display',
                'route' => 'kitchen.index',
                'steps' => [
                    'Los pedidos aparecen solos en tres columnas: Nuevos, En preparación y Listos.',
                    'Cada tarjeta muestra la mesa, el mozo, los ítems con sus variantes y notas, y los minutos desde que entró.',
                    'La tarjeta se pone amarilla a los 10 minutos y roja a los 15.',
                    'Tocá la campana para activar el sonido cuando entra un pedido. El navegador pide un toque para habilitarlo.',
                    'El botón de pantalla completa deja la pantalla limpia para una TV o una tablet.',
                ],
                'tips' => [
                    'La pantalla es de solo lectura: muestra lo que entra. El avance del pedido lo marca el salón.',
                    'Si se corta internet, arriba aparece el aviso de desconexión y la pantalla se reconecta sola.',
                ],
            ],
            'cocina_tickets' => [
                'title' => 'Tickets de cocina',
                'icon' => 'bi-printer',
                'steps' => [
                    'Cada pedido nuevo imprime un ticket con todos sus ítems.',
                    'Si después agregan algo a la mesa, sale un ticket solo con lo nuevo.',
                    'Si un ticket no salió, el mozo puede reimprimirlo desde el pedido («Ticket Cocina»).',
                ],
            ],
            'caja_abrir' => [
                'title' => 'Abrir la caja',
                'icon' => 'bi-unlock',
                'route' => 'cash-register.index',
                'steps' => [
                    'Abrí Caja.',
                    'Elegí la caja y cargá el monto inicial: el efectivo con el que arrancás.',
                    'Abrí la sesión. Desde ese momento, todos los cobros entran en esa sesión.',
                ],
                'tips' => ['Solo puede haber una sesión abierta por caja.'],
            ],
            'caja_movimientos' => [
                'title' => 'Ingresos y egresos de caja',
                'icon' => 'bi-arrow-down-up',
                'route' => 'cash-register.index',
                'steps' => [
                    'En la sesión abierta, buscá «Movimientos».',
                    'Cargá un ingreso (por ejemplo, cambio que te dejan) o un egreso (una compra chica, un adelanto).',
                    'Escribí una descripción clara: después se ve en el cierre.',
                ],
            ],
            'caja_cerrar' => [
                'title' => 'Cerrar la caja',
                'icon' => 'bi-lock',
                'route' => 'cash-register.index',
                'steps' => [
                    'Contá el efectivo que hay en la caja.',
                    'En la sesión, mirá «Efectivo esperado»: es el monto inicial más las ventas en efectivo y los movimientos. No incluye tarjeta, transferencia ni QR.',
                    'Tocá «Cerrar Sesión», cargá el monto final contado y, si hay diferencia, explicala en las notas.',
                    'La sesión cerrada queda en el historial con las ventas por medio de pago.',
                ],
            ],
            'caja_admin' => [
                'title' => 'Crear y administrar cajas',
                'icon' => 'bi-safe',
                'route' => 'cash-register.index',
                'steps' => [
                    'En Caja → «Gestión de Cajas», creá una caja por cada puesto de cobro (barra, salón, mostrador).',
                    'Podés editarlas o desactivarlas. No se borra una caja que tiene sesiones.',
                ],
            ],
            'stock_ver' => [
                'title' => 'Controlar el stock',
                'icon' => 'bi-boxes',
                'route' => 'stock.index',
                'steps' => [
                    'Abrí Stock. Arriba están las alertas de stock bajo.',
                    'La tabla muestra el stock actual y el mínimo de cada producto.',
                    'Con «Registrar Movimiento» cargás una entrada (compra), una salida (rotura, consumo interno) o un ajuste.',
                    'En Movimientos ves el historial: quién movió qué y cuándo.',
                ],
                'tips' => ['Cada venta descuenta stock sola, incluidos los ingredientes de los productos armados.'],
            ],
            'puesta_a_punto' => [
                'title' => 'Puesta a punto (corte general)',
                'icon' => 'bi-arrow-repeat',
                'route' => 'stock.reset.index',
                'intro' => 'Es un punto de partida: frena todo lo que está en curso y deja el stock en lo que realmente hay.',
                'steps' => [
                    'Abrí «Puesta a punto».',
                    'Cargá el conteo real de cada producto. El sistema muestra la diferencia con lo que tenía.',
                    'Revisá qué se va a bajar: pedidos en curso, total sin cobrar, mesas ocupadas y sesiones abiertas.',
                    'Escribí el motivo y la palabra de confirmación que pide la pantalla, y confirmá.',
                ],
                'tips' => ['Usala al empezar con el sistema, al abrir una sucursal o después de un inventario. No es para el cierre de todos los días.'],
            ],
            'sectores' => [
                'title' => 'Sectores y barras',
                'icon' => 'bi-door-open',
                'route' => 'sectors.index',
                'steps' => [
                    'En Sectores, creá cada zona del local: Salón, Patio, Terraza, Barra.',
                    'Dentro de un sector podés crear subsectores con elementos numerados, como una barra con banquetas.',
                    'Desactivar un sector lo saca de Mesas y del Plano sin borrar su historia.',
                ],
            ],
            'mesas_crear' => [
                'title' => 'Crear y editar mesas',
                'icon' => 'bi-plus-square',
                'route' => 'tables.index',
                'steps' => [
                    'En Mesas, tocá «Nueva mesa».',
                    'Elegí el sector, poné el número o el nombre (por ejemplo, «14» o «Barra 5») y la cantidad de lugares.',
                    'Ubicala en el salón desde Plano → «Editar plano».',
                    'Para cambiar número, lugares o sector: ⋮ → «Editar mesa».',
                ],
            ],
            'categorias' => [
                'title' => 'Categorías de la carta',
                'icon' => 'bi-tags',
                'route' => 'categories.index',
                'steps' => [
                    'Creá las categorías en el orden en que querés que aparezcan al tomar pedidos (Entradas, Platos, Bebidas…).',
                    'Cada producto pertenece a una categoría.',
                ],
            ],
            'productos' => [
                'title' => 'Productos, variantes y precios',
                'icon' => 'bi-journal-text',
                'route' => 'products.index',
                'steps' => [
                    'En Productos, creá cada producto con categoría, precio y, si se controla, stock mínimo.',
                    'Variantes: un producto base (Pizza) puede tener variantes (Muzzarella, Napolitana…) y cada una define su precio. Al tomar el pedido se elige la variante.',
                    'Proporciones: un trago se puede armar por porcentaje del vaso, y el stock descuenta cada ingrediente.',
                    'Los insumos llevan unidad de medida, costo unitario y proveedor habitual.',
                    'Para cambiar muchos precios juntos, usá la matriz de precios.',
                ],
            ],
            'descuentos' => [
                'title' => 'Tipos de descuento',
                'icon' => 'bi-percent',
                'route' => 'discount-types.index',
                'steps' => [
                    'Creá los descuentos que usa el local: porcentaje o monto fijo, por ejemplo «Empleados 20 %».',
                    'Después se eligen al cobrar una mesa o desde el detalle del pedido.',
                ],
            ],
            'impresoras' => [
                'title' => 'Impresoras',
                'icon' => 'bi-printer',
                'route' => 'printers.index',
                'steps' => [
                    'Cargá cada impresora térmica (cocina, barra, caja) con su conexión.',
                    'Usá «Probar» para imprimir una hoja de prueba.',
                    'La impresora de cocina recibe los tickets de cada pedido.',
                ],
            ],
            'usuarios' => [
                'title' => 'Usuarios del equipo',
                'icon' => 'bi-people',
                'route' => 'users.index',
                'steps' => [
                    'En Usuarios, tocá «Nuevo Usuario» y cargá nombre, usuario, contraseña (8 caracteres como mínimo) y rol.',
                    'Elegí el rol según la tarea: Mozo, Cocina, Cajero, Encargado, Supervisor, Gerente o Administrador.',
                    'Si alguien olvidó su clave, generale una contraseña temporal desde su ficha.',
                    'Cuando alguien deja de trabajar, desactivalo: no puede entrar más, pero su historial queda.',
                ],
                'tips' => ['Cada persona tiene que usar su propio usuario: así los pedidos y los cobros quedan a nombre de quien los hizo.'],
            ],
            'medios_cobro' => [
                'title' => 'Medios de cobro',
                'icon' => 'bi-credit-card',
                'route' => 'configuration.payment-methods.index',
                'steps' => [
                    'Activá los medios que acepta el local: efectivo, débito, crédito, transferencia, QR.',
                    'En transferencia cargá alias, CVU/CBU y titular. Se muestran al cobrar para que el cliente los copie.',
                    'En QR subí la imagen del código para mostrarla al cobrar.',
                ],
            ],
            'sucursales' => [
                'title' => 'Sucursales',
                'icon' => 'bi-shop',
                'route' => 'branches.index',
                'steps' => [
                    'En Sucursales, creá cada local del grupo con nombre, dirección y teléfono.',
                    'La sucursal nueva arranca con stock en cero: cargalo con una puesta a punto.',
                    'El administrador elige la sucursal al entrar. El resto del equipo entra directo a la suya.',
                ],
            ],
            'reportes' => [
                'title' => 'Reportes',
                'icon' => 'bi-bar-chart-line',
                'route' => 'reports.index',
                'steps' => [
                    'Ventas: ventas por día y por medio de pago. Se exportan a Excel o PDF.',
                    'Productos: lo más vendido y sus números.',
                    'Personal: ventas por mozo.',
                ],
            ],
            'gastos' => [
                'title' => 'Gastos fijos y actividades',
                'icon' => 'bi-receipt-cutoff',
                'route' => 'fixed-expenses.index',
                'steps' => [
                    'Cargá los gastos fijos (alquiler, servicios, sueldos) para ver el margen real en los reportes.',
                    'En Actividades recurrentes anotás tareas que se repiten, como limpiezas o controles.',
                ],
            ],
            'eventos' => [
                'title' => 'Eventos',
                'icon' => 'bi-calendar-event',
                'route' => 'events.index',
                'steps' => ['Cargá los eventos del local (shows, fechas especiales) para que el equipo los tenga presentes.'],
            ],
            'monitor' => [
                'title' => 'Monitor de módulos',
                'icon' => 'bi-speedometer',
                'route' => 'module-usage.index',
                'steps' => [
                    'Elegí el restaurante y el período para ver qué módulos se usan y cuánto.',
                    'Encendé o apagá módulos por local según lo contratado.',
                    'Mirá los usuarios más activos para detectar quién necesita ayuda.',
                ],
                'tips' => ['Solo un superadmin puede borrar sesiones de caja desde los reportes. Hacelo solo para corregir errores.'],
            ],
            'notificaciones' => [
                'title' => 'Notificaciones',
                'icon' => 'bi-bell',
                'route' => 'notifications.index',
                'steps' => [
                    'La campana de arriba muestra cuántos avisos sin leer tenés.',
                    'Tocala para ver la lista. Con «Marcar todas como leídas» la dejás en cero.',
                ],
            ],
            'estados' => [
                'title' => 'Qué significa cada estado',
                'icon' => 'bi-info-circle',
                'steps' => [
                    'Mesa Libre (verde): disponible.',
                    'Mesa Ocupada (color del local): tiene gente y, en general, un pedido abierto.',
                    'Mesa Reservada (amarillo): apartada para una reserva.',
                    'Mesa Cerrada (gris): fuera de uso por ahora.',
                    'Pedido Abierto → Enviado (a cocina) → En preparación → Listo → Entregado → Cerrado (cobrado). Cancelado: anulado.',
                ],
            ],
        ];
    }

    /**
     * Preguntas frecuentes. `roles` vacío = para todos.
     *
     * @return list<array{q: string, a: string, roles: list<string>}>
     */
    public static function faqs(): array
    {
        return [
            ['q' => 'Toqué «Tomar pedido» y la mesa quedó a mi nombre. ¿Está bien?', 'a' => 'Sí. Una mesa libre se ocupa sola al pedir y queda a nombre de quien toma el pedido. Para asignarla a otro mozo, usá ⋮ → «Ocupar y asignar mozo» antes de pedir.', 'roles' => ['MOZO', 'ENCARGADO', 'GERENTE', 'ADMIN']],
            ['q' => 'Me equivoqué en un producto ya enviado. ¿Qué hago?', 'a' => 'Abrí el pedido desde «Pedidos» de la mesa. Ahí podés cambiar o quitar ítems. Avisale a la cocina si ya lo estaban preparando.', 'roles' => ['MOZO', 'ENCARGADO', 'GERENTE', 'ADMIN']],
            ['q' => 'Dice «No hay una caja abierta» al cobrar.', 'a' => 'Para cobrar tiene que haber una sesión de caja abierta. La abre el cajero, el encargado o el administrador desde Caja.', 'roles' => []],
            ['q' => 'No salió el ticket en cocina.', 'a' => 'Revisá que la impresora tenga papel y esté encendida. Después reimprimí desde el pedido («Ticket Cocina»). Si sigue fallando, el administrador puede probarla desde Impresoras.', 'roles' => ['MOZO', 'COCINA', 'ENCARGADO', 'ADMIN']],
            ['q' => 'Un producto aparece «Sin stock» pero hay.', 'a' => 'El stock del sistema quedó desactualizado. Registrá una entrada o un ajuste desde Stock (o pedíselo al encargado) y vuelve a aparecer.', 'roles' => []],
            ['q' => 'No veo una mesa que antes veía.', 'a' => 'Revisá que no haya un filtro activo (contadores o sectores de arriba) ni texto en el buscador. Los mozos solo ven las mesas libres y las propias.', 'roles' => ['MOZO', 'ENCARGADO', 'SUPERVISOR']],
            ['q' => 'La pantalla de cocina no suena.', 'a' => 'Tocá el botón de la campana una vez: los navegadores no permiten sonido hasta que alguien toca la pantalla.', 'roles' => ['COCINA']],
            ['q' => 'El efectivo no coincide al cerrar la caja.', 'a' => 'Revisá los movimientos de la sesión (ingresos y egresos sin cargar) y los cobros en efectivo. Si la diferencia sigue, cerrá igual con el monto contado y explicala en las notas.', 'roles' => ['CAJERO', 'GERENTE', 'ADMIN']],
            ['q' => '¿Cómo vuelvo a ver el tutorial guiado de una pantalla?', 'a' => 'Agregá ?tutorial=1 al final de la dirección de esa pantalla, por ejemplo /tables?tutorial=1.', 'roles' => []],
        ];
    }

    public static function normalizeRole(?string $role): string
    {
        $role = strtoupper((string) $role);

        return array_key_exists($role, self::roles()) ? $role : 'MOZO';
    }
}
