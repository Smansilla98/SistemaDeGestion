@extends('layouts.app')

@section('title', 'Calendario de Eventos')

@section('content')
<div class="row mb-3 mb-md-4">
    <div class="col-12">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div>
                <h1 class="text-white mb-0 mb-md-2" style="font-weight: 700; font-size: 1.75rem; font-size: clamp(1.5rem, 4vw, 2.5rem);">
                    <i class="bi bi-calendar-event"></i> Calendario de Eventos
                </h1>
            </div>
            @can('create', App\Models\Event::class)
            <a href="{{ route('events.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> <span class="d-none d-sm-inline">Nuevo </span>Evento
            </a>
            @endcan
        </div>
    </div>
</div>

@if(count($stockAlerts) > 0)
<div class="alert alert-danger mb-4">
    <h5 class="mb-3"><i class="bi bi-exclamation-triangle-fill"></i> Alertas de Stock para Eventos</h5>
    <p class="mb-3 fw-semibold">Los siguientes eventos requieren productos que no tienen suficiente stock:</p>
    <ul class="mb-0 alert-list">
        @foreach($stockAlerts as $alert)
        <li class="mb-2 pb-2 border-bottom border-danger border-opacity-25">
            <div class="d-flex flex-column flex-md-row flex-wrap align-items-start align-items-md-center gap-2">
                <div class="flex-grow-1">
                    <strong class="d-block mb-1">{{ $alert['event']->name }}</strong>
                    <small class="text-muted d-block mb-1">{{ $alert['event']->formatted_date_time }}</small>
                    <strong class="d-block">{{ $alert['product']->name }}</strong>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="badge bg-danger-subtle text-danger-emphasis border border-danger">
                        Stock: <strong>{{ $alert['current_stock'] }}</strong>
                    </span>
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning">
                        Necesario: <strong>{{ $alert['expected_quantity'] }}</strong>
                    </span>
                    <span class="badge bg-danger text-white">
                        Faltan: <strong>{{ $alert['shortage'] }}</strong>
                    </span>
                </div>
            </div>
        </li>
        @endforeach
    </ul>
</div>
@endif

