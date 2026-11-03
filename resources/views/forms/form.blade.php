@extends('layouts.app')

@section('title', $source->exists ? 'Editar formulario' : 'Conectar formulario')

@section('content')
@include('community._head', ['icon' => 'bi-ui-checks', 'title' => $source->exists ? $source->name : 'Conectar formulario',
    'back' => $source->exists ? route('forms.show', $source) : route('forms.index')])

<form method="POST" action="{{ $source->exists ? route('forms.update', $source) : route('forms.store') }}" enctype="multipart/form-data" class="card" style="max-width: 860px">
    @csrf @if($source->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-7"><label class="form-label">Nombre</label><input name="name" class="form-control" required value="{{ old('name', $source->name) }}" placeholder="Ej. Inscripciones talleres 2027"></div>
        <div class="col-md-5"><label class="form-label">¿Para qué es?</label>
            <select name="target" class="form-select" id="target">
                @foreach(\App\Models\FormSource::TARGETS as $k => $l)<option value="{{ $k }}" @selected(old('target', $source->target) === $k)>{{ $l }}</option>@endforeach
            </select></div>

        <div class="col-12"><label class="form-label">Link de la planilla de respuestas (Google Sheets)</label>
            <input name="sheet_url" type="url" class="form-control" value="{{ old('sheet_url', $source->sheet_url) }}" placeholder="https://docs.google.com/spreadsheets/d/…">
            <div class="form-text">En el Form: Respuestas → Vincular a Hojas de cálculo. Compartí la planilla como «Cualquier persona con el enlace: lector».</div></div>
        @unless($source->exists)
        <div class="col-12"><label class="form-label">…o subí el archivo de respuestas (Excel o CSV)</label>
            <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.ods,.csv"></div>
        @endunless

        <div class="col-md-6" data-for="ALUMNOS"><label class="form-label">Inscribir siempre en este taller</label>
            <select name="workshop_id" class="form-select"><option value="">Según la respuesta (columna «Taller»)</option>
                @foreach($workshops as $w)<option value="{{ $w->id }}" @selected((int) old('workshop_id', $source->workshop_id) === $w->id)>{{ $w->name }}</option>@endforeach
            </select>
            <div class="form-text">Si el form deja elegir varios talleres, se inscribe en cada uno.</div></div>
        <div class="col-md-6" data-for="CONTACTOS"><label class="form-label">Tipo de contacto</label>
            <select name="contact_kind" class="form-select" data-kinds="contact">
                @foreach($contactKinds as $k => $l)<option value="{{ $k }}" @selected(old('contact_kind', $source->contact_kind) === $k)>{{ $l }}</option>@endforeach
            </select></div>
        <div class="col-md-6" data-for="AGENDA"><label class="form-label">Tipo de actividad</label>
            <select name="contact_kind" class="form-select" data-kinds="agenda" disabled>
                @foreach($noticeKinds as $k => $l)<option value="{{ $k }}" @selected(old('contact_kind', $source->contact_kind) === $k)>{{ $l }}</option>@endforeach
            </select></div>
        <div class="col-md-6"><label class="form-label">Categoría por defecto</label>
            <select name="activity_category_id" class="form-select"><option value="">Sin categoría (o según la columna «Categoría»)</option>
                @foreach($categories as $c)<option value="{{ $c->id }}" @selected((int) old('activity_category_id', $source->activity_category_id) === $c->id)>{{ $c->name }}</option>@endforeach
            </select></div>
        <div class="col-md-6"><label class="form-label">Etiquetas para los contactos</label><input name="tags" class="form-control" value="{{ old('tags', $source->tags) }}" placeholder="Ej. inscripción 2027, zona sur"></div>
        <div class="col-12">
            <div class="form-check"><input type="checkbox" class="form-check-input" name="auto_sync" value="1" id="as" @checked(old('auto_sync', $source->auto_sync))>
                <label class="form-check-label" for="as">Traer respuestas nuevas automáticamente (cada 30 minutos, sólo con link de Google)</label></div>
            <div class="form-check"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="ia" @checked(old('is_active', $source->is_active ?? true))>
                <label class="form-check-label" for="ia">Activo</label></div>
        </div>
        <div class="col-12"><button class="btn btn-primary">{{ $source->exists ? 'Guardar' : 'Conectar y ver columnas' }}</button></div>
    </div>
</form>

<script>
    (function () {
        const target = document.getElementById('target');
        function sync() {
            document.querySelectorAll('[data-for]').forEach(el => {
                const on = el.dataset.for === target.value;
                el.style.display = on ? '' : 'none';
                el.querySelectorAll('select, input').forEach(i => i.disabled = !on);
            });
        }
        target.addEventListener('change', sync);
        sync();
    })();
</script>
@endsection
