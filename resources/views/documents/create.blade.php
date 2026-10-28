@extends('layouts.app')

@section('title', 'Subir documento')

@section('content')
@include('community._head', ['icon' => 'bi-upload', 'title' => 'Subir documento', 'back' => route('documents.index')])
<form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="card" style="max-width: 760px"><div class="card-body row g-3">
    @csrf
    <div class="col-md-8"><label class="form-label">Título</label><input name="title" class="form-control" required value="{{ old('title') }}"></div>
    <div class="col-md-4"><label class="form-label">Categoría</label><select name="category" class="form-select">@foreach(\App\Models\Document::CATEGORIES as $k => $l)<option value="{{ $k }}" @selected(old('category') === $k)>{{ $l }}</option>@endforeach</select></div>
    <div class="col-12"><label class="form-label">Descripción</label><textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea></div>
    <div class="col-md-6"><label class="form-label">De un emprendedor (opcional)</label>
        <select name="entrepreneur_id" class="form-select"><option value="">De la organización</option>@foreach($entrepreneurs as $e)<option value="{{ $e->id }}" @selected((int) old('entrepreneur_id', request('emprendedor')) === $e->id)>{{ $e->business_name }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Quién lo ve</label><select name="visibility" class="form-select">@foreach(\App\Models\Document::VISIBILITIES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
    <div class="col-12"><label class="form-label">Archivo (hasta 20 MB)</label><input type="file" name="file" class="form-control" required></div>
    <div class="col-12 form-check ms-1"><input type="checkbox" class="form-check-input" name="publish" value="1" id="pub"><label for="pub" class="form-check-label">Publicar ahora (si lo ven todos los miembros, se les avisa)</label></div>
    <div class="col-12"><button class="btn btn-primary">Guardar</button></div>
</div></form>
@endsection