<div class="card mb-3 mb-md-4">
    <div class="card-header">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div>
                <h5 class="mb-0">
                    {{ $startDate->locale('es')->translatedFormat('F Y') }}
                </h5>
            </div>
            <div class="btn-group w-100 w-md-auto">
                <a href="{{ route('events.index', ['year' => $prevMonth->year, 'month' => $prevMonth->month]) }}" 
                   class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-chevron-left"></i> <span class="d-none d-sm-inline">Anterior</span>
                </a>
                <a href="{{ route('events.index') }}" class="btn btn-sm btn-outline-secondary">
                    Hoy
                </a>
                <a href="{{ route('events.index', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}" 
                   class="btn btn-sm btn-outline-secondary">
                    <span class="d-none d-sm-inline">Siguiente </span><i class="bi bi-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="card-body p-1 p-md-3">
        <div class="calendar-container">
            <table class="table table-bordered calendar-table mb-0">
                <thead>
                    <tr>
                        <th class="text-center calendar-header">Dom</th>
                        <th class="text-center calendar-header">Lun</th>
                        <th class="text-center calendar-header">Mar</th>
                        <th class="text-center calendar-header">Mié</th>
                        <th class="text-center calendar-header">Jue</th>
                        <th class="text-center calendar-header">Vie</th>
                        <th class="text-center calendar-header">Sáb</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $day = 1;
                        $currentDay = 1;
                        $totalCells = $firstDayOfWeek + $daysInMonth;
                        $weeks = ceil($totalCells / 7);
                    @endphp
                    @for($week = 0; $week < $weeks; $week++)
                    <tr>
                        @for($dayOfWeek = 0; $dayOfWeek < 7; $dayOfWeek++)
                            @php
                                $cellDay = ($week * 7) + $dayOfWeek - $firstDayOfWeek + 1;
                                $isCurrentMonth = $cellDay >= 1 && $cellDay <= $daysInMonth;
                                $isToday = $isCurrentMonth && $cellDay == now()->day && $startDate->year == now()->year && $startDate->month == now()->month;
                                $dateKey = $isCurrentMonth ? $startDate->copy()->addDays($cellDay - 1)->format('Y-m-d') : null;
                                $dayEvents = $dateKey && isset($eventsByDay[$dateKey]) ? $eventsByDay[$dateKey] : [];
                            @endphp
                            <td class="calendar-day {{ $isToday ? 'today' : '' }} {{ !$isCurrentMonth ? 'other-month' : '' }}">
                                <div class="day-number">{{ $isCurrentMonth ? $cellDay : '' }}</div>
                                <div class="day-events">
                                    @foreach($dayEvents as $item)
                                        @if($item['type'] === 'event')
                                            @php $event = $item['data']; @endphp
                                            <div class="event-item event-{{ strtolower($event->status) }}" 
                                                 onclick="window.location.href='{{ route('events.show', $event) }}'"
                                                 role="button"
                                                 tabindex="0"
                                                 onkeypress="if(event.key==='Enter') window.location.href='{{ route('events.show', $event) }}'"
                                                 title="{{ $event->name }} - {{ $event->formatted_date_time }}">
                                                <small class="event-text">
                                                    @if($event->time)
                                                        @php
                                                            $time = is_string($event->time) ? substr($event->time, 0, 5) : \Carbon\Carbon::parse($event->time)->format('H:i');
                                                        @endphp
                                                        <span class="event-time">{{ $time }}hs</span>
                                                    @endif
                                                    <span class="event-name">{{ \Illuminate\Support\Str::limit($event->name, 15) }}</span>
                                                </small>
                                            </div>
                                        @elseif($item['type'] === 'recurring')
                                            @php $activity = $item['data']; @endphp
                                            <div class="event-item event-recurring" 
                                                 role="button"
                                                 tabindex="0"
                                                 title="{{ $activity['name'] }} - {{ $activity['day_name'] }} {{ substr($activity['time'], 0, 5) }}hs">
                                                <small class="event-text">
                                                    <i class="bi bi-repeat event-icon"></i>
                                                    <span class="event-time">{{ substr($activity['time'], 0, 5) }}hs</span>
                                                    <span class="event-name">{{ \Illuminate\Support\Str::limit($activity['name'], 12) }}</span>
                                                </small>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                        @endfor
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Lista de eventos del mes -->
@if($events->count() > 0)
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Eventos de {{ $startDate->locale('es')->translatedFormat('F Y') }}</h5>
    </div>
    <div class="card-body p-2 p-md-3">
        <div class="list-group list-group-flush">
            @foreach($events as $event)
            <a href="{{ route('events.show', $event) }}" 
               class="list-group-item list-group-item-action event-list-item"
               role="button"
               tabindex="0">
                <div class="d-flex w-100 justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <span class="badge bg-{{ 
                                $event->status === 'FINALIZADO' ? 'success' : 
                                ($event->status === 'CANCELADO' ? 'secondary' : 
                                ($event->status === 'EN_CURSO' ? 'warning' : 'primary')) 
                            }} event-status-badge">{{ $event->status }}</span>
                            <h6 class="mb-0 event-title">{{ $event->name }}</h6>
                        </div>
                        <div class="event-meta mb-1">
                            <small class="text-muted d-flex flex-wrap align-items-center gap-2">
                                <span><i class="bi bi-calendar"></i> {{ $event->date->locale('es')->translatedFormat('d/m/Y') }}</span>
                                @if($event->time)
                                    @php
                                        $time = is_string($event->time) ? $event->time : \Carbon\Carbon::parse($event->time)->format('H:i');
                                    @endphp
                                    <span><i class="bi bi-clock"></i> {{ $time }}hs</span>
                                @endif
                            </small>
                        </div>
                        @if($event->description)
                        <p class="mb-1 text-muted small event-description">{{ \Illuminate\Support\Str::limit($event->description, 80) }}</p>
                        @endif
                        @if($event->products->count() > 0)
                        <p class="mb-0">
                            <small class="text-muted">
                                <i class="bi bi-box-seam"></i> {{ $event->products->count() }} producto(s)
                            </small>
                        </p>
                        @endif
                    </div>
                    <div class="text-end ms-2 flex-shrink-0">
                        <i class="bi bi-chevron-right event-arrow"></i>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</div>
