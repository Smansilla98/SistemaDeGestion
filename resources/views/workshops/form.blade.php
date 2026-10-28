@extends('layouts.app')

@section('title', $workshop->exists ? 'Editar taller' : 'Nuevo taller')

@section('content')
@include('community._head', ['icon' => 'bi-easel', 'title' => $workshop->exists ? 'Editar: '.$workshop->name : 'Nuevo taller',
    'back' => $workshop->exists ? route('workshops.show', $workshop) : route('workshops.index')])

<form method="POST" action="{{ $workshop->exists ? route('workshops.update', $workshop) : route('workshops.store') }}">
    @csrf
    @if($workshop->exists) @method('PUT') @endif
    <div class="card mb-4"><div class="card-body row g-3">
        <div class="col-md-8"><label class="form-label">Nombre</label><input name="name" class="form-control" required value="{{ old('name', $workshop->name) }}"></div>
        <div class="col-md-4"><label class="form-label">Estado</label>
            <select name="status" class="form-select">@foreach(\App\Models\Workshop::STATUSES as $k => $l)<option value="{{ $k }}" @selected(old('status', $workshop->status) === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label">Descripción</label><textarea name="description" rows="3" class="form-control">{{ old('description', $workshop->description) }}</textarea></div>
        <div class="col-md-6"><label class="form-label">Profe / responsable</label>
            <select name="teacher_contact_id" class="form-select"><option value="">—</option>
                @foreach($teachers as $t)<option value="{{ $t->id }}" @selected((int) old('teacher_contact_id', $workshop->teacher_contact_id) === $t->id)>{{ $t->name }} ({{ $t->kindLabel() }})</option>@endforeach
            </select>
            <div class="form-text">Los profes son contactos. Si tiene cuenta, ve sus talleres y toma asistencia.</div></div>
        <div class="col-md-6"><label class="form-label">Lugar / sala</label><input name="room" class="form-control" value="{{ old('room', $workshop->room) }}"></div>
        <div class="col-md-3"><label class="form-label">Cupo</label><input type="number" min="1" name="capacity" class="form-control" value="{{ old('capacity', $workshop->capacity) }}" placeholder="Sin límite"></div>
        <div class="col-md-3"><label class="form-label">Arancel ($)</label><input name="fee" class="form-control" value="{{ old('fee', $workshop->fee_cents ? number_format($workshop->fee_cents / 100, 2, ',', '.') : '') }}" placeholder="0"></div>
        <div class="col-md-3"><label class="form-label">Desde</label><input type="date" name="starts_on" class="form-control" value="{{ old('starts_on', $workshop->starts_on?->toDateString()) }}"></div>
        <div class="col-md-3"><label class="form-label">Hasta</label><input type="date" name="ends_on" class="form-control" value="{{ old('ends_on', $workshop->ends_on?->toDateString()) }}"></div>
        @if($rules->isNotEmpty())
        <div class="col-md-6"><label class="form-label">Reparto de lo recaudado</label>
            <select name="distribution_rule_id" class="form-select"><option value="">Sin regla</option>
                @foreach($rules as $r)<option value="{{ $r->id }}" @selected((int) old('distribution_rule_id', $workshop->distribution_rule_id) === $r->id)>{{ $r->name }} — {{ $r->summary() }}</option>@endforeach
            </select></div>
        @endif
    </div></div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Horarios semanales</h5></div>
        <div class="card-body">
            @php $rows = $schedules->map(fn ($s) => ['weekday' => $s->weekday, 'start_time' => substr($s->start_time, 0, 5), 'end_time' => substr($s->end_time, 0, 5)])->values()->all(); @endphp
            @for($i = 0; $i < max(3, count($rows) + 1); $i++)
            <div class="row g-2 mb-2">
                <div class="col-5"><select name="schedules[{{ $i }}][weekday]" class="form-select"><option value="">Día</option>
                    @foreach(\App\Models\WorkshopSchedule::WEEKDAYS as $n => $d)<option value="{{ $n }}" @selected(($rows[$i]['weekday'] ?? null) === $n)>{{ $d }}</option>@endforeach</select></div>
                <div class="col-3"><input type="time" name="schedules[{{ $i }}][start_time]" class="form-control" value="{{ $rows[$i]['start_time'] ?? '' }}"></div>
                <div class="col-3"><input type="time" name="schedules[{{ $i }}][end_time]" class="form-control" value="{{ $rows[$i]['end_time'] ?? '' }}"></div>
            </div>
            @endfor
            <div class="form-text">Cambiar un horario no borra las clases ya creadas ni su asistencia.</div>
        </div>
    </div>
    <button class="btn btn-primary">Guardar</button>
</form>
@endsection
