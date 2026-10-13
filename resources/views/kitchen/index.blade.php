<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cocina - {{ config('app.name', 'Al Toque') }}</title>
    @include('partials.favicon')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --bg: #0e1416;
            --panel: #172124;
            --panel-2: #1f2c30;
            --line: #2c3d42;
            --ink: #f2f6f7;
            --muted: #9db0b5;
            --new: #3aa0b3;
            --prep: #f0a72e;
            --ready: #37b36b;
            --late: #e5484d;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .kd-top {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 12px 20px;
            background: var(--panel);
            border-bottom: 1px solid var(--line);
            flex-wrap: wrap;
        }
        .kd-title { font-size: 1.6rem; font-weight: 800; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; }
        .kd-clock { font-size: 1.6rem; font-weight: 700; font-variant-numeric: tabular-nums; }
        .kd-counts { display: flex; gap: 10px; margin-left: auto; flex-wrap: wrap; }
        .kd-count {
            display: flex; align-items: baseline; gap: 8px;
            padding: 6px 14px; border-radius: 12px;
            background: var(--panel-2); border-left: 6px solid var(--muted);
            font-weight: 600; color: var(--muted);
        }
        .kd-count strong { font-size: 1.7rem; color: var(--ink); font-variant-numeric: tabular-nums; }
        .kd-count.new { border-left-color: var(--new); }
        .kd-count.prep { border-left-color: var(--prep); }
        .kd-count.ready { border-left-color: var(--ready); }

        .kd-status { display: flex; align-items: center; gap: 8px; color: var(--muted); font-size: 0.95rem; }
        .kd-dot { width: 12px; height: 12px; border-radius: 50%; background: var(--ready); }
        .kd-status.offline { color: #ffb4b6; font-weight: 700; }
        .kd-status.offline .kd-dot { background: var(--late); animation: kd-blink 1s infinite; }
        .kd-tools { display: flex; gap: 6px; }
        .kd-tool {
            background: transparent; border: 1px solid var(--line); color: var(--muted);
            border-radius: 10px; padding: 8px 12px; font-size: 1rem; cursor: pointer;
        }
        .kd-tool:hover, .kd-tool:focus-visible { color: var(--ink); border-color: var(--muted); outline: none; }
        .kd-tool[aria-pressed="true"] { color: var(--ink); background: var(--panel-2); }

        .kd-board {
            flex: 1;
            overflow-y: auto;
            padding: 18px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 18px;
            align-content: start;
        }

        .kd-card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-top: 10px solid var(--new);
            border-radius: 16px;
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .kd-card.prep { border-top-color: var(--prep); }
        .kd-card.ready { border-top-color: var(--ready); }
        .kd-card.late { border-color: var(--late); border-top-color: var(--late); }
        .kd-card.fresh { animation: kd-flash 1.2s ease-in-out 4; }

        .kd-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
        .kd-place { font-size: 2.1rem; font-weight: 800; line-height: 1.05; letter-spacing: -0.02em; overflow-wrap: anywhere; }
        .kd-meta { color: var(--muted); font-size: 1rem; margin-top: 4px; }
        .kd-timer {
            font-size: 1.8rem; font-weight: 800; font-variant-numeric: tabular-nums;
            padding: 4px 12px; border-radius: 10px; background: var(--panel-2); white-space: nowrap;
        }
        .kd-timer.warn { background: var(--prep); color: #1b1300; }
        .kd-timer.late { background: var(--late); color: #fff; }
        .kd-chip {
            display: inline-block; margin-top: 6px; padding: 2px 10px; border-radius: 999px;
            font-size: 0.85rem; font-weight: 700; letter-spacing: 0.04em;
            background: var(--panel-2); color: var(--ink);
        }

        .kd-note {
            background: #3a2f12; color: #ffe2a3; border-radius: 10px;
            padding: 8px 12px; font-size: 1.1rem; font-weight: 600;
        }
        .kd-items { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 10px; }
        .kd-item { display: flex; gap: 14px; align-items: flex-start; padding-top: 10px; border-top: 1px solid var(--line); }
        .kd-item:first-child { border-top: 0; padding-top: 0; }
        .kd-qty {
            min-width: 58px; text-align: center; font-size: 1.9rem; font-weight: 800;
            background: var(--panel-2); border-radius: 10px; padding: 2px 8px; font-variant-numeric: tabular-nums;
        }
        .kd-name { font-size: 1.55rem; font-weight: 700; line-height: 1.15; overflow-wrap: anywhere; }
        .kd-mods { color: #8fd3e0; font-size: 1.2rem; font-weight: 600; margin-top: 2px; }
        .kd-obs { color: #ffe2a3; font-size: 1.1rem; font-weight: 600; margin-top: 2px; }
        .kd-item.done .kd-name, .kd-item.done .kd-mods { color: var(--muted); text-decoration: line-through; }
        .kd-item.done .kd-qty { color: var(--muted); }

        .kd-empty {
            grid-column: 1 / -1;
            text-align: center; color: var(--muted);
            padding: 12vh 20px; font-size: 1.8rem; font-weight: 600;
        }
        .kd-empty i { display: block; font-size: 5rem; margin-bottom: 12px; opacity: 0.5; }

        @keyframes kd-blink { 50% { opacity: 0.3; } }
        @keyframes kd-flash { 50% { background: #24424a; } }
        @media (prefers-reduced-motion: reduce) {
            .kd-card.fresh, .kd-status.offline .kd-dot { animation: none; }
        }
        @media (max-width: 720px) {
            .kd-board { grid-template-columns: 1fr; padding: 12px; }
            .kd-counts { margin-left: 0; width: 100%; }
        }
    </style>
</head>
<body>
    <header class="kd-top">
        <div class="kd-title"><i class="bi bi-fire"></i> Cocina</div>
        <div class="kd-clock" id="kdClock" aria-label="Hora actual">--:--</div>

        <div class="kd-counts" aria-live="polite">
            <div class="kd-count new"><strong id="kdCountNew">0</strong> Nuevos</div>
            <div class="kd-count prep"><strong id="kdCountPrep">0</strong> En preparación</div>
            <div class="kd-count ready"><strong id="kdCountReady">0</strong> Listos</div>
        </div>

        <div class="kd-status" id="kdStatus" role="status">
            <span class="kd-dot"></span>
            <span id="kdStatusText">Conectando…</span>
        </div>

        <div class="kd-tools">
            <button type="button" class="kd-tool" id="kdSound" aria-pressed="false" title="Avisar con sonido cuando entra un pedido">
                <i class="bi bi-volume-mute"></i>
            </button>
            <button type="button" class="kd-tool" id="kdFullscreen" title="Pantalla completa">
                <i class="bi bi-arrows-fullscreen"></i>
            </button>
            <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="kd-tool" title="Cerrar sesión"><i class="bi bi-box-arrow-right"></i></button>
            </form>
        </div>
    </header>

    <main class="kd-board" id="kdBoard" aria-label="Pedidos en cocina">
        <div class="kd-empty"><i class="bi bi-hourglass-split"></i>Cargando pedidos…</div>
    </main>

    <script>
    (function () {
        const BOARD_URL = @json($boardUrl);
        const REFRESH_MS = @json($refreshSeconds) * 1000;
        const WARN_MIN = 10;
        const LATE_MIN = 15;
        const STATUS_LABEL = { ENVIADO: 'NUEVO', EN_PREPARACION: 'EN PREPARACIÓN', LISTO: 'LISTO' };
        const STATUS_CLASS = { ENVIADO: 'new', EN_PREPARACION: 'prep', LISTO: 'ready' };

        const board = document.getElementById('kdBoard');
        const statusBox = document.getElementById('kdStatus');
        const statusText = document.getElementById('kdStatusText');

        let orders = [];
        let knownIds = null;      // null hasta la primera carga: no avisar por lo que ya estaba
        let freshUntil = {};      // id de pedido -> hasta cuándo resaltarlo
        let lastOkAt = null;
        let clockOffset = 0;      // diferencia reloj servidor - reloj de la pantalla
        let soundOn = false;
        let audioCtx = null;

        function el(tag, className, text) {
            const node = document.createElement(tag);
            if (className) node.className = className;
            if (text !== undefined && text !== null) node.textContent = text;
            return node;
        }

        function now() { return Date.now() + clockOffset; }

        function elapsed(sentAt) {
            const start = Date.parse(sentAt);
            if (Number.isNaN(start)) return { minutes: 0, label: '0:00' };
            const seconds = Math.max(0, Math.floor((now() - start) / 1000));
            const minutes = Math.floor(seconds / 60);
            const label = minutes >= 60
                ? `${Math.floor(minutes / 60)}h ${String(minutes % 60).padStart(2, '0')}m`
                : `${minutes}:${String(seconds % 60).padStart(2, '0')}`;
            return { minutes, label };
        }

        function renderCard(order) {
            const time = elapsed(order.sent_at);
            const card = el('article', 'kd-card ' + (STATUS_CLASS[order.status] || 'new'));
            if (time.minutes >= LATE_MIN && order.status !== 'LISTO') card.classList.add('late');
            if ((freshUntil[order.id] || 0) > Date.now()) card.classList.add('fresh');

            const head = el('div', 'kd-head');
            const left = el('div');
            left.appendChild(el('div', 'kd-place', order.place || 'Barra'));
            const meta = [order.number, order.sector, order.waiter].filter(Boolean).join(' · ');
            left.appendChild(el('div', 'kd-meta', meta));
            left.appendChild(el('span', 'kd-chip', STATUS_LABEL[order.status] || order.status));
            head.appendChild(left);

            const timer = el('div', 'kd-timer', time.label);
            timer.dataset.sentAt = order.sent_at || '';
            timer.dataset.status = order.status;
            if (order.status !== 'LISTO') {
                if (time.minutes >= LATE_MIN) timer.classList.add('late');
                else if (time.minutes >= WARN_MIN) timer.classList.add('warn');
            }
            head.appendChild(timer);
            card.appendChild(head);

            if (order.observations) card.appendChild(el('div', 'kd-note', order.observations));

            const list = el('ul', 'kd-items');
            (order.items || []).forEach((item) => {
                const row = el('li', 'kd-item');
                if (item.status === 'LISTO' || item.status === 'ENTREGADO') row.classList.add('done');
                row.appendChild(el('div', 'kd-qty', `${item.quantity}×`));
                const body = el('div');
                body.appendChild(el('div', 'kd-name', item.name || 'Producto'));
                if ((item.modifiers || []).length) body.appendChild(el('div', 'kd-mods', item.modifiers.join(' · ')));
                if (item.observations) body.appendChild(el('div', 'kd-obs', item.observations));
                row.appendChild(body);
                list.appendChild(row);
            });
            card.appendChild(list);

            return card;
        }

        function render() {
            board.replaceChildren();
            if (!orders.length) {
                const empty = el('div', 'kd-empty');
                empty.appendChild(el('i', 'bi bi-check2-circle'));
                empty.appendChild(document.createTextNode('Cocina al día: no hay pedidos pendientes'));
                board.appendChild(empty);
                return;
            }
            // Lo más viejo primero: es lo que hay que sacar antes.
            orders.slice()
                .sort((a, b) => Date.parse(a.sent_at) - Date.parse(b.sent_at))
                .forEach((order) => board.appendChild(renderCard(order)));
        }

        function tickTimers() {
            document.querySelectorAll('.kd-timer').forEach((timer) => {
                const time = elapsed(timer.dataset.sentAt);
                timer.textContent = time.label;
                if (timer.dataset.status === 'LISTO') return;
                timer.classList.toggle('late', time.minutes >= LATE_MIN);
                timer.classList.toggle('warn', time.minutes >= WARN_MIN && time.minutes < LATE_MIN);
                timer.closest('.kd-card').classList.toggle('late', time.minutes >= LATE_MIN);
            });

            const clock = new Date(now());
            document.getElementById('kdClock').textContent =
                `${String(clock.getHours()).padStart(2, '0')}:${String(clock.getMinutes()).padStart(2, '0')}`;

            if (lastOkAt !== null) {
                const ago = Math.floor((Date.now() - lastOkAt) / 1000);
                const offline = ago > (REFRESH_MS / 1000) * 3;
                statusBox.classList.toggle('offline', offline);
                statusText.textContent = offline
                    ? `Sin conexión hace ${ago}s — reintentando`
                    : 'En vivo';
            }
        }

        function beep() {
            if (!soundOn || !audioCtx) return;
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.frequency.value = 880;
            gain.gain.setValueAtTime(0.25, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.6);
            osc.connect(gain).connect(audioCtx.destination);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.6);
        }

        async function refresh() {
            try {
                const res = await fetch(BOARD_URL, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    cache: 'no-store',
                });
                if (res.status === 401 || res.status === 419) {
                    window.location.href = @json(route('login'));
                    return;
                }
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                if (!data.success) throw new Error('respuesta inválida');

                if (data.server_time) clockOffset = Date.parse(data.server_time) - Date.now();

                const incoming = data.orders || [];
                const ids = new Set(incoming.map((o) => o.id));
                if (knownIds !== null) {
                    const added = incoming.filter((o) => !knownIds.has(o.id));
                    added.forEach((o) => { freshUntil[o.id] = Date.now() + 6000; });
                    if (added.length) beep();
                }
                knownIds = ids;
                orders = incoming;

                document.getElementById('kdCountNew').textContent = (data.counts && data.counts.ENVIADO) || 0;
                document.getElementById('kdCountPrep').textContent = (data.counts && data.counts.EN_PREPARACION) || 0;
                document.getElementById('kdCountReady').textContent = (data.counts && data.counts.LISTO) || 0;

                lastOkAt = Date.now();
                render();
            } catch (e) {
                // Se conserva el último tablero conocido; tickTimers avisa si la desconexión se alarga.
                if (lastOkAt === null) statusText.textContent = 'Sin conexión — reintentando';
            }
        }

        // La pantalla no se apaga mientras el tablero esté a la vista.
        let wakeLock = null;
        async function keepAwake() {
            if (!('wakeLock' in navigator) || document.visibilityState !== 'visible') return;
            try { wakeLock = await navigator.wakeLock.request('screen'); } catch (e) { wakeLock = null; }
        }
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') { keepAwake(); refresh(); }
        });

        document.getElementById('kdFullscreen').addEventListener('click', () => {
            if (document.fullscreenElement) document.exitFullscreen();
            else document.documentElement.requestFullscreen().catch(() => {});
        });

        document.getElementById('kdSound').addEventListener('click', function () {
            soundOn = !soundOn;
            if (soundOn && !audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            this.setAttribute('aria-pressed', String(soundOn));
            this.firstElementChild.className = soundOn ? 'bi bi-volume-up' : 'bi bi-volume-mute';
            beep();
        });

        keepAwake();
        refresh();
        setInterval(refresh, REFRESH_MS);
        setInterval(tickTimers, 1000);
        tickTimers();
    })();
    </script>
</body>
</html>
