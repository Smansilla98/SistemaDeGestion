@extends('layouts.app')

@section('title', $section->exists ? 'Editar sección' : 'Nueva sección')

@section('content')
@include('community._head', ['icon' => 'bi-megaphone', 'title' => $section->exists ? 'Editar: '.$section->title : 'Nueva sección de la guía', 'back' => route('guide.index')])
<div class="card"><div class="card-body">
    <form method="POST" action="{{ $section->exists ? route('guide.update', $section) : route('guide.store') }}" class="row g-3">
        @csrf @if($section->exists) @method('PUT') @endif
        <div class="col-md-8"><label class="form-label">Título</label><input name="title" class="form-control" value="{{ old('title', $section->title) }}" required maxlength="255"></div>
        <div class="col-md-4"><label class="form-label">Para quién</label><select name="audience" class="form-select">
            @foreach(\App\Models\GuideSection::AUDIENCES as $k => $l)<option value="{{ $k }}" @selected(old('audience', $section->audience) === $k)>{{ $l }}</option>@endforeach
        </select></div>
        <div class="col-12"><label class="form-label">Resumen <span class="text-muted small">(una línea)</span></label><input name="summary" class="form-control" value="{{ old('summary', $section->summary) }}" maxlength="300"></div>
        <div class="col-12">
            <label class="form-label">Contenido</label>
            <textarea name="body" class="form-control font-monospace" rows="18" required maxlength="20000">{{ old('body', $section->body) }}</textarea>
            <div class="form-text">Formato simple: <code>**negrita**</code>, <code>*cursiva*</code>, listas con <code>-</code> o <code>1.</code>, casillas con <code>- [ ]</code> y tablas con <code>| col | col |</code>.</div>
        </div>
        <div class="col-md-4"><label class="form-label">Ícono <span class="text-muted small">(Bootstrap Icons)</span></label><input name="icon" class="form-control" value="{{ old('icon', $section->icon) }}" placeholder="bi-megaphone"></div>
        <div class="col-md-4"><label class="form-label">Plantilla de Diseño sugerida</label><select name="template_id" class="form-select">
            <option value="">Ninguna</option>
            @foreach(collect(json_decode((string) file_get_contents(database_path('data/design-templates.json')), true))->pluck('name', 'id') as $id => $name)
            <option value="{{ $id }}" @selected(old('template_id', $section->template_id) === $id)>{{ $name }}</option>
            @endforeach
        </select></div>
        <div class="col-12 d-flex justify-content-between">
            <button class="btn btn-primary">Guardar</button>
        </div>
    </form>
    @if($section->exists)
    <form method="POST" action="{{ route('guide.destroy', $section) }}" class="mt-3 text-end" onsubmit="return confirm('¿Eliminar esta sección?')">@csrf @method('DELETE')
        <button class="btn btn-outline-danger btn-sm">Eliminar sección</button>
    </form>
    @endif
</div></div>
@endsection
