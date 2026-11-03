@extends('layouts.app')

@section('title', 'Nueva comunicación')

@section('content')
@include('community._head', ['icon' => 'bi-megaphone', 'title' => 'Nueva comunicación', 'back' => route('campaigns.index')])
<form method="POST" action="{{ route('campaigns.store') }}">
    @csrf
    <div class="row g-4">
        <div class="col-lg-7"><div class="card"><div class="card-body row g-3">
            <div class="col-12"><label class="form-label">Título</label><input name="title" class="form-control" required value="{{ old('title') }}" placeholder="Abrimos inscripción al taller de percusión"></div>
            <div class="col-12"><label class="form-label">Mensaje</label><textarea name="body" rows="6" class="form-control" required>{{ old('body') }}</textarea></div>
            <div class="col-12"><label class="form-label">Enlace (opcional)</label><input type="url" name="link" class="form-control" value="{{ old('link') }}" placeholder="https://"></div>
            <div class="col-12 form-check ms-1"><input type="checkbox" class="form-check-input" name="send_email" value="1" id="se"><label class="form-check-label" for="se">También enviar por correo a quienes tengan email</label></div>
        </div></div></div>
        <div class="col-lg-5"><div class="card"><div class="card-body">
            <h6>¿A quién?</h6>
            <p class="small text-muted">Sin filtros, llega a todos los contactos activos que aceptaron comunicaciones. Con cuenta reciben un aviso en la app.</p>
            <label class="form-label small">Tipos</label>
            <select name="kind[]" class="form-select mb-2" multiple size="4">@foreach(\App\Models\Contact::KINDS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
            @if($tags->isNotEmpty())<label class="form-label small">Etiquetas</label>
            <select name="tag_id[]" class="form-select mb-2" multiple size="3">@foreach($tags as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>@endif
            @if($groups->isNotEmpty())<label class="form-label small">Grupos</label>
            <select name="group_id[]" class="form-select mb-2" multiple size="3">@foreach($groups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach</select>@endif
            @if($workshops->isNotEmpty())<label class="form-label small">Inscriptos en</label>
            <select name="workshop_id[]" class="form-select mb-2" multiple size="3">@foreach($workshops as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach</select>@endif
        </div></div></div>
    </div>
    <button class="btn btn-primary mt-3">Guardar borrador y ver destinatarios</button>
</form>
@endsection
