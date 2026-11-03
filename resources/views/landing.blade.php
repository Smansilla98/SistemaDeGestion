@php
    $restaurant = \App\Models\Restaurant::first();
    $settings = is_array($restaurant?->settings ?? null) ? $restaurant->settings : [];
    $colors = \App\Support\Branding::palette($settings);
    $brand = \App\Support\Branding::name();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $brand }} — {{ \App\Support\Branding::tagline() }}</title>
    <meta name="description" content="{{ $brand }} es un sistema para gestionar un restaurante completo —salón de mesas, pedidos, cocina, caja, stock y reportes— y también centros culturales, escuelas, talleres, mercados y nodos: inscripciones, agenda, contactos, consignaciones y repartos.">
    <meta name="theme-color" content="{{ $colors['primary'] }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: {{ $colors['primary'] }};
            --secondary: {{ $colors['secondary'] }};
            --accent: {{ $colors['accent'] }};
            --ink: #16343b;
            --muted: #5d6b70;
            --bg: #f6f4f1;
            --card: #ffffff;
            --line: #e4e0da;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; font-family: Inter, system-ui, sans-serif; color: var(--ink); background: var(--bg); }
        a { color: inherit; }
        .wrap { max-width: 1120px; margin: 0 auto; padding: 0 20px; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 22px; border-radius: 12px; font-weight: 700; text-decoration: none; border: 2px solid transparent; transition: transform .15s, box-shadow .15s; }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; box-shadow: 0 8px 24px color-mix(in srgb, var(--primary) 35%, transparent); }
        .btn-ghost { border-color: var(--line); background: var(--card); color: var(--ink); }

        .nav { position: sticky; top: 0; z-index: 10; background: color-mix(in srgb, var(--bg) 88%, transparent); backdrop-filter: blur(10px); border-bottom: 1px solid var(--line); }
        .nav .wrap { display: flex; align-items: center; gap: 24px; padding-top: 12px; padding-bottom: 12px; }
        .nav__brand { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 1.15rem; text-decoration: none; }
        .nav__brand img { width: 36px; height: 36px; object-fit: contain; border-radius: 9px; }
        .nav__links { display: flex; gap: 22px; margin-left: auto; }
        .nav__links a { text-decoration: none; color: var(--muted); font-weight: 600; }
        .nav__links a:hover { color: var(--primary); }
        .nav .btn { padding: 9px 18px; }

        .hero { padding: 72px 0 56px; background: radial-gradient(900px 480px at 100% 0, color-mix(in srgb, var(--primary) 18%, transparent), transparent 65%); }
        .hero .wrap { display: grid; grid-template-columns: 1.15fr .85fr; gap: 48px; align-items: center; }
        .kicker { color: var(--accent); font-weight: 800; text-transform: uppercase; letter-spacing: .1em; font-size: .78rem; margin: 0 0 10px; }
        .hero h1 { font-size: clamp(2.1rem, 5vw, 3.5rem); line-height: 1.06; letter-spacing: -.02em; margin: 0 0 18px; }
        .hero h1 span { color: var(--primary); }
        .lead { font-size: 1.15rem; line-height: 1.6; color: var(--muted); margin: 0 0 28px; max-width: 36em; }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; }
        .mock { background: var(--card); border: 1px solid var(--line); border-radius: 20px; padding: 18px; box-shadow: 0 30px 60px rgba(22, 52, 59, .14); }
        .mock__bar { display: flex; justify-content: space-between; align-items: center; font-weight: 700; margin-bottom: 14px; }
        .mock__bar small { color: var(--muted); font-weight: 600; }
        .mock__tables { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
        .mesa { aspect-ratio: 1; border-radius: 14px; display: grid; place-items: center; font-weight: 800; font-size: .9rem; border: 2px solid var(--line); color: var(--muted); }
        .mesa.ocupada { background: color-mix(in srgb, var(--primary) 16%, #fff); border-color: var(--primary); color: var(--secondary); }
        .mesa.cuenta { background: color-mix(in srgb, var(--accent) 14%, #fff); border-color: var(--accent); color: var(--accent); }
        .mock__legend { display: flex; gap: 14px; margin-top: 14px; font-size: .8rem; color: var(--muted); }
        .mock__legend i { font-size: .7rem; margin-right: 4px; }

        section.block { padding: 56px 0; border-top: 1px solid var(--line); scroll-margin-top: 64px; }
        section.block h2 { font-size: clamp(1.5rem, 3vw, 2.2rem); margin: 0 0 24px; letter-spacing: -.01em; }
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 22px; transition: border-color .15s, transform .15s; }
        .card:hover { border-color: var(--primary); transform: translateY(-2px); }
        .card > i { display: inline-grid; place-items: center; width: 42px; height: 42px; border-radius: 12px; background: color-mix(in srgb, var(--primary) 14%, #fff); color: var(--secondary); font-size: 1.25rem; margin-bottom: 12px; }
        .card h3 { margin: 0 0 6px; font-size: 1.08rem; }
        .card p { margin: 0; color: var(--muted); line-height: 1.55; font-size: .94rem; }

        .steps { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; counter-reset: paso; }
        .steps li { counter-increment: paso; background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 22px; }
        .steps li::before { content: counter(paso); display: grid; place-items: center; width: 34px; height: 34px; border-radius: 50%; background: var(--primary); color: #fff; font-weight: 800; margin-bottom: 12px; }
        .steps strong { display: block; margin-bottom: 6px; }
        .steps span { color: var(--muted); line-height: 1.5; font-size: .93rem; }

        .roles { display: flex; flex-wrap: wrap; gap: 10px; }
        .roles span { background: var(--card); border: 1px solid var(--line); border-radius: 999px; padding: 8px 16px; font-weight: 600; }
        .roles span i { color: var(--primary); margin-right: 6px; }
        .note { color: var(--muted); margin: 18px 0 0; max-width: 46em; line-height: 1.6; }

        .alt { background: color-mix(in srgb, var(--accent) 6%, var(--bg)); }
        .split { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; align-items: start; margin-top: 32px; }
        .split h3 { margin: 0 0 12px; font-size: 1.2rem; }
        .checks { list-style: none; padding: 0; margin: 0; display: grid; gap: 10px; }
        .checks li { display: flex; gap: 10px; line-height: 1.5; color: var(--muted); }
        .checks li i { color: var(--primary); margin-top: 2px; }
        .checks li strong { color: var(--ink); }
        .kinds { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 24px; }
        .kinds span { background: var(--card); border: 1px dashed var(--line); border-radius: 999px; padding: 6px 14px; font-size: .9rem; font-weight: 600; color: var(--muted); }
        @media (max-width: 960px) { .split { grid-template-columns: 1fr; } }

        .cta { margin: 24px auto 64px; padding: 40px 24px; text-align: center; border-radius: 20px; background: linear-gradient(135deg, var(--secondary), var(--ink)); color: #fff; }
        .cta h2 { margin: 0 0 8px; font-size: clamp(1.5rem, 3vw, 2.1rem); }
        .cta p { margin: 0 0 22px; opacity: .85; }
        .cta .btn-primary { background: #fff; color: var(--secondary); box-shadow: none; }

        footer { border-top: 1px solid var(--line); padding: 24px 20px; text-align: center; color: var(--muted); font-size: .88rem; }

        @media (max-width: 960px) {
            .hero .wrap { grid-template-columns: 1fr; }
            .grid { grid-template-columns: repeat(2, 1fr); }
            .steps { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 600px) {
            .nav__links { display: none; }
            .nav .btn { margin-left: auto; }
            .hero { padding-top: 40px; }
            .grid, .steps { grid-template-columns: 1fr; }
            .actions .btn { width: 100%; justify-content: center; }
            .wrap { padding: 0 16px; }
        }
    </style>
</head>
<body>
<header class="nav">
    <div class="wrap">
        <a href="{{ route('home') }}" class="nav__brand">
            @if($logo = \App\Support\Branding::logoUrl($settings))
                <img src="{{ $logo }}" alt="">
            @else
                <img src="{{ asset('icons/icon-192.png') }}" alt="">
            @endif
            <span>{{ $brand }}</span>
        </a>
        <nav class="nav__links" aria-label="Secciones">
            <a href="#que-es">Qué es</a>
            <a href="#como-funciona">Cómo funciona</a>
            <a href="#centros-culturales">Centros culturales</a>
            <a href="#equipo">Equipo</a>
        </nav>
        <a href="{{ route('login') }}" class="btn btn-primary">Ingresar</a>
    </div>
</header>

<main>
    <section class="hero">
        <div class="wrap">
            <div>
                <p class="kicker">{{ \App\Support\Branding::modulesLine() }}</p>
                <h1>Tu restaurante, <span>al toque</span>.</h1>
                <p class="lead">
                    {{ $brand }} ordena la operación diaria de un restaurante: el salón de mesas, los pedidos,
                    las comandas de cocina, la caja y el stock, todo conectado y en tiempo real.
                </p>
                <div class="actions">
                    <a href="{{ route('login') }}" class="btn btn-primary"><i class="bi bi-box-arrow-in-right"></i> Ingresar al sistema</a>
                    <a href="#que-es" class="btn btn-ghost">Conocer más</a>
                    <a href="#centros-culturales" class="btn btn-ghost"><i class="bi bi-easel"></i> Para centros culturales</a>
                </div>
            </div>
            <div class="mock" aria-hidden="true">
                <div class="mock__bar"><span><i class="bi bi-grid-3x3-gap"></i> Salón</span><small>en vivo</small></div>
                <div class="mock__tables">
                    <div class="mesa">1</div><div class="mesa ocupada">2</div><div class="mesa">3</div><div class="mesa cuenta">4</div>
                    <div class="mesa ocupada">5</div><div class="mesa ocupada">6</div><div class="mesa">7</div><div class="mesa">8</div>
                    <div class="mesa cuenta">9</div><div class="mesa">10</div><div class="mesa ocupada">11</div><div class="mesa">12</div>
                </div>
                <div class="mock__legend">
                    <span><i class="bi bi-circle"></i>Libre</span>
                    <span><i class="bi bi-circle-fill" style="color: var(--primary)"></i>Ocupada</span>
                    <span><i class="bi bi-circle-fill" style="color: var(--accent)"></i>Pidió la cuenta</span>
                </div>
            </div>
        </div>
    </section>

    <section id="que-es" class="block">
        <div class="wrap">
            <p class="kicker">Qué es</p>
            <h2>Todo el servicio en un solo sistema</h2>
            <div class="grid">
                <article class="card">
                    <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>
                    <h3>Salón</h3>
                    <p>Mapa de mesas con su estado en tiempo real. Unir y dividir mesas en segundos.</p>
                </article>
                <article class="card">
                    <i class="bi bi-receipt" aria-hidden="true"></i>
                    <h3>Pedidos</h3>
                    <p>Carga rápida con modificadores y variantes, envío a cocina y control de tiempos.</p>
                </article>
                <article class="card">
                    <i class="bi bi-fire" aria-hidden="true"></i>
                    <h3>Cocina</h3>
                    <p>Pantalla de comandas siempre encendida que se actualiza sola.</p>
                </article>
                <article class="card">
                    <i class="bi bi-cash-coin" aria-hidden="true"></i>
                    <h3>Caja</h3>
                    <p>Apertura y cierre, movimientos, arqueo y todos los medios de pago.</p>
                </article>
                <article class="card">
                    <i class="bi bi-box-seam" aria-hidden="true"></i>
                    <h3>Stock</h3>
                    <p>Productos, costos y precios, con descuento automático en cada venta.</p>
                </article>
                <article class="card">
                    <i class="bi bi-bar-chart-line" aria-hidden="true"></i>
                    <h3>Reportes</h3>
                    <p>Ventas, gastos fijos y márgenes para decidir con números.</p>
                </article>
                <article class="card">
                    <i class="bi bi-shop" aria-hidden="true"></i>
                    <h3>Sucursales</h3>
                    <p>Un negocio con varios locales: cada uno con sus mesas, su caja y su stock.</p>
                </article>
                <article class="card">
                    <i class="bi bi-cup-straw" aria-hidden="true"></i>
                    <h3>Variantes y proporciones</h3>
                    <p>Una pizza con sus sabores o un trago armado por porcentaje del vaso.</p>
                </article>
                <article class="card">
                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                    <h3>Puesta a punto</h3>
                    <p>Corte general: frena pedidos, libera mesas y fija el stock real contado.</p>
                </article>
            </div>
        </div>
    </section>

    <section id="como-funciona" class="block">
        <div class="wrap">
            <p class="kicker">Cómo funciona</p>
            <h2>Del pedido al cierre de caja</h2>
            <ol class="steps">
                <li><strong>Se abre la mesa</strong><span>El mozo toma el pedido desde la tablet o el celular.</span></li>
                <li><strong>Cocina lo ve al instante</strong><span>La comanda aparece en la pantalla de cocina con su tiempo.</span></li>
                <li><strong>Se cobra</strong><span>La cuenta pasa a caja con el medio de pago elegido y se descuenta el stock.</span></li>
                <li><strong>Se cierra el día</strong><span>Arqueo de caja y reportes de ventas listos para revisar.</span></li>
            </ol>
        </div>
    </section>


    <section id="centros-culturales" class="block alt">
        <div class="wrap">
            <p class="kicker">También para centros culturales</p>
            <h2>Gestioná tu espacio, tus talleres y tu comunidad</h2>
            <div class="kinds">
                <span>Centros culturales</span><span>Escuelas y talleres</span><span>Mercados territoriales</span>
                <span>Nodos agroecológicos</span><span>Emprendedores</span><span>Organizadores de eventos</span>
            </div>
            <div class="grid">
                <article class="card">
                    <i class="bi bi-easel" aria-hidden="true"></i>
                    <h3>Talleres e inscripciones</h3>
                    <p>Cupos y lista de espera: si alguien se da de baja, el lugar pasa solo a quien sigue y se le avisa.</p>
                </article>
                <article class="card">
                    <i class="bi bi-calendar-week" aria-hidden="true"></i>
                    <h3>Agenda y clases</h3>
                    <p>Clases semanales, reprogramaciones con aviso, eventos, ferias, entregas y fechas especiales en un solo calendario.</p>
                </article>
                <article class="card">
                    <i class="bi bi-check2-square" aria-hidden="true"></i>
                    <h3>Asistencia y seguimiento</h3>
                    <p>El profe toma asistencia desde el celular; si alguien falta varias clases seguidas, la coordinación se entera.</p>
                </article>
                <article class="card">
                    <i class="bi bi-person-lines-fill" aria-hidden="true"></i>
                    <h3>Contactos y difusión</h3>
                    <p>Profes, artistas, alumnos y público con etiquetas y grupos. Comunicaciones sólo a quien aceptó recibirlas.</p>
                </article>
                <article class="card">
                    <i class="bi bi-shop-window" aria-hidden="true"></i>
                    <h3>Emprendedores y consignaciones</h3>
                    <p>Productos, stock y consignaciones: qué se vendió, qué se devolvió y cuánto se le debe a cada emprendedor.</p>
                </article>
                <article class="card">
                    <i class="bi bi-basket" aria-hidden="true"></i>
                    <h3>Ciclos de pedidos</h3>
                    <p>Para nodos y mercados: se abren pedidos, se cierra, se consolida qué pedir a cada productor y se entrega.</p>
                </article>
                <article class="card">
                    <i class="bi bi-pie-chart" aria-hidden="true"></i>
                    <h3>Repartos 70/30</h3>
                    <p>Ingresos y gastos por taller o evento, reglas de reparto a medida, pagos parciales y saldos al día.</p>
                </article>
                <article class="card">
                    <i class="bi bi-diagram-3" aria-hidden="true"></i>
                    <h3>Eventos por etapas</h3>
                    <p>Dividí cada evento en etapas con responsable, presupuesto y lo pagado de cada una.</p>
                </article>
                <article class="card">
                    <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                    <h3>Documentos y manual de marca</h3>
                    <p>Reglamentos y materiales con versiones y permisos; manual de marca en PDF para cada emprendedor.</p>
                </article>
            </div>

            <div class="split">
                <div>
                    <h3>Cómo se gestiona</h3>
                    <ul class="checks">
                        <li><i class="bi bi-1-circle-fill"></i><span><strong>Elegís el tipo de organización</strong> y los módulos que usás: nada más aparece en el menú.</span></li>
                        <li><i class="bi bi-2-circle-fill"></i><span><strong>Sumás a tu equipo con su propia cuenta</strong>: una persona puede ser mozo en un bar y profe en tu centro, sin otra contraseña.</span></li>
                        <li><i class="bi bi-3-circle-fill"></i><span><strong>Cargás talleres, eventos y productos</strong>, o importás tus contactos desde una planilla.</span></li>
                        <li><i class="bi bi-4-circle-fill"></i><span><strong>El sistema avisa solo</strong>: inscripciones, cambios de horario, recordatorios de clase, stock bajo y pagos pendientes.</span></li>
                    </ul>
                </div>
                <div>
                    <h3>Cada quien ve lo suyo</h3>
                    <ul class="checks">
                        <li><i class="bi bi-check-circle-fill"></i><span><strong>Coordinación</strong>: todo el espacio, agenda, finanzas y difusión.</span></li>
                        <li><i class="bi bi-check-circle-fill"></i><span><strong>Profes</strong>: sus talleres, la asistencia y las evaluaciones.</span></li>
                        <li><i class="bi bi-check-circle-fill"></i><span><strong>Alumnos</strong>: sus clases, avisos y documentos.</span></li>
                        <li><i class="bi bi-check-circle-fill"></i><span><strong>Emprendedores</strong>: sus productos, consignaciones y liquidaciones.</span></li>
                        <li><i class="bi bi-check-circle-fill"></i><span><strong>Marketing e inventario</strong>: contactos y comunicaciones, o stock y movimientos.</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section id="equipo" class="block">
        <div class="wrap">
            <p class="kicker">Equipo</p>
            <h2>Cada rol con lo que necesita</h2>
            <div class="roles">
                <span><i class="bi bi-shield-check"></i>Administrador</span>
                <span><i class="bi bi-person-badge"></i>Encargado</span>
                <span><i class="bi bi-person-walking"></i>Mozo</span>
                <span><i class="bi bi-egg-fried"></i>Cocina</span>
                <span><i class="bi bi-person-gear"></i>Superadmin</span>
                <span><i class="bi bi-diagram-3"></i>Coordinación</span>
                <span><i class="bi bi-easel"></i>Profe</span>
                <span><i class="bi bi-mortarboard"></i>Alumno</span>
                <span><i class="bi bi-shop-window"></i>Emprendedor</span>
                <span><i class="bi bi-music-note-beamed"></i>Artista</span>
                <span><i class="bi bi-megaphone"></i>Marketing</span>
            </div>
            <p class="note">Cada persona entra con su usuario y ve solo lo suyo: el mozo, sus mesas y pedidos; la cocina, sus comandas; la administración, la caja, el stock y los reportes. En un centro cultural, el profe ve sus talleres y el emprendedor sus consignaciones; y una misma cuenta puede participar en varias organizaciones con un rol distinto en cada una.</p>
        </div>
    </section>

    <div class="wrap">
        <section class="cta">
            <h2>¿Ya tenés usuario?</h2>
            <p>Entrá para abrir el salón, o para organizar los talleres y la agenda de tu espacio.</p>
            <a href="{{ route('login') }}" class="btn btn-primary">Ingresar</a>
        </section>
    </div>
</main>

<footer>
    © {{ date('Y') }} {{ $brand }} · {{ \App\Support\Branding::tagline() }} · <a href="{{ route('privacy') }}">Privacidad</a>
</footer>
</body>
</html>