@endif

@push('styles')
<style>
/* Contenedor del calendario */
.calendar-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    width: 100%;
}

.calendar-table {
    width: 100%;
    table-layout: fixed;
    border-collapse: separate;
    border-spacing: 0;
}

/* Headers del calendario */
.calendar-header {
    width: calc(100% / 7);
    padding: 0.5rem 0.25rem !important;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    background-color: rgba(0, 0, 0, 0.05);
}

@media (min-width: 768px) {
    .calendar-header {
        padding: 0.75rem 0.5rem !important;
        font-size: 0.875rem;
    }
}

/* Celdas del calendario */
.calendar-day {
    width: calc(100% / 7);
    height: 80px;
    vertical-align: top;
    padding: 4px;
    position: relative;
    border: 1px solid #dee2e6;
    word-wrap: break-word;
}

@media (min-width: 768px) {
    .calendar-day {
        height: 120px;
        padding: 8px;
    }
}

.calendar-day.other-month {
    background-color: #f8f9fa;
    color: #adb5bd;
}

.calendar-day.today {
    background-color: #e7f3ff;
    border: 2px solid #0d6efd !important;
    font-weight: 600;
}

.calendar-day.today .day-number {
    color: #0d6efd;
}

/* Número del día */
.day-number {
    font-weight: bold;
    margin-bottom: 2px;
    font-size: 0.875rem;
    line-height: 1.2;
}

@media (min-width: 768px) {
    .day-number {
        font-size: 1.1em;
        margin-bottom: 5px;
    }
}

/* Contenedor de eventos */
.day-events {
    max-height: 60px;
    overflow-y: auto;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
}

@media (min-width: 768px) {
    .day-events {
        max-height: 80px;
    }
}

/* Scrollbar personalizado para eventos */
.day-events::-webkit-scrollbar {
    width: 3px;
}

.day-events::-webkit-scrollbar-track {
    background: transparent;
}

.day-events::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 3px;
}

/* Items de eventos */
.event-item {
    padding: 3px 4px;
    margin-bottom: 2px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.65rem;
    line-height: 1.3;
    display: block;
    width: 100%;
    box-sizing: border-box;
    transition: all 0.2s ease;
    touch-action: manipulation;
    -webkit-tap-highlight-color: rgba(0, 0, 0, 0.1);
}

@media (min-width: 768px) {
    .event-item {
        padding: 4px 6px;
        font-size: 0.75rem;
        margin-bottom: 3px;
    }
}

.event-item:active,
.event-item:focus {
    outline: 2px solid rgba(13, 110, 253, 0.5);
    outline-offset: 1px;
}

.event-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}

.event-text {
    display: flex;
    align-items: center;
    gap: 3px;
    white-space: nowrap;
    overflow: hidden;
}

.event-time {
    font-weight: 600;
    flex-shrink: 0;
}

.event-name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    flex: 1;
    min-width: 0;
}

.event-icon {
    font-size: 0.7em;
    flex-shrink: 0;
}

/* Colores de eventos */
.event-programado {
    background-color: #0d6efd;
    color: white;
}

.event-en_curso {
    background-color: #ffc107;
    color: #000;
}

.event-finalizado {
    background-color: #198754;
    color: white;
}

.event-cancelado {
    background-color: #6c757d;
    color: white;
}

