@extends('layouts.app')

@section('title', 'Menús especiales')

@section('content')
@include('community._head', ['icon' => 'bi-stars', 'title' => 'Menús especiales',
    'subtitle' => 'Carta para una fecha (patria, show, feria): qué se vende, a qué precio y cuánto stock hace falta.'])

@if($active)
<div class="alert alert-info d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span><i class="bi bi-stars"></i> <strong>Hoy rige «{{ $active->name }}»</strong> hasta el {{ $active->ends_on->format('d/m') }}
        · {{ $active->exclusive ? 'sólo se venden sus productos' : 'la carta sigue completa, con los precios del menú' }}.</span>
    <a href="{{ route('special-menus.show', $active) }}" class="btn btn-sm btn-primary">Ver</a>
</div>
@endif

<div class="row g-4">
    <div class="{{ $canEdit ? 'col-lg-8' : 'col-12' }}"><div class="card"><ul class="list-group list-group-flush">
        @forelse($menus as $m)
        @php $state = $m->ends_on->lt(today()) ? ['Terminado', 'secondary'] : ($m->starts_on->gt(today()) ? ['Próximo', 'warning'] : ['Hoy', 'success']); @endphp
        <a href="{{ route('special-menus.show', $m) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2">
            <span><strong>{{ $m->name }}</strong>
                <span class="small text-muted d-block">{{ $m->starts_on->format('d/m/Y') }}@if($m->ends_on->ne($m->starts_on)) al {{ $m->ends_on->format('d/m/Y') }}@endif · {{ $m->items_count }} productos{{ $m->exclusive ? ' · exclusivo' : '' }}</span></span>
            <span class="badge bg-{{ $state[1] }}">{{ $state[0] }}</span>
        </a>
        @empty
        <li class="list-group-item text-center text-muted py-5">Todavía no hay menús especiales.</li>
        @endforelse
    </ul></div><div class="mt-3">{{ $menus->links() }}</div></div>

    @if($canEdit)
    <div class="col-lg-4"><div class="card"><div class="card-header"><h6 class="mb-0">Nuevo menú</h6></div><div class="card-body">
        <form method="POST" action="{{ route('special-menus.store') }}" class="row g-2">@csrf
            <div class="col-12"><input name="name" class="form-control" placeholder="Ej. Locro del 25 de Mayo" value="{{ old('name') }}" required></div>
            <div class="col-6"><label class="form-label small">Desde</label><input type="date" name="starts_on" class="form-control" value="{{ old('starts_on') }}" required></div>
            <div class="col-6"><label class="form-label small">Hasta</label><input type="date" name="ends_on" class="form-control" value="{{ old('ends_on') }}" required></div>
            @if($menus->isNotEmpty())
            <div class="col-12"><label class="form-label small">Productos</label><select name="copy_from" class="form-select">
                <option value="">Elegirlos después</option>
                @foreach($menus as $m)<option value="{{ $m->id }}">Copiar de «{{ $m->name }}»</option>@endforeach
            </select></div>
            @endif
            <div class="col-12"><textarea name="notes" class="form-control" rows="2" placeholder="Notas para la barra y la cocina">{{ old('notes') }}</textarea></div>
            <div class="col-12 form-check ms-1"><input type="checkbox" class="form-check-input" name="exclusive" value="1" id="exclusive"><label class="form-check-label small" for="exclusive">Sólo se venden los productos del menú</label></div>
            <div class="col-12 form-check ms-1"><input type="checkbox" class="form-check-input" name="in_agenda" value="1" id="in_agenda" checked><label class="form-check-label small" for="in_agenda">Avisar en la agenda</label></div>
            <div class="col-12"><button class="btn btn-primary w-100">Crear</button></div>
        </form>
    </div></div></div>
    @endif
</div>
@endsection
