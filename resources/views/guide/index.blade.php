@extends('layouts.app')

@section('title', 'Guía de comunicación')

@push('styles')
<style>
    .guide-toc { position: sticky; top: 84px; }
    .guide-toc a { display: flex; gap: .5rem; align-items: center; padding: .4rem .6rem; border-radius: 8px; color: #222; text-decoration: none; font-size: .92rem; }
    .guide-toc a:hover { background: #f0f0f0; }
    .guide-body { font-size: 1rem; line-height: 1.6; color: #222; }
    .guide-body table { width: 100%; margin: .75rem 0 1rem; border-collapse: collapse; font-size: .93rem; }
    .guide-body th, .guide-body td { border-bottom: 1px solid #e5e5e5; padding: .45rem .5rem; vertical-align: top; text-align: left; }
    .guide-body th { background: #f5f5f5; }
    .guide-body ul, .guide-body ol { padding-left: 1.25rem; }
    .guide-body li { margin-bottom: .25rem; }
    .guide-body ul:has(input[type=checkbox]) { list-style: none; padding-left: .25rem; }
    .guide-body input[type=checkbox] { margin-right: .4rem; }
    .guide-section { scroll-margin-top: 90px; }
</style>
@endpush

@section('content')
@include('community._head', ['icon' => 'bi-megaphone', 'title' => 'Guía de comunicación',
    'subtitle' => 'Qué publicar, cómo, cuándo y con qué formato. Un manual interno para que todo lo que sale de '.\App\Support\Branding::name().' se vea parte de lo mismo.',
    'actions' => $canEdit ? '<a href="'.route('guide.create').'" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nueva sección</a>' : null])

@if(count($audiences) > 1)
<div class="mb-3 d-flex gap-2 flex-wrap">
    <a href="{{ route('guide.index') }}" class="btn btn-sm btn-{{ $filter === '' ? 'primary' : 'outline-light' }}">Todas las secciones</a>
    @foreach($audiences as $k => $l)<a href="{{ route('guide.index', ['para' => $k]) }}" class="btn btn-sm btn-{{ $filter === $k ? 'primary' : 'outline-light' }}">{{ $l }}</a>@endforeach
</div>
@endif

@if($sections->isEmpty())
    <div class="card"><div class="card-body text-center py-5">
        <i class="bi bi-journal-text display-5 text-muted"></i>
        <p class="mt-3 mb-3">Todavía no hay una guía cargada.</p>
        @if($canEdit)
        <form method="POST" action="{{ route('guide.install') }}">@csrf
            <button class="btn btn-primary"><i class="bi bi-magic"></i> Cargar la guía base</button>
        </form>
        <p class="small text-muted mt-2 mb-0">Diez secciones listas para editar: pilares de contenido, semana tipo, cómo escribir, formatos, fotos, emprendedores y más.</p>
        @endif
    </div></div>
@else
<div class="row g-4">
    <div class="col-lg-3 d-none d-lg-block">
        <div class="card guide-toc"><div class="card-body p-2">
            @foreach($sections as $s)<a href="#{{ $s->slug }}"><i class="bi {{ $s->icon }}"></i> {{ $s->title }}</a>@endforeach
        </div></div>
    </div>
    <div class="col-lg-9">
        @foreach($sections as $s)
        <article class="card mb-4 guide-section" id="{{ $s->slug }}">
            <div class="card-header d-flex justify-content-between align-items-start gap-2 flex-wrap">
                <div>
                    <h2 class="h5 mb-1"><i class="bi {{ $s->icon }}"></i> {{ $s->title }}</h2>
                    @if($s->summary)<div class="small text-muted">{{ $s->summary }}</div>@endif
                </div>
                <div class="d-flex gap-1 align-items-center">
                    @if($s->audience !== 'TODOS')<span class="badge bg-secondary">{{ \App\Models\GuideSection::AUDIENCES[$s->audience] }}</span>@endif
                    @if($canEdit)
                    <form method="POST" action="{{ route('guide.move', $s) }}">@csrf<input type="hidden" name="dir" value="up"><button class="btn btn-sm btn-outline-secondary" title="Subir" @disabled($loop->first)><i class="bi bi-arrow-up"></i></button></form>
                    <form method="POST" action="{{ route('guide.move', $s) }}">@csrf<input type="hidden" name="dir" value="down"><button class="btn btn-sm btn-outline-secondary" title="Bajar" @disabled($loop->last)><i class="bi bi-arrow-down"></i></button></form>
                    <a href="{{ route('guide.edit', $s) }}" class="btn btn-sm btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></a>
                    @endif
                </div>
            </div>
            <div class="card-body guide-body">{!! $html($s) !!}</div>
            @if($s->template_id && $canDesign && isset($templates[$s->template_id]))
            <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="small text-muted"><i class="bi bi-palette"></i> Plantilla sugerida: <strong>{{ $templates[$s->template_id] }}</strong></span>
                <a href="{{ route('design.index') }}" class="btn btn-sm btn-primary">Abrir Diseño</a>
            </div>
            @endif
        </article>
        @endforeach
        @if($canEdit)
        <form method="POST" action="{{ route('guide.install') }}" class="text-end">@csrf
            <button class="btn btn-sm btn-outline-light" title="Agrega sólo las secciones base que borraste o que todavía no tenías">Restaurar secciones base que falten</button>
        </form>
        @endif
    </div>
</div>
@endif
@endsection
