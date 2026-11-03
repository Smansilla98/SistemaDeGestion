@extends('layouts.app')

@section('title', 'Mesas')

@php
    $authUser = auth()->user();
    $role = $authUser->role ?? '';
    $canTakeOrder = in_array($role, \App\Models\Table::ORDER_TAKER_ROLES, true);
    $canOperate = in_array($role, \App\Policies\TablePolicy::UPDATE_ROLES, true);
    $canCreate = $authUser->can('create', \App\Models\Table::class);

    // Solo sectores principales con mesas (los subsectores se ven en el plano).
    $visibleSectors = $sectors->filter(fn ($s) => strtoupper($s->type ?? 'SECTOR') !== 'SUBSECTOR' && ($s->tables->isNotEmpty() || $canCreate))->values();
    $allTables = $visibleSectors->flatMap(fn ($s) => $s->tables);
    $countBy = fn (string $status) => $allTables->where('status', $status)->count();
    $stats = [
        'all' => $allTables->count(),
        'LIBRE' => $countBy('LIBRE'),
        'OCUPADA' => $countBy('OCUPADA'),
        'RESERVADA' => $countBy('RESERVADA'),
    ];
    $statusLabels = ['LIBRE' => 'Libre', 'OCUPADA' => 'Ocupada', 'RESERVADA' => 'Reservada', 'CERRADA' => 'Cerrada'];
    $money = fn ($n) => '$'.number_format((float) $n, 2, ',', '.');
@endphp

