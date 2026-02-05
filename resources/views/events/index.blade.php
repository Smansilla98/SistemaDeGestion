@extends('layouts.app')

@section('title', 'Calendario de Eventos')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
                <i class="bi bi-calendar-event"></i> Calendario de Eventos
            </h1>
        </div>
        @can('create', App\Models\Event::class)
        <a href="{{ route('events.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo Evento
        </a>
        @endcan
    </div>
</div>

@if(count($stockAlerts) > 0)
<div class="alert alert-danger mb-4">
    <h5><i class="bi bi-exclamation-triangle-fill"></i> Alertas de Stock para Eventos</h5>
    <p class="mb-2">Los siguientes eventos requieren productos que no tienen suficiente stock:</p>
    <ul class="mb-0">
        @foreach($stockAlerts as $alert)
        <li>
            <strong>{{ $alert['event']->name }}</strong> ({{ $alert['event']->formatted_date_time }}):
            <strong>{{ $alert['product']->name }}</strong> - 
            Stock actual: <span class="badge bg-danger">{{ $alert['current_stock'] }}</span> | 
            Necesario: <span class="badge bg-warning">{{ $alert['expected_quantity'] }}</span> | 
            Faltan: <span class="badge bg-danger">{{ $alert['shortage'] }}</span>
        </li>
        @endforeach
    </ul>
</div>
@endif

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">
                {{ $startDate->locale('es')->translatedFormat('F Y') }}
            </h5>
        </div>
        <div class="btn-group">
            <a href="{{ route('events.index', ['year' => $prevMonth->year, 'month' => $prevMonth->month]) }}" 
               class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-chevron-left"></i> Anterior
            </a>
            <a href="{{ route('events.index') }}" class="btn btn-sm btn-outline-secondary">
                Hoy
            </a>
            <a href="{{ route('events.index', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}" 
               class="btn btn-sm btn-outline-secondary">
                Siguiente <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="calendar-container">
            <table class="table table-bordered calendar-table">
                <thead>
                    <tr>
                        <th class="text-center">Dom</th>
                        <th class="text-center">Lun</th>
                        <th class="text-center">Mar</th>
                        <th class="text-center">Mié</th>
                        <th class="text-center">Jue</th>
                        <th class="text-center">Vie</th>
                        <th class="text-center">Sáb</th>
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
                                                 title="{{ $event->name }} - {{ $event->formatted_date_time }}">
                                                <small>
                                                    @if($event->time)
                                                        @php
                                                            $time = is_string($event->time) ? substr($event->time, 0, 5) : \Carbon\Carbon::parse($event->time)->format('H:i');
                                                        @endphp
                                                        {{ $time }}hs
                                                    @endif
                                                    {{ \Illuminate\Support\Str::limit($event->name, 20) }}
                                                </small>
                                            </div>
                                        @elseif($item['type'] === 'recurring')
                                            @php $activity = $item['data']; @endphp
                                            <div class="event-item event-recurring" 
                                                 title="{{ $activity['name'] }} - {{ $activity['day_name'] }} {{ substr($activity['time'], 0, 5) }}hs">
                                                <small>
                                                    <i class="bi bi-repeat"></i>
                                                    {{ substr($activity['time'], 0, 5) }}hs
                                                    {{ \Illuminate\Support\Str::limit($activity['name'], 18) }}
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
    <div class="card-body">
        <div class="list-group">
            @foreach($events as $event)
            <a href="{{ route('events.show', $event) }}" class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between">
                    <div>
                        <h6 class="mb-1">
                            <span class="badge bg-{{ 
                                $event->status === 'FINALIZADO' ? 'success' : 
                                ($event->status === 'CANCELADO' ? 'secondary' : 
                                ($event->status === 'EN_CURSO' ? 'warning' : 'primary')) 
                            }} me-2">{{ $event->status }}</span>
                            {{ $event->name }}
                        </h6>
                        <p class="mb-1">
                            <i class="bi bi-calendar"></i> {{ $event->date->locale('es')->translatedFormat('l d/m/Y') }}
                            @if($event->time)
                                @php
                                    $time = is_string($event->time) ? $event->time : \Carbon\Carbon::parse($event->time)->format('H:i');
                                @endphp
                                <i class="bi bi-clock ms-2"></i> {{ $time }}hs
                            @endif
                        </p>
                        @if($event->description)
                        <p class="mb-1 text-muted small">{{ \Illuminate\Support\Str::limit($event->description, 100) }}</p>
                        @endif
                        @if($event->products->count() > 0)
                        <p class="mb-0">
                            <small class="text-muted">
                                <i class="bi bi-box-seam"></i> {{ $event->products->count() }} producto(s) relacionado(s)
                            </small>
                        </p>
                        @endif
                    </div>
                    <div class="text-end">
                        <i class="bi bi-chevron-right"></i>
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
.calendar-container {
    overflow-x: auto;
}

.calendar-table {
    width: 100%;
    min-width: 600px;
}

.calendar-day {
    height: 120px;
    vertical-align: top;
    padding: 8px;
    position: relative;
}

.calendar-day.other-month {
    background-color: #f8f9fa;
    color: #adb5bd;
}

.calendar-day.today {
    background-color: #e7f3ff;
    border: 2px solid #0d6efd;
}

.day-number {
    font-weight: bold;
    margin-bottom: 5px;
    font-size: 1.1em;
}

.day-events {
    max-height: 80px;
    overflow-y: auto;
}

.event-item {
    padding: 2px 6px;
    margin-bottom: 2px;
    border-radius: 3px;
    cursor: pointer;
    font-size: 0.75rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.event-item:hover {
    opacity: 0.8;
}

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
</style>
@endpush
@endsection

