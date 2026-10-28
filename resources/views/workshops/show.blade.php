@extends('layouts.app')

@section('title', $workshop->name)

@php $money = fn ($c) => \App\Services\Finance\ActivityFinanceService::money((int) $c); @endphp

@section('content')
@include('community._head', ['icon' => 'bi-easel', 'title' => $workshop->name, 'back' => route('workshops.index'),
    'subtitle' => ($workshop->teacher?->name ?? 'Sin profe').($workshop->room ? ' · '.$workshop->room : '').' · '.(\App\Models\Workshop::STATUSES[$workshop->status] ?? $workshop->status),
    'actions' => ($canManage ? '<a href="'.route('workshops.edit', $workshop).'" class="btn btn-outline-light"><i class="bi bi-pencil"></i> Editar</a>' : '')
        .($canFinance ? ' <a href="'.route('finance.activity', ['workshop', $workshop->id]).'" class="btn btn-outline-light"><i class="bi bi-pie-chart"></i> Finanzas</a>' : '')])

@if($absences->isNotEmpty())
<div class="alert alert-warning">
    <strong><i class="bi bi-exclamation-triangle"></i> Para contactar:</strong>
    @foreach($absences as $a)
        {{ $a['contact']->name }} ({{ $a['absences'] }} faltas seguidas)@if($a['contact']->whatsappNumber()) — <a href="https://wa.me/{{ $a['contact']->whatsappNumber() }}" target="_blank" rel="noopener">WhatsApp</a>@endif{{ $loop->last ? '' : ' · ' }}
    @endforeach
</div>
@endif