@push('styles')
<style>
    .mz { --mz-libre: #2f9e6e; --mz-ocupada: var(--conurbania-primary); --mz-reservada: #d99a1e; --mz-cerrada: #7b7d84; --mz-listo: #2f9e6e; }
    .mz-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem; }
    .mz-head h1 { color: #fff; font-weight: 800; font-size: clamp(1.6rem, 3vw, 2.3rem); margin: 0; }
    .mz-head p { color: rgba(255,255,255,.82); margin: .25rem 0 0; }
    .mz-head-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
    .mz-head-actions .btn { border-radius: 12px; font-weight: 700; padding: .6rem 1rem; }
    .mz-btn-light { background: rgba(255,255,255,.95); color: var(--conurbania-dark); border: 0; }
    .mz-btn-light:hover { background: #fff; color: var(--conurbania-dark); }

    .mz-panel { background: #fff; border-radius: 18px; box-shadow: 0 10px 30px rgba(0,0,0,.12); padding: 1rem; margin-bottom: 1.25rem; }
    .mz-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: .6rem; }
    .mz-stat { border: 2px solid transparent; background: #f5f6f7; border-radius: 14px; padding: .7rem .9rem; text-align: left; cursor: pointer; transition: border-color .15s, background .15s; }
    .mz-stat:hover { background: #eef0f2; }
    .mz-stat.is-active { border-color: var(--conurbania-primary); background: var(--conurbania-primary-10); }
    .mz-stat strong { display: block; font-size: 1.6rem; line-height: 1.1; color: #1a2326; }
    .mz-stat span { font-size: .82rem; color: #5d676b; font-weight: 600; display: inline-flex; align-items: center; gap: .35rem; }
    .mz-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; background: var(--mz-cerrada); }
    .mz-dot.libre { background: var(--mz-libre); } .mz-dot.ocupada { background: var(--mz-ocupada); } .mz-dot.reservada { background: var(--mz-reservada); }

    .mz-toolbar { display: flex; gap: .6rem; align-items: center; margin-top: .9rem; flex-wrap: wrap; }
    .mz-search { flex: 1 1 220px; position: relative; margin: 0; }
    .mz-search i { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); color: #8a9397; }
    .mz-search input { width: 100%; border: 1.5px solid #e2e5e7; border-radius: 12px; padding: .65rem .9rem .65rem 2.4rem; font-size: 1rem; }
    .mz-search input:focus { outline: none; border-color: var(--conurbania-primary); box-shadow: 0 0 0 3px var(--conurbania-primary-10); }
    .mz-sort { border-radius: 12px; border: 1.5px solid #e2e5e7; padding: .6rem .8rem; font-size: .9rem; background: #fff; }
    .mz-chips { display: flex; gap: .45rem; overflow-x: auto; padding: .15rem 0 .25rem; margin-top: .75rem; scrollbar-width: thin; }
    .mz-chip { flex: 0 0 auto; border: 1.5px solid #e2e5e7; background: #fff; color: #33403f; border-radius: 999px; padding: .45rem .9rem; font-weight: 700; font-size: .88rem; cursor: pointer; white-space: nowrap; }
    .mz-chip small { color: #8a9397; font-weight: 600; margin-left: .3rem; }
    .mz-chip.is-active { background: var(--conurbania-primary); border-color: var(--conurbania-primary); color: #fff; }
    .mz-chip.is-active small { color: rgba(255,255,255,.8); }

    .mz-sector { background: rgba(255,255,255,.97); border-radius: 18px; box-shadow: 0 10px 30px rgba(0,0,0,.12); padding: 1rem 1rem 1.1rem; margin-bottom: 1.25rem; }
    .mz-sector-head { display: flex; align-items: baseline; justify-content: space-between; gap: .75rem; flex-wrap: wrap; margin-bottom: .85rem; padding: 0 .15rem; }
    .mz-sector-head h2 { font-size: 1.15rem; font-weight: 800; margin: 0; color: #1a2326; }
    .mz-sector-head h2 small { font-weight: 500; color: #7b7d84; font-size: .85rem; margin-left: .4rem; }
    .mz-sector-sum { display: flex; gap: .75rem; font-size: .82rem; color: #5d676b; font-weight: 600; }
    .mz-sector-sum span { display: inline-flex; align-items: center; gap: .3rem; }

    .mz-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: .9rem; }
    .mz-table { position: relative; display: flex; flex-direction: column; min-height: 212px; border-radius: 20px; border: 1.5px solid #e8eaec; background: #fff; padding: 1rem; box-shadow: 0 1px 2px rgba(16,24,40,.04); transition: transform .15s, box-shadow .15s, border-color .15s; }
    .mz-table.is-ocupada { background: linear-gradient(160deg, var(--conurbania-primary-10), #fff 60%); border-color: var(--conurbania-primary-30); }
    .mz-table.is-reservada { background: linear-gradient(160deg, rgba(217,154,30,.10), #fff 60%); border-color: rgba(217,154,30,.35); }
    .mz-table:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(16,24,40,.08); }
    .mz-table[hidden] { display: none; }
    .mz-table-top { display: flex; align-items: flex-start; gap: .5rem; }
    .mz-id { display: flex; flex-direction: column; align-items: flex-start; gap: .4rem; min-width: 0; }
    .mz-num { font-size: 1.9rem; font-weight: 800; line-height: 1; color: #1a2326; letter-spacing: -.03em; overflow-wrap: anywhere; }
    .mz-badge { display: inline-flex; align-items: center; gap: .35rem; font-size: .74rem; font-weight: 700; padding: .3rem .6rem; border-radius: 999px; background: #f0f2f3; color: #5d676b; white-space: nowrap; }
    .mz-badge .mz-dot { width: 8px; height: 8px; }
    .mz-badge.libre { background: rgba(47,158,110,.12); color: #217a53; }
    .mz-badge.ocupada { background: #fff; color: var(--conurbania-secondary); box-shadow: inset 0 0 0 1px var(--conurbania-primary-30); }
    .mz-badge.reservada { background: #fff; color: #94640a; box-shadow: inset 0 0 0 1px rgba(217,154,30,.4); }
    .mz-badge.listo { background: var(--mz-listo); color: #fff; animation: mzPulse 1.6s ease-in-out infinite; }
    .mz-badge.listo .mz-dot { background: #fff; }
    @keyframes mzPulse { 50% { box-shadow: 0 0 0 6px rgba(47,158,110,.18); } }
    .mz-table-top .dropdown { margin-left: auto; }
    .mz-more { width: 34px; height: 34px; margin-right: -6px; border: 0; border-radius: 10px; background: transparent; color: #7b8488; font-size: 1.1rem; display: grid; place-items: center; }
    .mz-more:hover, .mz-more[aria-expanded="true"] { background: rgba(0,0,0,.05); color: #1a2326; }
    .mz-body { flex: 1 0 auto; display: flex; flex-direction: column; justify-content: flex-end; gap: .45rem; padding: .6rem 0 .75rem; }
    .mz-total { font-size: 1.45rem; font-weight: 800; color: #1a2326; letter-spacing: -.02em; line-height: 1.1; }
    .mz-tags { display: flex; flex-wrap: wrap; gap: .35rem; }
    .mz-tags span { display: inline-flex; align-items: center; gap: .3rem; max-width: 100%; background: rgba(255,255,255,.85); border: 1px solid #e8eaec; border-radius: 8px; padding: .2rem .5rem; font-size: .78rem; font-weight: 600; color: #4b5557; }
    .mz-tags em { font-style: normal; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mz-tags span:has(.mz-late) { border-color: rgba(201,74,45,.35); background: rgba(201,74,45,.06); }
    .mz-late { color: var(--conurbania-accent); font-weight: 700; }
    .mz-seats { display: inline-flex; align-items: center; gap: .4rem; color: #5d676b; font-weight: 600; font-size: .92rem; }
    .mz-seats i { font-size: 1.1rem; color: #9aa3a7; }
    .mz-actions { display: flex; gap: .4rem; align-items: stretch; }
    .mz-actions .mz-main { flex: 1; min-width: 0; border: 0; border-radius: 12px; padding: .55rem .5rem; font-weight: 700; font-size: .9rem; color: #fff; background: linear-gradient(135deg, var(--conurbania-primary), var(--conurbania-secondary)); display: inline-flex; align-items: center; justify-content: center; gap: .35rem; text-decoration: none; min-height: 42px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; box-shadow: 0 4px 12px var(--conurbania-primary-30); }
    .mz-actions .mz-main:hover { filter: brightness(1.06); color: #fff; }
    .mz-actions .mz-main.is-soft { background: #f0f2f3; color: #1a2326; box-shadow: none; }
    .mz-icon { flex: 0 0 42px; width: 42px; min-height: 42px; border-radius: 12px; border: 1.5px solid #e2e5e7; background: #fff; color: #33403f; display: grid; place-items: center; font-size: 1.05rem; text-decoration: none; }
    .mz-icon:hover { border-color: var(--conurbania-primary); color: var(--conurbania-primary); }
    .mz-table .dropdown-menu { border-radius: 12px; box-shadow: 0 12px 30px rgba(0,0,0,.15); border: 0; padding: .35rem; }
    .mz-table .dropdown-item { border-radius: 8px; padding: .55rem .75rem; font-weight: 600; display: flex; gap: .5rem; align-items: center; }
    .mz-empty { grid-column: 1 / -1; text-align: center; color: #7b7d84; padding: 1.25rem; border: 2px dashed #e2e5e7; border-radius: 14px; }
    .mz-none { text-align: center; color: #fff; padding: 2rem 1rem; display: none; }
    .mz-refresh { color: rgba(255,255,255,.8); font-size: .8rem; text-align: right; margin: -.6rem 0 .6rem; }
    .mz-refresh button { background: none; border: 0; color: #fff; font-weight: 700; text-decoration: underline; padding: 0 .25rem; }

    @media (max-width: 767.98px) {
        .mz-stats { grid-template-columns: repeat(2, 1fr); }
        .mz-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem; }
        .mz-table { min-height: 172px; padding: .8rem .7rem .7rem; border-radius: 18px; }
        .mz-num { font-size: 1.5rem; }
        .mz-badge { font-size: .68rem; padding: .25rem .45rem; }
        .mz-total { font-size: 1.15rem; }
        .mz-actions .mz-main { font-size: .84rem; padding: .5rem .3rem; }
        .mz-actions .mz-main i { display: none; }
        .mz-icon { flex-basis: 38px; width: 38px; }
        .mz-sector { padding: .85rem .7rem; border-radius: 16px; }
        .mz-head-actions { width: 100%; }
        .mz-head-actions .btn { flex: 1; }
    }

    #createTableModal .modal-content { border-radius: 18px; border: 0; }
</style>
@endpush

@section('content')
@include('partials.variant-picker')
<div class="mz">
    <div class="mz-head">
        <div>
            <h1><i class="bi bi-grid-3x3-gap"></i> Mesas</h1>
            <p>{{ $canTakeOrder ? 'Tocá «Tomar pedido» en cualquier mesa: si está libre, se ocupa sola.' : 'Estado del salón en tiempo real.' }}</p>
        </div>
        <div class="mz-head-actions">
            <a href="{{ route('tables.layout') }}" class="btn mz-btn-light"><i class="bi bi-diagram-3"></i> Plano</a>
            @if($canCreate)
            <button type="button" class="btn mz-btn-light" data-bs-toggle="modal" data-bs-target="#createTableModal">
                <i class="bi bi-plus-lg"></i> Nueva mesa
            </button>
            @endif
        </div>
    </div>

    <div class="mz-panel">
        <div class="mz-stats" id="mzStats" role="group" aria-label="Filtrar por estado">
            <button type="button" class="mz-stat is-active" data-status-filter="all"><strong data-stat="all">{{ $stats['all'] }}</strong><span>Todas</span></button>
            <button type="button" class="mz-stat" data-status-filter="LIBRE"><strong data-stat="LIBRE">{{ $stats['LIBRE'] }}</strong><span><i class="mz-dot libre"></i>Libres</span></button>
            <button type="button" class="mz-stat" data-status-filter="OCUPADA"><strong data-stat="OCUPADA">{{ $stats['OCUPADA'] }}</strong><span><i class="mz-dot ocupada"></i>Ocupadas</span></button>
            <button type="button" class="mz-stat" data-status-filter="RESERVADA"><strong data-stat="RESERVADA">{{ $stats['RESERVADA'] }}</strong><span><i class="mz-dot reservada"></i>Reservadas</span></button>
        </div>
        <div class="mz-toolbar">
            <label class="mz-search" data-tutorial="tables-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" id="tableSearch" placeholder="Buscar mesa o mozo…" autocomplete="off" aria-label="Buscar mesa o mozo">
            </label>
            <form method="GET" action="{{ route('tables.index') }}">
                <select id="tableSort" name="sort" class="mz-sort" onchange="this.form.submit()" aria-label="Ordenar mesas">
                    @foreach(\App\Models\Table::sortOptions() as $value => $label)
                        <option value="{{ $value }}" {{ ($tableSort ?? 'number') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        @if($visibleSectors->count() > 1)
        <div class="mz-chips" role="group" aria-label="Filtrar por sector">
            <button type="button" class="mz-chip is-active" data-sector-filter="all">Todos los sectores</button>
            @foreach($visibleSectors as $sector)
            <button type="button" class="mz-chip" data-sector-filter="{{ $sector->id }}">{{ $sector->name }}<small>{{ $sector->tables->count() }}</small></button>
            @endforeach
        </div>
        @endif
    </div>

    <div class="mz-refresh" aria-live="polite">Actualizado <span id="mzUpdated">recién</span> · <button type="button" id="mzRefreshBtn">Actualizar</button></div>

    <div id="mzBoard">
        @forelse($visibleSectors as $sector)
        @php
            $sectorFree = $sector->tables->where('status', 'LIBRE')->count();
            $sectorBusy = $sector->tables->where('status', 'OCUPADA')->count();
        @endphp
        <section class="mz-sector" data-sector="{{ $sector->id }}" aria-label="Sector {{ $sector->name }}">
            <header class="mz-sector-head">
                <h2>{{ $sector->name }}@if($sector->description)<small class="d-none d-md-inline">{{ $sector->description }}</small>@endif</h2>
                <div class="mz-sector-sum">
                    <span><i class="mz-dot libre"></i>{{ $sectorFree }} libres</span>
                    <span><i class="mz-dot ocupada"></i>{{ $sectorBusy }} ocupadas</span>
                </div>
            </header>
            <div class="mz-grid">
                @forelse($sector->tables as $table)
                @php
                    $status = $table->status;
                    $session = $table->currentSession;
                    $order = $table->currentOrder;
                    $isReady = $order && $order->status === \App\Models\Order::STATUS_LISTO;
                    $waiterName = $session?->waiter?->name;
                    $canUpdateThis = $canOperate && $authUser->can('update', $table);
                    $isFirst = $loop->parent->first && $loop->first;
                    $search = strtolower($table->number.' '.$sector->name.' '.($waiterName ?? ''));
                @endphp
                <article class="mz-table is-{{ strtolower($status) }} table-item"
                         data-status="{{ $status }}"
                         data-table-number="{{ strtolower($table->number) }}"
                         data-search="{{ $search }}"
                         @if($isFirst) data-tutorial="tables-table-card" @endif>
                    <div class="mz-table-top">
                        <div class="mz-id">
                            <span class="mz-num">{{ $table->number }}</span>
                            @if($isReady)
                                <span class="mz-badge listo" title="La cocina marcó el pedido como listo"><i class="mz-dot"></i>Listo para servir</span>
                            @else
                                <span class="mz-badge {{ strtolower($status) }}"><i class="mz-dot {{ strtolower($status) }}"></i>{{ $statusLabels[$status] ?? $status }}</span>
                            @endif
                        </div>
                        @if($canUpdateThis)
                        <div class="dropdown">
                            <button type="button" class="mz-more" data-bs-toggle="dropdown" aria-expanded="false" title="Más acciones" aria-label="Más acciones de la mesa {{ $table->number }}"><i class="bi bi-three-dots-vertical"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @if($status === 'LIBRE')
                                    <li><button type="button" class="dropdown-item js-status" data-table-id="{{ $table->id }}" data-status="LIBRE" data-capacity="{{ $table->capacity }}" data-target-status="OCUPADA" @if($isFirst) data-tutorial="tables-action-ocupar" @endif><i class="bi bi-person-plus"></i> Ocupar y asignar mozo</button></li>
                                    <li><a class="dropdown-item" href="{{ route('tables.reserve', $table) }}" @if($isFirst) data-tutorial="tables-action-reservar" @endif><i class="bi bi-calendar-check"></i> Reservar</a></li>
                                @elseif($status === 'OCUPADA')
                                    <li><a class="dropdown-item" href="{{ route('tables.orders', $table) }}"><i class="bi bi-receipt"></i> Ver pedidos</a></li>
                                    <li><a class="dropdown-item" href="{{ route('tables.show-close', $table) }}"><i class="bi bi-cash-coin"></i> Cobrar y cerrar</a></li>
                                    <li><button type="button" class="dropdown-item js-status" data-table-id="{{ $table->id }}" data-status="OCUPADA" data-capacity="{{ $table->capacity }}" data-target-status="LIBRE"><i class="bi bi-arrow-counterclockwise"></i> Liberar sin cobrar</button></li>
                                @else
                                    <li><button type="button" class="dropdown-item js-status" data-table-id="{{ $table->id }}" data-status="{{ $status }}" data-capacity="{{ $table->capacity }}" data-target-status="{{ $status === 'RESERVADA' ? 'OCUPADA' : 'LIBRE' }}"><i class="bi bi-arrow-left-right"></i> Cambiar estado</button></li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('tables.edit', $table) }}"><i class="bi bi-pencil"></i> Editar mesa</a></li>
                            </ul>
                        </div>
                        @endif
                    </div>
                    <div class="mz-body">
                        @if($status === 'OCUPADA')
                            <span class="mz-total">{{ $order && (float) $order->total > 0 ? $money($order->total) : 'Sin consumo' }}</span>
                            <div class="mz-tags">
                                @if($waiterName)<span title="Mozo"><i class="bi bi-person" aria-hidden="true"></i><em>{{ $waiterName }}</em></span>@endif
                                @if($session?->started_at)<span title="Tiempo en la mesa"><i class="bi bi-clock" aria-hidden="true"></i><em class="mz-elapsed" data-since="{{ $session->started_at->toIso8601String() }}">{{ $session->started_at->format('H:i') }}</em></span>@endif
                            </div>
                        @else
                            <span class="mz-seats"><i class="bi bi-people" aria-hidden="true"></i> {{ $table->capacity }} {{ $table->capacity === 1 ? 'lugar' : 'lugares' }}</span>
                        @endif
                    </div>
                    <div class="mz-actions">
                        @if(in_array($status, ['LIBRE', 'OCUPADA', 'RESERVADA'], true) && $canTakeOrder)
                            <button type="button" class="mz-main js-take-order" data-table-id="{{ $table->id }}" data-table-label="{{ is_numeric($table->number) ? 'Mesa '.$table->number : $table->number }}" title="{{ $status === 'OCUPADA' ? 'Agregar al pedido' : 'Tomar pedido' }}">
                                <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ $status === 'OCUPADA' ? 'Agregar' : 'Tomar pedido' }}
                            </button>
                        @elseif($status === 'OCUPADA')
                            <a href="{{ route('tables.orders', $table) }}" class="mz-main is-soft"><i class="bi bi-receipt" aria-hidden="true"></i> Ver pedidos</a>
                        @elseif($canUpdateThis)
                            <button type="button" class="mz-main is-soft js-status" data-table-id="{{ $table->id }}" data-status="{{ $status }}" data-capacity="{{ $table->capacity }}" data-target-status="{{ $status === 'LIBRE' ? 'OCUPADA' : 'LIBRE' }}">
                                {{ $status === 'LIBRE' ? 'Ocupar mesa' : 'Cambiar estado' }}
                            </button>
                        @else
                            <span class="mz-main is-soft">{{ $statusLabels[$status] ?? $status }}</span>
                        @endif
                        @if($status === 'OCUPADA' && $canUpdateThis)
                            <a href="{{ route('tables.show-close', $table) }}" class="mz-icon" title="Cobrar y cerrar" aria-label="Cobrar y cerrar la mesa {{ $table->number }}"><i class="bi bi-cash-coin"></i></a>
                        @endif
                    </div>
                </article>
                @empty
                <div class="mz-empty">
                    Sin mesas en este sector.
                    @if($canCreate)<button type="button" class="btn btn-link p-0 align-baseline" data-bs-toggle="modal" data-bs-target="#createTableModal" data-sector-preset="{{ $sector->id }}">Agregar una</button>@endif
                </div>
                @endforelse
            </div>
        </section>
        @empty
        <section class="mz-sector text-center py-5">
            <i class="bi bi-grid-3x3-gap" style="font-size: 2.2rem; opacity: .4"></i>
            <p class="mt-2 mb-0">{{ $role === 'MOZO' ? 'No hay mesas libres ni mesas tuyas en este momento.' : 'Todavía no hay mesas cargadas.' }}</p>
        </section>
        @endforelse
    </div>
    <p class="mz-none" id="mzNone"><i class="bi bi-search"></i> Ninguna mesa coincide con el filtro.</p>
</div>

@if($canCreate)
<div class="modal fade" id="createTableModal" tabindex="-1" aria-labelledby="createTableTitle">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('tables.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="createTableTitle">Nueva mesa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="sector_id" class="form-label">Sector</label>
                        <select class="form-select" id="sector_id" name="sector_id" required>
                            <option value="">Elegí un sector</option>
                            @foreach($sectors->filter(fn ($s) => strtoupper($s->type ?? 'SECTOR') !== 'SUBSECTOR') as $sector)
                            <option value="{{ $sector->id }}">{{ $sector->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-7">
                            <label for="number" class="form-label">Número o nombre</label>
                            <input type="text" class="form-control" id="number" name="number" placeholder="Ej: 14 o Barra 5" required>
                        </div>
                        <div class="col-5">
                            <label for="capacity" class="form-label">Lugares</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" min="1" value="4" required>
                        </div>
                    </div>
                    <p class="small text-muted mt-3 mb-0"><i class="bi bi-info-circle"></i> La ubicación en el salón se acomoda después desde <strong>Plano</strong>.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear mesa</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@if($canOperate)
    @include('tables.partials.status-modal')
@endif
@if($canTakeOrder)
    @include('tables.partials.order-modal')
@endif

<script>
(function () {
    const STORE_KEY = 'altoque.mesas.filtros';

    // ===== Filtros (estado, sector, búsqueda) =====
    let filters = { status: 'all', sector: 'all', q: '' };
    try { Object.assign(filters, JSON.parse(localStorage.getItem(STORE_KEY) || '{}'), { q: '' }); } catch (e) {}

    function applyFilters() {
        const q = filters.q.toLowerCase().trim();
        if (filters.sector !== 'all' && !document.querySelector(`#mzBoard .mz-sector[data-sector="${filters.sector}"]`)) filters.sector = 'all';
        let anyVisible = false;
        document.querySelectorAll('#mzBoard .mz-sector[data-sector]').forEach((sec) => {
            const sectorOk = filters.sector === 'all' || sec.dataset.sector === String(filters.sector);
            let shown = 0;
            sec.querySelectorAll('.mz-table').forEach((t) => {
                const ok = sectorOk
                    && (filters.status === 'all' || t.dataset.status === filters.status)
                    && (!q || (t.dataset.search || '').includes(q));
                t.hidden = !ok;
                if (ok) shown++;
            });
            const hasEmptyHint = sec.querySelector('.mz-empty') && sectorOk && filters.status === 'all' && !q;
            sec.hidden = !(shown > 0 || hasEmptyHint);
            if (!sec.hidden) anyVisible = true;
        });
        const none = document.getElementById('mzNone');
        const hasSectors = !!document.querySelector('#mzBoard .mz-sector[data-sector]');
        if (none) none.style.display = (anyVisible || !hasSectors) ? 'none' : 'block';
        document.querySelectorAll('[data-status-filter]').forEach((b) => b.classList.toggle('is-active', b.dataset.statusFilter === filters.status));
        document.querySelectorAll('[data-sector-filter]').forEach((b) => b.classList.toggle('is-active', b.dataset.sectorFilter === String(filters.sector)));
        try { localStorage.setItem(STORE_KEY, JSON.stringify({ status: filters.status, sector: filters.sector })); } catch (e) {}
    }

    document.addEventListener('click', (e) => {
        const st = e.target.closest('[data-status-filter]');
        if (st) { filters.status = st.dataset.statusFilter; applyFilters(); return; }
        const sc = e.target.closest('[data-sector-filter]');
        if (sc) { filters.sector = sc.dataset.sectorFilter; applyFilters(); }
    });
    document.getElementById('tableSearch')?.addEventListener('input', (e) => { filters.q = e.target.value; applyFilters(); });

    // ===== Tiempo transcurrido en mesas ocupadas =====
    function tickElapsed() {
        const now = Date.now();
        document.querySelectorAll('.mz-elapsed[data-since]').forEach((el) => {
            const mins = Math.max(0, Math.floor((now - new Date(el.dataset.since).getTime()) / 60000));
            el.textContent = mins < 60 ? `${mins} min` : (mins < 1440 ? `${Math.floor(mins / 60)} h ${String(mins % 60).padStart(2, '0')}` : `${Math.floor(mins / 1440)} d ${Math.floor((mins % 1440) / 60)} h`);
            el.classList.toggle('mz-late', mins >= 90);
        });
    }

    // ===== Actualización sin recargar la página =====
    let lastUpdate = Date.now();
    function paintUpdated() {
        const s = Math.round((Date.now() - lastUpdate) / 1000);
        const el = document.getElementById('mzUpdated');
        if (el) el.textContent = s < 10 ? 'recién' : (s < 60 ? `hace ${s} s` : `hace ${Math.floor(s / 60)} min`);
    }
    async function refreshBoard() {
        try {
            const res = await fetch(window.location.href, { headers: { 'X-Requested-With': 'fetch' }, credentials: 'same-origin' });
            if (!res.ok || res.redirected) return;
            const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
            const board = doc.getElementById('mzBoard');
            if (!board) return;
            document.getElementById('mzBoard').innerHTML = board.innerHTML;
            doc.querySelectorAll('[data-stat]').forEach((n) => {
                const mine = document.querySelector(`[data-stat="${n.dataset.stat}"]`);
                if (mine) mine.textContent = n.textContent;
            });
            lastUpdate = Date.now();
            applyFilters(); tickElapsed(); paintUpdated();
        } catch (e) { /* sin red: se reintenta en el próximo ciclo */ }
    }
    window.mzRefreshBoard = refreshBoard;
    document.getElementById('mzRefreshBtn')?.addEventListener('click', refreshBoard);
    setInterval(() => {
        paintUpdated(); tickElapsed();
        const busy = document.querySelector('.modal.show, .dropdown-menu.show, .swal2-container');
        if (document.visibilityState === 'visible' && !busy && Date.now() - lastUpdate > 30000) refreshBoard();
    }, 10000);

    applyFilters(); tickElapsed();

    // ===== Toma de pedido (modal compartido en tables/partials/order-modal) =====
    document.addEventListener('click', (e) => {
        const preset = e.target.closest('[data-sector-preset]');
        if (preset) { const sel = document.getElementById('sector_id'); if (sel) sel.value = preset.dataset.sectorPreset; }
        const b = e.target.closest('.js-take-order');
        if (b && window.TakeOrder) window.TakeOrder.open(`/tables/${b.dataset.tableId}/orders`, b.dataset.tableLabel);
    });
    document.addEventListener('altoque:order-created', refreshBoard);

    // Desde el Plano: /tables?pedido=ID abre directo la toma de pedido de esa mesa.
    const fromPlan = new URLSearchParams(window.location.search).get('pedido');
    if (fromPlan && window.TakeOrder) {
        const btn = document.querySelector(`.js-take-order[data-table-id="${CSS.escape(fromPlan)}"]`);
        if (btn) window.TakeOrder.open(`/tables/${btn.dataset.tableId}/orders`, btn.dataset.tableLabel);
        history.replaceState(null, '', window.location.pathname);
    }
})();

@if($role === 'MOZO')
// Aviso al mozo cuando la cocina marca un pedido como listo.
(function () {
    const notified = new Set();
    async function checkReadyOrders() {
        try {
            const res = await fetch('/api/notifications/ready-orders', { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            (data.orders || []).forEach((order) => {
                if (notified.has(order.id)) return;
                notified.add(order.id);
                Swal.fire({
                    icon: 'success', title: 'Pedido listo',
                    html: `Pedido <strong>#${order.number}</strong> para la <strong>mesa ${order.table_number}</strong>`,
                    toast: true, position: 'top-end', showConfirmButton: true, confirmButtonText: 'Ver pedidos',
                    confirmButtonColor: '#4e8d99', timer: 10000, timerProgressBar: true,
                }).then((r) => { if (r.isConfirmed && order.table_id) window.location.href = `/tables/${order.table_id}/orders`; });
                if (window.mzRefreshBoard) window.mzRefreshBoard();
            });
        } catch (e) {}
    }
    setTimeout(checkReadyOrders, 2000);
    setInterval(checkReadyOrders, 10000);
})();
@endif
</script>
@endsection
