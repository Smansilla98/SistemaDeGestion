@extends('layouts.app')

@section('title', 'Contactos')

@section('content')
@php
    $actions = ($can['create'] ? '<a href="'.route('contacts.create').'" class="btn btn-primary"><i class="bi bi-person-plus"></i> Nuevo</a> <a href="'.route('contacts.import').'" class="btn btn-outline-light"><i class="bi bi-upload"></i> Importar</a>' : '')
        .($can['export'] ? ' <a href="'.route('contacts.export', request()->query()).'" class="btn btn-outline-light"><i class="bi bi-download"></i> Exportar CSV</a>' : '')
        .($can['campaign'] ? ' <a href="'.route('campaigns.create').'" class="btn btn-outline-light"><i class="bi bi-megaphone"></i> Comunicar</a>' : '');
@endphp
@include('community._head', ['icon' => 'bi-person-lines-fill', 'title' => 'Contactos', 'subtitle' => 'Profes, artistas, alumnos, emprendedores, clientes, proveedores y público. No hace falta que tengan cuenta.', 'actions' => $actions])

<div class="card mb-3"><div class="card-body py-3">
    <form class="row g-2 align-items-end">
        <div class="col-md-3"><input name="q" class="form-control" placeholder="Buscar nombre, alias, email o teléfono" value="{{ $filters['q'] ?? '' }}"></div>
        <div class="col-md-2"><select name="kind" class="form-select"><option value="">Todos los tipos</option>@foreach(\App\Models\Contact::KINDS as $k => $l)<option value="{{ $k }}" @selected(($filters['kind'] ?? '') === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="tag_id" class="form-select"><option value="">Etiqueta</option>@foreach($tags as $t)<option value="{{ $t->id }}" @selected((int) ($filters['tag_id'] ?? 0) === $t->id)>{{ $t->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="group_id" class="form-select"><option value="">Grupo</option>@foreach($groups as $g)<option value="{{ $g->id }}" @selected((int) ($filters['group_id'] ?? 0) === $g->id)>{{ $g->name }} ({{ $g->contacts_count }})</option>@endforeach</select></div>
        <div class="col-md-2"><select name="workshop_id" class="form-select"><option value="">Taller</option>@foreach($workshops as $w)<option value="{{ $w->id }}" @selected((int) ($filters['workshop_id'] ?? 0) === $w->id)>{{ $w->name }}</option>@endforeach</select></div>
        <div class="col-md-1"><button class="btn btn-primary w-100"><i class="bi bi-funnel"></i></button></div>
        <div class="col-12 form-check ms-1"><input type="checkbox" class="form-check-input" name="marketing" value="1" id="mk" @checked(! empty($filters['marketing']))><label class="form-check-label small" for="mk">Sólo quienes aceptan recibir comunicaciones</label></div>
    </form>
</div></div>

<form method="POST" action="{{ route('contacts.groups.add') }}" id="groupForm">@csrf</form>
<div class="card">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead><tr>@if($can['update'])<th></th>@endif<th>Nombre</th><th>Tipo</th><th>Contacto</th><th>Etiquetas / grupos</th><th></th></tr></thead>
            <tbody>
            @forelse($contacts as $c)
            <tr class="{{ $c->is_active ? '' : 'text-muted' }}">
                @if($can['update'])<td><input type="checkbox" name="contact_ids[]" value="{{ $c->id }}" form="groupForm" class="form-check-input"></td>@endif
                <td>{{ $c->name }} @if($c->alias)<span class="text-muted">({{ $c->alias }})</span>@endif @if($c->user_id)<i class="bi bi-person-check text-success" title="Tiene cuenta"></i>@endif
                    @if($c->accepts_marketing)<i class="bi bi-megaphone text-primary" title="Acepta comunicaciones"></i>@endif</td>
                <td>{{ $c->kindLabel() }}</td>
                <td class="small">{{ $c->email }}@if($c->phone)<br>{{ $c->phone }} @if($c->whatsappNumber())<a href="https://wa.me/{{ $c->whatsappNumber() }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i></a>@endif @endif</td>
                <td class="small">@foreach($c->tags as $t)<span class="badge bg-light text-dark">{{ $t->name }}</span> @endforeach @foreach($c->groups as $g)<span class="badge bg-info text-dark">{{ $g->name }}</span> @endforeach</td>
                <td class="text-end text-nowrap">
                    @if($can['update'])<a href="{{ route('contacts.edit', $c) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>@endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-5">No hay contactos con esos filtros.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-between flex-wrap gap-2 align-items-center">
        @if($can['update'] && $groups->isNotEmpty())
        <div class="d-flex gap-2">
            <select name="group_id" form="groupForm" class="form-select form-select-sm w-auto">@foreach($groups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach</select>
            <button form="groupForm" class="btn btn-sm btn-outline-primary">Agregar seleccionados al grupo</button>
        </div>
        @endif
        {{ $contacts->links() }}
    </div>
</div>

@if($can['update'])
<div class="card mt-4" style="max-width: 640px"><div class="card-body">
    <h6>Nuevo grupo</h6>
    <form method="POST" action="{{ route('contacts.groups.store') }}" class="row g-2">@csrf
        <div class="col-md-5"><input name="name" class="form-control" placeholder="Ej. Artistas del ciclo de verano" required></div>
        <div class="col-md-5"><input name="description" class="form-control" placeholder="Descripción (opcional)"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Crear</button></div>
    </form>
</div></div>
@endif
@endsection