<div class="row g-4">
    <div class="col-xl-7">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between">
                <h5 class="mb-0">Inscripciones</h5>
                <span class="small text-muted">{{ $enrollments->where('status', 'ACTIVA')->count() }}{{ $workshop->capacity ? ' de '.$workshop->capacity : '' }} lugares ocupados</span>
            </div>
            @if($canManage)
            <div class="card-body border-bottom">
                <form method="POST" action="{{ route('workshops.enroll', $workshop) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-5"><label class="form-label small">Contacto existente</label>
                        <select name="contact_id" class="form-select"><option value="">— Persona nueva —</option>
                            @foreach($contacts as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label small">o nombre nuevo</label><input name="new_name" class="form-control" placeholder="Nombre y apellido"></div>
                    <div class="col-md-3"><label class="form-label small">Teléfono</label><input name="new_phone" class="form-control"></div>
                    <div class="col-12"><button class="btn btn-primary btn-sm"><i class="bi bi-person-plus"></i> Inscribir</button>
                        <span class="small text-muted ms-2">Si no hay cupo queda en lista de espera; al liberarse un lugar pasa sola y se le avisa.</span></div>
                </form>
            </div>
            @endif
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead><tr><th>Persona</th><th>Estado</th>@if($workshop->fee_cents > 0)<th>Saldo</th>@endif<th></th></tr></thead>
                    <tbody>
                    @forelse($enrollments as $e)
                    <tr class="{{ $e->status === 'CANCELADA' ? 'text-muted' : '' }}">
                        <td>{{ $e->contact->name }} @if($e->contact->user_id)<i class="bi bi-person-check text-success" title="Tiene cuenta"></i>@endif
                            @if($e->contact->phone)<div class="small text-muted">{{ $e->contact->phone }}</div>@endif</td>
                        <td><span class="badge bg-{{ ['ACTIVA' => 'success', 'EN_ESPERA' => 'warning', 'CANCELADA' => 'secondary', 'FINALIZADA' => 'info'][$e->status] ?? 'secondary' }}">
                            {{ \App\Models\WorkshopEnrollment::LABELS[$e->status] ?? $e->status }}{{ $e->waitlist_position ? ' #'.$e->waitlist_position : '' }}</span></td>
                        @if($workshop->fee_cents > 0)<td>@isset($balances[$e->id]){{ $balances[$e->id]['pending_cents'] > 0 ? 'Debe '.$money($balances[$e->id]['pending_cents']) : 'Al día' }}@endisset</td>@endif
                        <td class="text-end">
                            @if($canManage && in_array($e->status, ['ACTIVA', 'EN_ESPERA'], true))
                            <form method="POST" action="{{ route('workshops.enrollments.cancel', [$workshop, $e]) }}" onsubmit="return confirm('¿Cancelar esta inscripción?')">@csrf
                                <button class="btn btn-sm btn-outline-danger">Cancelar</button></form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">Sin inscripciones todavía.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0">Evaluaciones</h5></div>
            @if($canAttend)
            <div class="card-body border-bottom">
                <form method="POST" action="{{ route('workshops.evaluations.store', $workshop) }}" class="row g-2">
                    @csrf
                    <div class="col-md-4"><select name="contact_id" class="form-select" required><option value="">Alumno/a</option>
                        @foreach($enrollments->where('status', 'ACTIVA') as $e)<option value="{{ $e->contact_id }}">{{ $e->contact->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><input name="title" class="form-control" placeholder="Instancia (ej. Muestra final)" required></div>
                    <div class="col-md-2"><input name="grade" class="form-control" placeholder="Nota"></div>
                    <div class="col-md-2"><input type="date" name="evaluated_on" class="form-control" value="{{ today()->toDateString() }}" required></div>
                    <div class="col-md-10"><input name="comment" class="form-control" placeholder="Devolución (opcional)"></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">Guardar</button></div>
                </form>
            </div>
            @endif
            <ul class="list-group list-group-flush">
                @forelse($evaluations as $ev)
                <li class="list-group-item"><strong>{{ $ev->contact->name }}</strong> — {{ $ev->title }} @if($ev->grade)<span class="badge bg-primary">{{ $ev->grade }}</span>@endif
                    <span class="small text-muted">{{ $ev->evaluated_on->format('d/m/Y') }}</span>@if($ev->comment)<div class="small">{{ $ev->comment }}</div>@endif</li>
                @empty
                <li class="list-group-item text-muted">Sin evaluaciones.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Clases</h5>
                <div class="small text-muted">@foreach($workshop->schedules as $s){{ $s->label() }}{{ $loop->last ? '' : ' · ' }}@endforeach</div></div>
            @if($canManage)
            <div class="card-body border-bottom">
                <form method="POST" action="{{ route('workshops.sessions.generate', $workshop) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-5"><label class="form-label small">Desde</label><input type="date" name="from" class="form-control form-control-sm" value="{{ today()->toDateString() }}"></div>
                    <div class="col-5"><label class="form-label small">Hasta</label><input type="date" name="to" class="form-control form-control-sm" value="{{ today()->addWeeks(8)->toDateString() }}"></div>
                    <div class="col-2"><button class="btn btn-sm btn-primary w-100" title="Crear clases">+</button></div>
                </form>
            </div>
            @endif
            <ul class="list-group list-group-flush">
                @forelse($sessions as $s)
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="{{ $s->status === 'CANCELADA' ? 'text-decoration-line-through text-muted' : '' }}">{{ $s->date->locale('es')->translatedFormat('D d/m') }} {{ substr($s->start_time, 0, 5) }}</strong>
                            @if($s->status === 'REPROGRAMADA')<span class="badge bg-info">Reprogramada{{ $s->original_date ? ' (era '.$s->original_date->format('d/m').')' : '' }}</span>@endif
                            @if($s->status === 'CANCELADA')<span class="badge bg-secondary">Suspendida</span>@endif
                            @if($s->present_count)<span class="small text-muted">· {{ $s->present_count }} presentes</span>@endif
                        </div>
                        <div class="d-flex gap-1">
                            @if($canAttend && $s->status !== 'CANCELADA')<a href="{{ route('workshops.attendance', [$workshop, $s]) }}" class="btn btn-sm btn-outline-primary" title="Asistencia"><i class="bi bi-check2-square"></i></a>@endif
                            @if($canManage && $s->status !== 'CANCELADA')
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#s{{ $s->id }}" title="Reprogramar o suspender"><i class="bi bi-three-dots"></i></button>
                            @endif
                        </div>
                    </div>
                    @if($canManage && $s->status !== 'CANCELADA')
                    <div class="collapse mt-2" id="s{{ $s->id }}">
                        <form method="POST" action="{{ route('workshops.sessions.update', [$workshop, $s]) }}" class="row g-1">
                            @csrf
                            <div class="col-4"><input type="date" name="date" class="form-control form-control-sm" value="{{ $s->date->toDateString() }}"></div>
                            <div class="col-3"><input type="time" name="start_time" class="form-control form-control-sm" value="{{ substr($s->start_time, 0, 5) }}"></div>
                            <div class="col-3"><input type="time" name="end_time" class="form-control form-control-sm" value="{{ substr($s->end_time, 0, 5) }}"></div>
                            <div class="col-12"><input name="note" class="form-control form-control-sm" placeholder="Motivo (se envía en el aviso)"></div>
                            <div class="col-6"><button name="action" value="reschedule" class="btn btn-sm btn-primary w-100">Reprogramar</button></div>
                            <div class="col-6"><button name="action" value="cancel" class="btn btn-sm btn-outline-danger w-100">Suspender</button></div>
                        </form>
                    </div>
                    @endif
                </li>
                @empty
                <li class="list-group-item text-muted">No hay clases en estas semanas. @if($canManage)Creálas desde los horarios.@endif</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
