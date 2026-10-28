@extends('layouts.app')

@section('title', 'Documentos')

@section('content')
@include('community._head', ['icon' => 'bi-folder2-open', 'title' => 'Documentos', 'subtitle' => 'Reglamentos, materiales, contratos, logos y manuales de marca, con historial de versiones.',
    'actions' => $canCreate ? '<a href="'.route('documents.create').'" class="btn btn-primary"><i class="bi bi-upload"></i> Subir documento</a>' : null])
<div class="mb-3 d-flex gap-2 flex-wrap">
    <a href="{{ route('documents.index') }}" class="btn btn-sm btn-{{ request('category') ? 'outline-light' : 'primary' }}">Todos</a>
    @foreach(\App\Models\Document::CATEGORIES as $k => $l)<a href="{{ route('documents.index', ['category' => $k]) }}" class="btn btn-sm btn-{{ request('category') === $k ? 'primary' : 'outline-light' }}">{{ $l }}</a>@endforeach
</div>
<div class="card"><ul class="list-group list-group-flush">
    @forelse($documents as $d)
    <a href="{{ route('documents.show', $d) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
        <span><i class="bi bi-file-earmark-text"></i> {{ $d->title }} @if($d->entrepreneur)<span class="small text-muted">· {{ $d->entrepreneur->business_name }}</span>@endif</span>
        <span class="small">{{ \App\Models\Document::CATEGORIES[$d->category] ?? $d->category }} · v{{ $d->versions->max('version') ?? 0 }}
            @if($canManage)<span class="badge bg-{{ $d->status === 'PUBLICADO' ? 'success' : 'secondary' }}">{{ $d->status }}</span> <span class="badge bg-light text-dark">{{ \App\Models\Document::VISIBILITIES[$d->visibility] ?? $d->visibility }}</span>@endif</span>
    </a>
    @empty
    <li class="list-group-item text-center text-muted py-5">No hay documentos disponibles.</li>
    @endforelse
</ul></div>
<div class="mt-3">{{ $documents->links() }}</div>
@endsection
