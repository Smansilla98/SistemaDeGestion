@extends('layouts.app')

@section('title', 'Formularios')

@section('content')
@include('community._head', ['icon' => 'bi-ui-checks', 'title' => 'Formularios',
    'subtitle' => 'Conectá tus Google Forms (o subí el Excel de respuestas) para sumar alumnos inscriptos, talleristas, contactos y actividades de agenda.',
    'actions' => $canManage ? '<a href="'.route('forms.create').'" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Conectar formulario</a>' : null])

@if($sources->isEmpty())
<div class="card"><div class="card-body">
    <h5>¿Cómo funciona?</h5>
    <ol class="mb-0">
        <li>En tu Google Form, pestaña <strong>Respuestas</strong> → <strong>Vincular a Hojas de cálculo</strong>.</li>
        <li>En la planilla: <strong>Compartir</strong> → «Cualquier persona con el enlace: lector» (o Archivo → Compartir → Publicar en la web → CSV).</li>
        <li>Acá tocá <strong>Conectar formulario</strong>, pegá el link de la planilla y elegí para qué es: inscripciones, talleristas, contactos o agenda.</li>
        <li>Indicá qué columna es el nombre, el email, el taller… El sistema lo propone solo. Cada 30 minutos trae las respuestas nuevas.</li>
    </ol>
    <p class="small text-muted mt-2 mb-0">¿No querés compartir la planilla? Descargala como Excel (.xlsx) o CSV y subila: se puede actualizar subiéndola de nuevo, sin duplicar.</p>
</div></div>
@else
<div class="row g-3">
    @foreach($sources as $s)
    <div class="col-md-6 col-xl-4">
        <a href="{{ route('forms.show', $s) }}" class="card h-100 text-decoration-none"><div class="card-body">
            <div class="d-flex justify-content-between"><h5 class="mb-1">{{ $s->name }}</h5>@unless($s->is_active)<span class="badge bg-secondary">Pausado</span>@endunless</div>
            <div class="small text-muted mb-2">{{ $s->targetLabel() }}@if($s->workshop) · {{ $s->workshop->name }}@endif @if($s->category) · {{ $s->category->name }}@endif</div>
            <div class="small"><i class="bi {{ $s->sheet_url ? 'bi-google' : 'bi-file-earmark-spreadsheet' }}"></i>
                {{ $s->sheet_url ? ($s->auto_sync ? 'Google Sheets · automático' : 'Google Sheets · manual') : 'Archivo subido' }}</div>
            <div class="d-flex gap-3 small mt-2">
                <span class="text-success"><i class="bi bi-check2-circle"></i> {{ $s->imported_count }} importadas</span>
                @if($s->error_count)<span class="text-danger"><i class="bi bi-exclamation-circle"></i> {{ $s->error_count }} para revisar</span>@endif
            </div>
            <div class="small text-muted mt-1">{{ $s->last_synced_at ? 'Última importación '.$s->last_synced_at->diffForHumans() : 'Sin importar todavía' }}</div>
            @if($s->last_error)<div class="small text-danger mt-1">{{ $s->last_error }}</div>@endif
        </div></a>
    </div>
    @endforeach
</div>
@endif
@endsection
