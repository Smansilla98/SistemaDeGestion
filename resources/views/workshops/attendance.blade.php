@extends('layouts.app')

@section('title', 'Asistencia')

@section('content')
@include('community._head', ['icon' => 'bi-check2-square', 'title' => 'Asistencia — '.$workshop->name, 'back' => route('workshops.show', $workshop),
    'subtitle' => $session->date->locale('es')->translatedFormat('l d/m/Y').' · '.substr($session->start_time, 0, 5)])

<form method="POST" action="{{ route('workshops.attendance.store', [$workshop, $session]) }}">
    @csrf
    <div class="card mb-3">
        <ul class="list-group list-group-flush">
        @forelse($enrollments as $e)
            <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>{{ $e->contact->name }}</span>
                <div class="btn-group" role="group">
                    @foreach($statuses as $k => $label)
                    <input type="radio" class="btn-check" name="status[{{ $e->contact_id }}]" id="a{{ $e->contact_id }}{{ $k }}" value="{{ $k }}"
                        @checked(($current[$e->contact_id] ?? 'PRESENTE') === $k)>
                    <label class="btn btn-sm btn-outline-{{ ['PRESENTE' => 'success', 'TARDE' => 'info', 'AUSENTE' => 'danger', 'JUSTIFICADA' => 'secondary'][$k] }}" for="a{{ $e->contact_id }}{{ $k }}">{{ $label }}</label>
                    @endforeach
                </div>
            </li>
        @empty
            <li class="list-group-item text-muted">No hay inscripciones activas.</li>
        @endforelse
        </ul>
    </div>
    @if($enrollments->isNotEmpty())<button class="btn btn-primary">Guardar asistencia</button>@endif
</form>
@endsection