.event-recurring {
    background-color: #6f42c1;
    color: white;
    border-left: 3px solid #5a32a3;
}

/* Mejoras para móvil */
@media (max-width: 767.98px) {
    .calendar-container {
        margin: 0 -15px;
        padding: 0 15px;
    }
    
    .calendar-table {
        font-size: 0.875rem;
    }
    
    .calendar-day {
        min-height: 80px;
    }
    
    .event-item {
        font-size: 0.6rem;
        padding: 2px 3px;
    }
    
    .event-time {
        font-size: 0.7em;
    }
    
    /* Ocultar algunos elementos en móvil para ahorrar espacio */
    .event-name {
        max-width: 60px;
    }
}

/* Mejoras de accesibilidad */
@media (prefers-reduced-motion: reduce) {
    .event-item {
        transition: none;
    }
}

/* Mejoras para pantallas muy pequeñas */
@media (max-width: 575.98px) {
    .calendar-day {
        height: 70px;
        padding: 2px;
    }
    
    .day-number {
        font-size: 0.75rem;
    }
    
    .day-events {
        max-height: 50px;
    }
    
    .event-item {
        font-size: 0.55rem;
        padding: 1px 2px;
    }
    
    .event-name {
        max-width: 50px;
    }
}

/* Estilos para lista de eventos */
.event-list-item {
    padding: 0.75rem !important;
    border-left: 3px solid transparent;
    transition: all 0.2s ease;
    touch-action: manipulation;
    -webkit-tap-highlight-color: rgba(0, 0, 0, 0.1);
}

.event-list-item:hover,
.event-list-item:focus {
    background-color: rgba(13, 110, 253, 0.05);
    border-left-color: #0d6efd;
    transform: translateX(2px);
}

.event-list-item:active {
    background-color: rgba(13, 110, 253, 0.1);
}

.event-status-badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
    white-space: nowrap;
}

.event-title {
    font-size: 1rem;
    font-weight: 600;
    word-break: break-word;
}

.event-meta {
    font-size: 0.85rem;
}

.event-description {
    font-size: 0.875rem;
    line-height: 1.4;
}

.event-arrow {
    font-size: 1.25rem;
    color: #6c757d;
    transition: transform 0.2s ease;
}

.event-list-item:hover .event-arrow,
.event-list-item:focus .event-arrow {
    transform: translateX(3px);
    color: #0d6efd;
}

/* Mejoras móvil para lista de eventos */
@media (max-width: 767.98px) {
    .event-list-item {
        padding: 0.625rem !important;
    }
    
    .event-title {
        font-size: 0.9rem;
    }
    
    .event-status-badge {
        font-size: 0.65rem;
        padding: 0.2rem 0.4rem;
    }
    
    .event-meta {
        font-size: 0.8rem;
    }
    
    .event-description {
        font-size: 0.8rem;
    }
    
    .event-arrow {
        font-size: 1.1rem;
    }
}

/* Mejoras de accesibilidad para lista */
@media (prefers-reduced-motion: reduce) {
    .event-list-item,
    .event-arrow {
        transition: none;
    }
}

/* Estilos para lista de alertas */
.alert-list {
    list-style: none;
    padding-left: 0;
}

.alert-list li:last-child {
    border-bottom: none !important;
    padding-bottom: 0 !important;
    margin-bottom: 0 !important;
}

.alert h5 {
    font-weight: 700;
    font-size: 1.25rem;
}

.alert p {
    font-size: 1rem;
    line-height: 1.6;
}

.alert ul {
    font-size: 0.95rem;
}

/* Mejoras móvil para alertas */
@media (max-width: 767.98px) {
    .alert {
        padding: 1rem !important;
    }
    
    .alert h5 {
        font-size: 1.1rem;
    }
    
    .alert p {
        font-size: 0.9rem;
    }
    
    .alert ul {
        font-size: 0.85rem;
    }
}
</style>
@endpush
@endsection

