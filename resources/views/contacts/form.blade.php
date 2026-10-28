@extends('layouts.app')

@section('title', $contact->exists ? 'Editar contacto' : 'Nuevo contacto')

@section('content')
@include('community._head', ['icon' => 'bi-person-lines-fill', 'title' => $contact->exists ? $contact->name : 'Nuevo contacto', 'back' => route('contacts.index')])

<form method="POST" action="{{ $contact->exists ? route('contacts.update', $contact) : route('contacts.store') }}">
    @csrf @if($contact->exists) @method('PUT') @endif
    <div class="card mb-3"><div class="card-body row g-3">
        <div class="col-md-6"><label class="form-label">Nombre</label><input name="name" class="form-control" required value="{{ old('name', $contact->name) }}"></div>
        <div class="col-md-3"><label class="form-label">Tipo</label><select name="kind" class="form-select">@foreach(\App\Models\Contact::KINDS as $k => $l)<option value="{{ $k }}" @selected(old('kind', $contact->kind) === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Alias</label><input name="alias" class="form-control" value="{{ old('alias', $contact->alias) }}"></div>
        <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $contact->email) }}"></div>
        <div class="col-md-4"><label class="form-label">Teléfono</label><input name="phone" class="form-control" value="{{ old('phone', $contact->phone) }}"></div>
        <div class="col-md-2"><label class="form-label">DNI</label><input name="document" class="form-control" value="{{ old('document', $contact->document) }}"></div>
        <div class="col-md-2"><label class="form-label">Nacimiento</label><input type="date" name="birth_date" class="form-control" value="{{ old('birth_date', $contact->birth_date?->toDateString()) }}"></div>
        <div class="col-md-6"><label class="form-label">Etiquetas</label><input name="tags" class="form-control" value="{{ old('tags', $tagString) }}" placeholder="Separadas por coma: percusión, zona sur"></div>
        <div class="col-md-6"><label class="form-label">Grupos</label>
            <select name="group_ids[]" class="form-select" multiple size="3">@foreach($groups as $g)<option value="{{ $g->id }}" @selected($contact->exists && $contact->groups->contains($g))>{{ $g->name }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label">Notas</label><textarea name="notes" class="form-control" rows="2">{{ old('notes', $contact->notes) }}</textarea></div>
        <div class="col-12">
            <div class="form-check"><input type="checkbox" class="form-check-input" name="accepts_marketing" value="1" id="am" @checked(old('accepts_marketing', $contact->accepts_marketing))>
                <label class="form-check-label" for="am">Aceptó recibir comunicaciones y novedades</label>
                @if($contact->marketing_consent_at)<span class="small text-muted">(desde {{ $contact->marketing_consent_at->format('d/m/Y') }})</span>@endif</div>
            <div class="form-check"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="ia" @checked(old('is_active', $contact->is_active ?? true))>
                <label class="form-check-label" for="ia">Activo</label></div>
        </div>
    </div></div>
    <button class="btn btn-primary">Guardar</button>
</form>
@if($contact->exists)
<form method="POST" action="{{ route('contacts.destroy', $contact) }}" class="mt-3" onsubmit="return confirm('¿Desactivar este contacto? Su historial se conserva.')">@csrf @method('DELETE')
    <button class="btn btn-outline-danger btn-sm">Desactivar</button></form>
@endif
@endsection
