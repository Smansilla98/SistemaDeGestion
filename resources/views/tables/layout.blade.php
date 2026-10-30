@extends('layouts.app')

@section('title', 'Plano del salón')

@php
    $authUser = auth()->user();
    $role = $authUser->role ?? '';
    $canTakeOrder = in_array($role, \App\Models\Table::ORDER_TAKER_ROLES, true);
    $canOperate = in_array($role, \App\Policies\TablePolicy::UPDATE_ROLES, true);
    $canEdit = $authUser->can('create', \App\Models\Table::class);
    $statusLabels = ['LIBRE' => 'Libre', 'OCUPADA' => 'Ocupada', 'RESERVADA' => 'Reservada', 'CERRADA' => 'Cerrada'];

    // Ubicación de cada mesa. Las que no tienen posición guardada (o comparten
    // exactamente la misma con otra) se acomodan solas en una grilla libre.
    $grid = 24;
    $placed = [];
    $autoPlaced = 0;
    $slot = 0;
    $collides = function (int $x, int $y) use (&$placed) {
        foreach ($placed as [$px, $py]) {
            if (abs($px - $x) < 136 && abs($py - $y) < 120) {
                return true;
            }
        }

        return false;
    };
    $positions = [];
    foreach ($tables as $table) {
        $x = $table->position_x;
        $y = $table->position_y;
        $taken = $x !== null && $y !== null && collect($placed)->contains(fn ($p) => $p[0] === (int) $x && $p[1] === (int) $y);
        if ($x === null || $y === null || $taken) {
            do {
                $x = 24 + ($slot % 7) * 144;
                $y = 168 + intdiv($slot, 7) * 144;
                $slot++;
            } while ($collides($x, $y));
            $autoPlaced++;
        }
        $placed[] = [(int) $x, (int) $y];
        $positions[$table->id] = [(int) $x, (int) $y];
    }
    $canvasW = max(1100, collect($positions)->max(fn ($p) => $p[0]) + 160);
    $canvasH = max(600, collect($positions)->max(fn ($p) => $p[1]) + 160);

    $counts = collect($statusLabels)->map(fn ($l, $s) => $tables->where('status', $s)->count());
@endphp

