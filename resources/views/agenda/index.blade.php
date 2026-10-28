@extends('layouts.app')

@section('title', 'Agenda')

@section('content')
@php
    $quickLabels = ['workshop' => ['bi-easel', 'Nuevo taller'], 'event' => ['bi-calendar-event', 'Nuevo evento'],
        'cycle' => ['bi-arrow-repeat', 'Ciclo de pedidos'], 'entrepreneur' => ['bi-shop-window', 'Sumar emprendedor']];
    $quickHtml = collect($quick)->map(fn ($url, $k) => '<a href="'.e($url).'" class="btn btn-'.($k === 'workshop' ? 'primary' : 'outline-light').'"><i class="bi '.$quickLabels[$k][0].'"></i> '.$quickLabels[$k][1].'</a>')->implode(' ');
@endphp
@include('community._head', ['icon' => 'bi-calendar-week', 'title' => 'Agenda',
    'subtitle' => 'Clases, eventos y etapas, actividades, entregas, fechas especiales y avisos en un solo lugar.',
    'actions' => $quickHtml !== '' ? $quickHtml : null])

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="btn-group">
        <a href="{{ route('agenda.index', ['vista' => $view, 'desde' => $prev->toDateString()]) }}" class="btn btn-outline-light"><i class="bi bi-chevron-left"></i></a>
        <a href="{{ route('agenda.index', ['vista' => $view]) }}" class="btn btn-outline-light">Hoy</a>
        <a href="{{ route('agenda.index', ['vista' => $view, 'desde' => $next->toDateString()]) }}" class="btn btn-outline-light"><i class="bi bi-chevron-right"></i></a>
    </div>
    <strong class="text-white">{{ $from->locale('es')->translatedFormat('d M') }} – {{ $to->locale('es')->translatedFormat('d M Y') }}</strong>
    <div class="btn-group">
        <a href="{{ route('agenda.index', ['vista' => 'semana', 'desde' => $from->toDateString()]) }}" class="btn btn-{{ $view === 'semana' ? 'primary' : 'outline-light' }}">Semana</a>
        <a href="{{ route('agenda.index', ['vista' => 'mes', 'desde' => $from->toDateString()]) }}" class="btn btn-{{ $view === 'mes' ? 'primary' : 'outline-light' }}">Mes</a>
    </div>
</div>

<div class="row g-4">
    <div class="{{ $canCreate ? 'col-lg-8' : 'col-12' }}">
        <div class="card">
            <ul class="list-group list-group-flush">
            @forelse($days as $date => $items)
                <li class="list-group-item {{ $date === today()->toDateString() ? 'bg-light' : '' }}">
                    <div class="fw-bold mb-1 text-capitalize">{{ \Carbon\Carbon::parse($date)->locale('es')->translatedFormat('l d/m') }}</div>
                    @foreach($items as $i)
                    <div class="d-flex gap-2 align-items-baseline small {{ $i['cancelled'] ? 'text-muted text-decoration-line-through' : '' }}">
                        <span class="text-muted" style="min-width: 3rem">{{ $i['time'] ?? '—' }}</span>
                        <span class="badge bg-secondary">{{ $i['kind'] }}</span>
                        @if($i['url'])<a href="{{ $i['url'] }}">{{ $i['title'] }}</a>@else<span>{{ $i['title'] }}</span>@endif
                        @if($i['detail'])<span class="text-muted">· {{ $i['detail'] }}</span>@endif
                    </div>
                    @endforeach
                </li>
            @empty
                <li class="list-group-item text-center text-muted py-5">No hay nada agendado en este período.</li>
            @endforelse
            </ul>
        </div>
    </div>

    @if($canCreate)
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Agregar aviso o fecha especial</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('agenda.notices.store') }}" class="row g-2">
                    @csrf
                    <div class="col-12"><select name="kind" class="form-select">@foreach(\App\Models\AgendaNotice::KINDS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                    <div class="col-12"><input name="title" class="form-control" placeholder="Título (ej. Menú especial 25 de Mayo)" required></div>
                    <div class="col-12"><textarea name="body" class="form-control" rows="2" placeholder="Detalle (opcional)"></textarea></div>
                    <div class="col-6"><label class="form-label small">Fecha</label><input type="date" name="date" class="form-control" required></div>
                    <div class="col-6"><label class="form-label small">Hasta (opcional)</label><input type="date" name="end_date" class="form-control"></div>
                    <div class="col-6"><label class="form-label small">Hora</label><input type="time" name="time" class="form-control"></div>
                    <div class="col-12 form-check ms-1"><input type="checkbox" class="form-check-input" name="notify" value="1" id="notify"><label for="notify" class="form-check-label">Avisar ahora a todos los miembros</label></div>
                    <div class="col-12"><button class="btn btn-primary w-100">Agregar</button></div>
                </form>
                <div class="small text-muted mt-2">El día anterior se manda un recordatorio automático.</div>
            </div>
        </div>
        @if($notices->isNotEmpty())
        <div class="card"><ul class="list-group list-group-flush">
            @foreach($notices as $n)
            <li class="list-group-item d-flex justify-content-between align-items-center small">
                <span>{{ $n->date->format('d/m') }} · {{ $n->title }}</span>
                <form method="POST" action="{{ route('agenda.notices.destroy', $n) }}" onsubmit="return confirm('¿Quitar este aviso?')">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-x-lg"></i></button></form>
            </li>
            @endforeach
        </ul></div>
        @endif
    </div>
    @endif
</div>
@endsection
