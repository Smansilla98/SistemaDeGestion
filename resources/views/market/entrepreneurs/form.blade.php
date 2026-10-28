@extends('layouts.app')

@section('title', $entrepreneur->exists ? 'Editar emprendedor' : 'Nuevo emprendedor')

@php $brand = $entrepreneur->brand ?? []; @endphp

@section('content')
@include('community._head', ['icon' => 'bi-shop-window', 'title' => $entrepreneur->exists ? $entrepreneur->business_name : 'Nuevo emprendedor',
    'back' => $entrepreneur->exists ? route('market.entrepreneurs.show', $entrepreneur) : route('market.entrepreneurs.index')])
<form method="POST" action="{{ $entrepreneur->exists ? route('market.entrepreneurs.update', $entrepreneur) : route('market.entrepreneurs.store') }}">
    @csrf @if($entrepreneur->exists) @method('PUT') @endif
    <div class="card mb-3"><div class="card-body row g-3">
        <div class="col-md-6"><label class="form-label">Nombre del emprendimiento</label><input name="business_name" class="form-control" required value="{{ old('business_name', $entrepreneur->business_name) }}"></div>
        <div class="col-md-6"><label class="form-label">Persona de contacto</label>
            <select name="contact_id" class="form-select"><option value="">{{ $entrepreneur->exists ? '—' : 'Crear contacto con el mismo nombre' }}</option>
                @foreach($contacts as $c)<option value="{{ $c->id }}" @selected((int) old('contact_id', $entrepreneur->contact_id) === $c->id)>{{ $c->name }} ({{ $c->kindLabel() }})</option>@endforeach</select>
            <div class="form-text">Si el contacto tiene cuenta, la persona ve sus productos, consignaciones y documentos.</div></div>
        <div class="col-12"><label class="form-label">Descripción</label><textarea name="description" class="form-control" rows="2">{{ old('description', $entrepreneur->description) }}</textarea></div>
        <div class="col-md-4"><label class="form-label">Alias / CBU para pagos</label><input name="payment_alias" class="form-control" value="{{ old('payment_alias', $entrepreneur->payment_alias) }}"></div>
        <div class="col-md-8"><label class="form-label">Grupos</label>
            <select name="group_ids[]" class="form-select" multiple size="3">@foreach($groups as $g)<option value="{{ $g->id }}" @selected($entrepreneur->exists && $entrepreneur->groups->contains($g))>{{ $g->name }}</option>@endforeach</select></div>
        <div class="col-12 form-check ms-1"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="ia" @checked(old('is_active', $entrepreneur->is_active ?? true))><label for="ia" class="form-check-label">Activo</label></div>
    </div></div>
    <div class="card mb-3"><div class="card-header"><h5 class="mb-0">Identidad de marca</h5><div class="small text-muted">Se usa para generar el manual de marca en PDF.</div></div><div class="card-body row g-3">
        <div class="col-md-6"><label class="form-label">Frase / bajada</label><input name="brand_tagline" class="form-control" value="{{ old('brand_tagline', $brand['tagline'] ?? '') }}"></div>
        <div class="col-md-6"><label class="form-label">Colores (hex, separados por coma)</label><input name="brand_colors" class="form-control" value="{{ old('brand_colors', implode(', ', $brand['colors'] ?? [])) }}" placeholder="#2E7D32, #F9A825"></div>
        <div class="col-md-6"><label class="form-label">Tipografía principal</label><input name="brand_font_primary" class="form-control" value="{{ old('brand_font_primary', $brand['font_primary'] ?? '') }}"></div>
        <div class="col-md-6"><label class="form-label">Tipografía secundaria</label><input name="brand_font_secondary" class="form-control" value="{{ old('brand_font_secondary', $brand['font_secondary'] ?? '') }}"></div>
        <div class="col-12"><label class="form-label">Valores / qué nos identifica</label><textarea name="brand_values" class="form-control" rows="2">{{ old('brand_values', $brand['values'] ?? '') }}</textarea></div>
        <div class="col-12"><label class="form-label">Usos correctos e incorrectos del logo</label><textarea name="brand_usage" class="form-control" rows="3">{{ old('brand_usage', $brand['usage'] ?? '') }}</textarea></div>
    </div></div>
    <button class="btn btn-primary">Guardar</button>
</form>
@endsection
