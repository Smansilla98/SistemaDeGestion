@extends('layouts.app')

@section('title', 'Talleres')

@section('content')
@include('community._head', ['icon' => 'bi-easel', 'title' => 'Talleres', 'subtitle' => 'Inscripciones con cupo y lista de espera, clases, asistencia y evaluaciones.',
    'actions' => $canCreate ? '<a href="'.route('workshops.create').'" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nuevo taller</a>' : null])

<div class="row g-3">
@forelse($workshops as $w)
    <div class="col-md-6 col-xl-4">
        <a href="{{ route('workshops.show', $w) }}" class="card h-100 text-decoration-none">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="mb-0">{{ $w->name }}</h5>
                    <span class="badge bg-{{ $w->status === 'ABIERTO' ? 'success' : ($w->status === 'EN_CURSO' ? 'primary' : 'secondary') }}">{{ \App\Models\Workshop::STATUSES[$w->status] ?? $w->status }}</span>
                </div>
                <div class="small text-muted mb-2">{{ $w->teacher?->name ?? 'Sin profe asignado' }}@if($w->room) · {{ $w->room }}@endif</div>
                <div class="small mb-2">@foreach($w->schedules as $s)<span class="badge bg-light text-dark me-1">{{ $s->label() }}</span>@endforeach</div>
                <div class="d-flex gap-3 small">
                    <span><i class="bi bi-people"></i> {{ $w->active_count }}{{ $w->capacity ? ' / '.$w->capacity : '' }} inscriptos</span>
                    @if($w->waiting_count)<span class="text-warning"><i class="bi bi-hourglass-split"></i> {{ $w->waiting_count }} en espera</span>@endif
                </div>
            </div>
        </a>
    </div>
@empty
    <div class="col-12"><div class="card"><div class="card-body text-center text-muted py-5">Todavía no hay talleres.</div></div></div>
@endforelse
</div>
@endsection