@push('styles')
<style>
    .pl, #tableActions { --pl-libre: #2f9e6e; --pl-ocupada: var(--conurbania-primary); --pl-reservada: #d99a1e; --pl-cerrada: #8a9397; }
    .pl-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem; margin-bottom: 1.1rem; }
    .pl-head h1 { color: #fff; font-weight: 800; font-size: clamp(1.6rem, 3vw, 2.3rem); margin: 0; }
    .pl-head p { color: rgba(255,255,255,.85); margin: .25rem 0 0; }
    .pl-head-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
    .pl-btn { border: 0; border-radius: 12px; font-weight: 700; padding: .6rem 1rem; background: rgba(255,255,255,.95); color: var(--conurbania-dark); text-decoration: none; display: inline-flex; align-items: center; gap: .4rem; }
    .pl-btn:hover { background: #fff; color: var(--conurbania-dark); }
    .pl-btn.is-on { background: var(--conurbania-dark); color: #fff; }

    .pl-panel { background: #fff; border-radius: 18px; box-shadow: 0 10px 30px rgba(0,0,0,.12); padding: .9rem 1rem; margin-bottom: 1rem; }
    .pl-tabs { display: flex; gap: .45rem; overflow-x: auto; scrollbar-width: thin; padding-bottom: .15rem; }
    .pl-tab { flex: 0 0 auto; border: 1.5px solid #e2e5e7; background: #fff; color: #33403f; border-radius: 999px; padding: .45rem .95rem; font-weight: 700; font-size: .9rem; text-decoration: none; white-space: nowrap; }
    .pl-tab.is-active { background: var(--conurbania-primary); border-color: var(--conurbania-primary); color: #fff; }
    .pl-legend { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: .75rem; font-size: .85rem; color: #5d676b; font-weight: 600; }
    .pl-legend span { display: inline-flex; align-items: center; gap: .35rem; }
    .pl-legend i { width: 12px; height: 12px; border-radius: 4px; display: inline-block; }

    .pl-editbar { display: none; align-items: center; justify-content: space-between; gap: .75rem; flex-wrap: wrap; background: #fff8e6; border: 1.5px solid #f2d48a; border-radius: 14px; padding: .65rem .9rem; margin-bottom: 1rem; }
    .pl.is-editing .pl-editbar { display: flex; }
    .pl-editbar p { margin: 0; font-weight: 600; color: #6b5212; font-size: .9rem; }
    .pl-editbar .btn { border-radius: 10px; font-weight: 700; }

    .pl-board { background: #fff; border-radius: 18px; box-shadow: 0 10px 30px rgba(0,0,0,.12); padding: .75rem; }
    .pl-scroll { overflow: auto; border-radius: 14px; -webkit-overflow-scrolling: touch; max-height: 72vh; }
    #plCanvas { position: relative; min-width: 100%; background-color: #f6f7f8; border-radius: 14px; }
    .pl.is-editing #plCanvas { background-image: linear-gradient(to right, rgba(148,163,184,.22) 1px, transparent 1px), linear-gradient(to bottom, rgba(148,163,184,.22) 1px, transparent 1px); background-size: {{ $grid }}px {{ $grid }}px; outline: 2px dashed #f2d48a; outline-offset: -2px; }

    .pl-table { position: absolute; width: 96px; height: 96px; border-radius: 20px; border: 0; padding: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; color: #fff; font-weight: 800; box-shadow: 0 8px 20px rgba(15,23,42,.18); transition: transform .12s, box-shadow .12s; user-select: none; touch-action: manipulation; background: var(--pl-cerrada); }
    .pl-table.cap-s { width: 80px; height: 80px; border-radius: 50%; }
    .pl-table.cap-l { width: 124px; height: 92px; }
    .pl-table.libre { background: var(--pl-libre); }
    .pl-table.ocupada { background: linear-gradient(135deg, var(--conurbania-primary), var(--conurbania-secondary)); }
    .pl-table.reservada { background: var(--pl-reservada); }
    .pl-table:hover, .pl-table:focus-visible { transform: translateY(-2px) scale(1.03); box-shadow: 0 14px 28px rgba(15,23,42,.24); outline: none; }
    .pl-table .n { font-size: 1.3rem; line-height: 1; max-width: 90%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pl-table .s { font-size: .68rem; font-weight: 700; opacity: .92; letter-spacing: .02em; }
    .pl-table .ready { position: absolute; top: -8px; right: -8px; background: #fff; color: #217a53; border-radius: 999px; font-size: .66rem; padding: .2rem .45rem; box-shadow: 0 3px 8px rgba(0,0,0,.2); animation: plPulse 1.6s ease-in-out infinite; }
    @keyframes plPulse { 50% { transform: scale(1.08); } }
    .pl.is-editing .pl-table.auto { outline: 3px dashed rgba(217,154,30,.9); outline-offset: 3px; }
    .pl.is-editing .pl-table, .pl.is-editing .pl-fixture, .pl.is-editing .pl-sub { cursor: grab; touch-action: none; }
    .pl.is-editing .is-dragging { cursor: grabbing; z-index: 50; transform: scale(1.06); box-shadow: 0 18px 36px rgba(15,23,42,.3); }

    .pl-fixture { position: absolute; width: 220px; height: 84px; border-radius: 14px; background: linear-gradient(135deg, #262c3b, #22565e); color: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center; font-weight: 800; box-shadow: 0 8px 20px rgba(0,0,0,.15); user-select: none; }
    .pl-fixture small { font-weight: 500; opacity: .8; }
    .pl-sub { position: absolute; width: 150px; background: #fff; border: 2px solid #e2e5e7; border-radius: 14px; padding: .6rem; box-shadow: 0 6px 16px rgba(0,0,0,.08); user-select: none; }
    .pl-sub-title { font-weight: 800; font-size: .9rem; text-align: center; border-bottom: 1px solid #eceef0; padding-bottom: .4rem; margin-bottom: .5rem; }
    .pl-sub-items { display: flex; flex-wrap: wrap; gap: .4rem; justify-content: center; }
    .pl-seat { width: 40px; height: 40px; border-radius: 50%; border: 0; color: #fff; font-weight: 800; background: var(--pl-cerrada); }
    .pl-seat.libre { background: var(--pl-libre); } .pl-seat.ocupada { background: var(--conurbania-primary); } .pl-seat.reservada { background: var(--pl-reservada); }
    .pl-empty { padding: 3rem 1rem; text-align: center; color: #7b7d84; }

    #tableActions .modal-content { border: 0; border-radius: 20px; }
    .ta-head { display: flex; align-items: center; gap: .85rem; }
    .ta-num { width: 56px; height: 56px; border-radius: 16px; display: grid; place-items: center; font-weight: 800; font-size: 1.2rem; color: #fff; background: var(--pl-cerrada); flex: 0 0 auto; }
    .ta-num.libre { background: var(--pl-libre); } .ta-num.ocupada { background: var(--conurbania-primary); } .ta-num.reservada { background: var(--pl-reservada); }
    .ta-meta { color: #5d676b; font-size: .88rem; }
    .ta-list { display: grid; gap: .5rem; margin-top: 1rem; }
    .ta-list a, .ta-list button { display: flex; align-items: center; gap: .75rem; width: 100%; text-align: left; border: 1.5px solid #e6e8ea; background: #fff; border-radius: 14px; padding: .8rem .95rem; font-weight: 700; color: #1a1a1a; text-decoration: none; min-height: 52px; }
    .ta-list a:hover, .ta-list button:hover { border-color: var(--conurbania-primary); }
    .ta-list i { font-size: 1.2rem; color: var(--conurbania-secondary); width: 1.4rem; text-align: center; }
    .ta-list .is-main { background: linear-gradient(135deg, var(--conurbania-primary), var(--conurbania-secondary)); color: #fff; border: 0; }
    .ta-list .is-main i { color: #fff; }
    @media (max-width: 575.98px) {
        #tableActions .modal-dialog { margin: 0; position: fixed; bottom: 0; left: 0; right: 0; top: auto; max-width: none; min-height: 0; }
        #tableActions .modal-content { border-radius: 20px 20px 0 0; padding-bottom: env(safe-area-inset-bottom); }
        .pl-head-actions { width: 100%; }
        .pl-head-actions .pl-btn { flex: 1; justify-content: center; }
    }
</style>
@endpush

@section('content')
@include('special-menus._banner')
@include('partials.variant-picker')
<div class="pl" id="plRoot">
    <div class="pl-head">
        <div>
            <h1><i class="bi bi-diagram-3"></i> Plano del salón</h1>
            <p>Tocá una mesa para ver qué podés hacer con ella.</p>
        </div>
        <div class="pl-head-actions">
            <a href="{{ route('tables.index') }}" class="pl-btn"><i class="bi bi-grid-3x3-gap"></i> Ver como lista</a>
            @if($canEdit && $selectedSector)
            <button type="button" class="pl-btn" id="plEditToggle"><i class="bi bi-arrows-move"></i> <span>Editar plano</span></button>
            @endif
        </div>
    </div>

    <div class="pl-panel">
        @if($sectors->isEmpty())
            <p class="mb-0 text-muted">Todavía no hay sectores. {{ $canEdit ? 'Creá uno desde Sectores para empezar a armar el plano.' : '' }}</p>
        @else
        <nav class="pl-tabs" aria-label="Sectores">
            @foreach($sectors as $sector)
            <a href="{{ route('tables.layout', $sector->id) }}" class="pl-tab {{ $selectedSector && $selectedSector->id === $sector->id ? 'is-active' : '' }}" @if($selectedSector && $selectedSector->id === $sector->id) aria-current="page" @endif>{{ $sector->name }}</a>
            @endforeach
        </nav>
        <div class="pl-legend" id="plLegend">
            <span><i style="background: var(--pl-libre)"></i> Libres <strong data-count="LIBRE">{{ $counts['LIBRE'] }}</strong></span>
            <span><i style="background: var(--conurbania-primary)"></i> Ocupadas <strong data-count="OCUPADA">{{ $counts['OCUPADA'] }}</strong></span>
            <span><i style="background: var(--pl-reservada)"></i> Reservadas <strong data-count="RESERVADA">{{ $counts['RESERVADA'] }}</strong></span>
            <span><i style="background: var(--pl-cerrada)"></i> Cerradas <strong data-count="CERRADA">{{ $counts['CERRADA'] }}</strong></span>
        </div>
        @endif
    </div>

    @if($selectedSector)
    @if($canEdit)
    <div class="pl-editbar" role="region" aria-label="Edición del plano">
        <p><i class="bi bi-hand-index"></i> Arrastrá mesas, barras y escenario. Se acomodan a la grilla.
            @if($autoPlaced)<br><small>{{ $autoPlaced }} {{ $autoPlaced === 1 ? 'mesa no tenía' : 'mesas no tenían' }} lugar y se ubicaron solas (borde punteado). Guardá para fijarlas.</small>@endif
        </p>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="plAuto"><i class="bi bi-grid"></i> Ordenar automáticamente</button>
            <button type="button" class="btn btn-light btn-sm" id="plCancel">Cancelar</button>
            <button type="button" class="btn btn-primary btn-sm" id="plSave"><i class="bi bi-check2"></i> Guardar plano</button>
        </div>
    </div>
    @endif

    @php
        $sectorNameLower = mb_strtolower($selectedSector->name ?? '');
        $isSalon = str_contains($sectorNameLower, 'salon') || str_contains($sectorNameLower, 'salón');
        $fixtures = is_array($selectedSector->layout_config) ? ($selectedSector->layout_config['fixtures'] ?? []) : [];
        $subsectors = $selectedSector->subsectors ?? collect();
    @endphp
    <div class="pl-board">
        <div class="pl-scroll">
            <div id="plCanvas" style="width: {{ $canvasW }}px; height: {{ $canvasH }}px;" data-grid="{{ $grid }}">
                @if($isSalon)
                <div class="pl-fixture pl-drag" data-kind="fixture" data-id="stage" style="left: {{ $fixtures['stage']['x'] ?? 380 }}px; top: {{ $fixtures['stage']['y'] ?? 24 }}px;">
                    <span><i class="bi bi-music-note-beamed"></i> Escenario</span>
                </div>
                @endif

                @foreach($subsectors as $subsector)
                @php $cfg = is_array($subsector->layout_config) ? $subsector->layout_config : []; @endphp
                <div class="pl-sub pl-drag" data-kind="subsector" data-id="{{ $subsector->id }}" style="left: {{ $cfg['x'] ?? 24 }}px; top: {{ $cfg['y'] ?? 24 }}px;">
                    <div class="pl-sub-title">{{ $subsector->name }}@if($subsector->capacity)<small class="d-block text-muted fw-normal">{{ $subsector->capacity }} lugares</small>@endif</div>
                    <div class="pl-sub-items">
                        @foreach($subsector->items as $item)
                        <button type="button" class="pl-seat {{ strtolower($item->status) }}"
                                data-seat-id="{{ $item->id }}" data-seat-label="{{ $subsector->name }} · {{ $item->name }}" data-seat-status="{{ $item->status }}"
                                title="{{ $item->name }} · {{ $statusLabels[$item->status] ?? $item->status }}"
                                aria-label="{{ $subsector->name }} {{ $item->name }}, {{ $statusLabels[$item->status] ?? $item->status }}">{{ $item->position }}</button>
                        @endforeach
                    </div>
                </div>
                @endforeach

                @forelse($tables as $table)
                @php
                    [$px, $py] = $positions[$table->id];
                    $status = $table->status;
                    $session = $table->currentSession;
                    $order = $table->currentOrder;
                    $cap = $table->capacity <= 2 ? 'cap-s' : ($table->capacity >= 6 ? 'cap-l' : '');
                    $isAuto = $table->position_x === null || $table->position_y === null || $px !== (int) $table->position_x || $py !== (int) $table->position_y;
                    $label = is_numeric($table->number) ? 'Mesa '.$table->number : $table->number;
                    $canUpdateThis = $canOperate && $authUser->can('update', $table);
                @endphp
                <button type="button" class="pl-table pl-drag {{ strtolower($status) }} {{ $cap }} {{ $isAuto && $canEdit ? 'auto' : '' }}"
                        data-kind="table" data-id="{{ $table->id }}"
                        style="left: {{ $px }}px; top: {{ $py }}px;"
                        data-label="{{ $label }}"
                        data-status="{{ $status }}"
                        data-status-label="{{ $statusLabels[$status] ?? $status }}"
                        data-capacity="{{ $table->capacity }}"
                        data-waiter="{{ $session?->waiter?->name }}"
                        data-since="{{ $status === 'OCUPADA' ? $session?->started_at?->toIso8601String() : '' }}"
                        data-total="{{ $order && (float) $order->total > 0 ? '$'.number_format((float) $order->total, 2, ',', '.') : '' }}"
                        @if($canTakeOrder && in_array($status, ['LIBRE', 'OCUPADA', 'RESERVADA'], true)) data-order-url="{{ route('tables.orders.store', $table) }}" @endif
                        @if($status === 'OCUPADA') data-orders-url="{{ route('tables.orders', $table) }}" @endif
                        @if($canUpdateThis)
                            data-can-status="1"
                            data-edit-url="{{ route('tables.edit', $table) }}"
                            @if($status === 'OCUPADA') data-close-url="{{ route('tables.show-close', $table) }}" @endif
                            @if($status === 'LIBRE') data-reserve-url="{{ route('tables.reserve', $table) }}" @endif
                        @endif
                        aria-label="{{ $label }}, {{ $statusLabels[$status] ?? $status }}, {{ $table->capacity }} lugares">
                    @if($order && $order->status === \App\Models\Order::STATUS_LISTO)<span class="ready">Listo</span>@endif
                    <span class="n">{{ $table->number }}</span>
                    <span class="s">{{ $statusLabels[$status] ?? $status }} · {{ $table->capacity }}p</span>
                </button>
                @empty
                <div class="pl-empty">
                    <i class="bi bi-grid-3x3-gap" style="font-size: 2rem; opacity: .4"></i>
                    <p class="mt-2 mb-0">Este sector no tiene mesas todavía.@if($canEdit) Creálas desde <a href="{{ route('tables.index') }}">Mesas → Nueva mesa</a>.@endif</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
    @endif
</div>

{{-- Acciones de una mesa (modo servicio) --}}
<div class="modal fade" id="tableActions" tabindex="-1" aria-labelledby="taTitle">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content p-3">
            <div class="ta-head">
                <div class="ta-num" id="taNum"></div>
                <div class="flex-grow-1 min-w-0">
                    <h5 class="mb-0 fw-bold" id="taTitle"></h5>
                    <div class="ta-meta" id="taMeta"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="ta-list" id="taList"></div>
        </div>
    </div>
</div>

@if($canOperate)
    @include('tables.partials.status-modal')
@endif
@if($canTakeOrder)
    @include('tables.partials.order-modal')
@endif

<script>
(function () {
    const root = document.getElementById('plRoot');
    const canvas = document.getElementById('plCanvas');
    if (!canvas) return;
    const GRID = Number(canvas.dataset.grid) || 24;
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    let editing = false;

    const elapsed = (iso) => {
        if (!iso) return '';
        const m = Math.max(0, Math.floor((Date.now() - new Date(iso).getTime()) / 60000));
        return m < 60 ? `${m} min` : `${Math.floor(m / 60)} h ${String(m % 60).padStart(2, '0')} min`;
    };

    // ===== Modo servicio: acciones de la mesa =====
    const actionsModal = () => bootstrap.Modal.getOrCreateInstance(document.getElementById('tableActions'));
    function openActions(t) {
        const d = t.dataset;
        const num = document.getElementById('taNum');
        num.textContent = t.querySelector('.n')?.textContent || '';
        num.className = 'ta-num ' + d.status.toLowerCase();
        document.getElementById('taTitle').textContent = d.label;
        document.getElementById('taMeta').textContent = [d.statusLabel, `${d.capacity} lugares`, d.waiter, elapsed(d.since), d.total].filter(Boolean).join(' · ');
        const items = [];
        if (d.orderUrl) items.push(`<button type="button" class="is-main" data-act="order"><i class="bi bi-plus-lg"></i>${d.status === 'OCUPADA' ? 'Agregar al pedido' : 'Tomar pedido'}</button>`);
        if (d.ordersUrl) items.push(`<a href="${esc(d.ordersUrl)}"><i class="bi bi-receipt"></i>Ver pedidos</a>`);
        if (d.closeUrl) items.push(`<a href="${esc(d.closeUrl)}"><i class="bi bi-cash-coin"></i>Cobrar y cerrar</a>`);
        if (d.canStatus) items.push(`<button type="button" data-act="status"><i class="bi bi-arrow-left-right"></i>Cambiar estado</button>`);
        if (d.reserveUrl) items.push(`<a href="${esc(d.reserveUrl)}"><i class="bi bi-calendar-check"></i>Reservar</a>`);
        if (d.editUrl) items.push(`<a href="${esc(d.editUrl)}"><i class="bi bi-pencil"></i>Editar mesa</a>`);
        document.getElementById('taList').innerHTML = items.join('') || '<p class="text-muted mb-0 mt-2">Solo lectura para tu rol.</p>';
        document.getElementById('taList').onclick = (e) => {
            const act = e.target.closest('[data-act]')?.dataset.act;
            if (!act) return;
            actionsModal().hide();
            if (act === 'order' && window.TakeOrder) window.TakeOrder.open(d.orderUrl, d.label);
            if (act === 'status' && window.openChangeStatusModal) window.openChangeStatusModal(d.id, d.status, Number(d.capacity), d.status === 'LIBRE' ? 'OCUPADA' : 'LIBRE');
        };
        actionsModal().show();
    }

    canvas.addEventListener('click', (e) => {
        if (editing) return;
        const t = e.target.closest('.pl-table');
        if (t) return openActions(t);
        const seat = e.target.closest('.pl-seat');
        if (seat && window.TakeOrder && ['LIBRE', 'OCUPADA'].includes(seat.dataset.seatStatus)) {
            window.TakeOrder.open(`/tables/subsector-items/${seat.dataset.seatId}/orders`, seat.dataset.seatLabel);
        }
    });

    // ===== Actualización en vivo (solo en modo servicio) =====
    async function refresh() {
        if (editing) return;
        try {
            const res = await fetch(window.location.href, { credentials: 'same-origin' });
            if (!res.ok || res.redirected) return;
            const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
            const fresh = doc.getElementById('plCanvas');
            if (fresh && !editing) canvas.innerHTML = fresh.innerHTML;
            doc.querySelectorAll('[data-count]').forEach((n) => {
                const mine = document.querySelector(`[data-count="${n.dataset.count}"]`);
                if (mine) mine.textContent = n.textContent;
            });
        } catch (e) { /* se reintenta en el próximo ciclo */ }
    }
    document.addEventListener('altoque:order-created', refresh);
    setInterval(() => {
        if (document.visibilityState === 'visible' && !document.querySelector('.modal.show, .swal2-container')) refresh();
    }, 30000);

@if($canEdit)
    // ===== Modo edición: arrastrar y guardar =====
    const toggle = document.getElementById('plEditToggle');
    if (!toggle) return;
    const snap = (v) => Math.max(0, Math.round(v / GRID) * GRID);

    function setEditing(on) {
        editing = on;
        root.classList.toggle('is-editing', on);
        toggle.classList.toggle('is-on', on);
        toggle.querySelector('span').textContent = on ? 'Editando…' : 'Editar plano';
    }
    toggle.addEventListener('click', () => setEditing(!editing));
    document.getElementById('plCancel').addEventListener('click', () => window.location.reload());

    let drag = null;
    canvas.addEventListener('pointerdown', (e) => {
        if (!editing) return;
        const el = e.target.closest('.pl-drag');
        if (!el) return;
        e.preventDefault();
        el.setPointerCapture(e.pointerId);
        drag = { el, x0: e.clientX, y0: e.clientY, left: parseFloat(el.style.left) || 0, top: parseFloat(el.style.top) || 0 };
        el.classList.add('is-dragging');
    });
    canvas.addEventListener('pointermove', (e) => {
        if (!drag) return;
        const left = snap(drag.left + e.clientX - drag.x0);
        const top = snap(drag.top + e.clientY - drag.y0);
        drag.el.style.left = left + 'px';
        drag.el.style.top = top + 'px';
        // El lienzo crece si se arrastra hacia el borde.
        if (left + drag.el.offsetWidth + 40 > canvas.offsetWidth) canvas.style.width = (left + drag.el.offsetWidth + 120) + 'px';
        if (top + drag.el.offsetHeight + 40 > canvas.offsetHeight) canvas.style.height = (top + drag.el.offsetHeight + 120) + 'px';
    });
    const endDrag = () => { if (drag) { drag.el.classList.remove('is-dragging'); drag.el.classList.remove('auto'); drag = null; } };
    canvas.addEventListener('pointerup', endDrag);
    canvas.addEventListener('pointercancel', endDrag);

    document.getElementById('plAuto').addEventListener('click', () => {
        const tables = [...canvas.querySelectorAll('.pl-table')]
            .sort((a, b) => (a.querySelector('.n').textContent).localeCompare(b.querySelector('.n').textContent, 'es', { numeric: true }));
        const perRow = Math.max(3, Math.floor((canvas.offsetWidth - 48) / 144));
        const top0 = canvas.querySelector('.pl-fixture, .pl-sub') ? 168 : 24;
        tables.forEach((t, i) => {
            t.style.left = (24 + (i % perRow) * 144) + 'px';
            t.style.top = (top0 + Math.floor(i / perRow) * 144) + 'px';
            t.classList.remove('auto');
        });
    });

    document.getElementById('plSave').addEventListener('click', async () => {
        const pos = (el) => ({ id: el.dataset.id, position_x: Math.round(parseFloat(el.style.left) || 0), position_y: Math.round(parseFloat(el.style.top) || 0) });
        const body = {
            sector_id: {{ $selectedSector->id ?? 'null' }},
            tables: [...canvas.querySelectorAll('.pl-table')].map(pos),
            fixtures: [...canvas.querySelectorAll('.pl-fixture')].map(pos),
            subsectors: [...canvas.querySelectorAll('.pl-sub')].map(pos),
        };
        try {
            const res = await fetch('{{ route('tables.update-layout') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify(body),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.success) throw new Error(data.message || 'No se pudo guardar el plano.');
            setEditing(false);
            canvas.querySelectorAll('.auto').forEach((el) => el.classList.remove('auto'));
            Swal.fire({ icon: 'success', title: 'Plano guardado', timer: 1400, showConfirmButton: false });
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'No se pudo guardar', text: err.message, confirmButtonColor: '#b3261e' });
        }
    });
@endif
})();
</script>
@endsection
