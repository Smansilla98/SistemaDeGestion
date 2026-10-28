@extends('layouts.app')

@section('title', $document->title)

@section('content')
@include('community._head', ['icon' => 'bi-file-earmark-text', 'title' => $document->title, 'back' => route('documents.index'),
    'subtitle' => (\App\Models\Document::CATEGORIES[$document->category] ?? $document->category).($document->entrepreneur ? ' · '.$document->entrepreneur->business_name : '')])
<div class="row g-4">
    <div class="col-lg-7"><div class="card">
        <div class="card-header"><h5 class="mb-0">Versiones</h5></div>
        <ul class="list-group list-group-flush">
        @foreach($document->versions as $v)
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span><strong>v{{ $v->version }}</strong> {{ $v->original_name }} <span class="small text-muted">{{ number_format($v->size / 1024, 0, ',', '.') }} KB · {{ $v->created_at->format('d/m/Y H:i') }} · {{ $v->uploader?->name }}</span>
                    @if($v->note)<div class="small">{{ $v->note }}</div>@endif</span>
                <a href="{{ route('documents.download', [$document, $v]) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a>
            </li>
        @endforeach
        </ul>
        @if($canManage)
        <div class="card-body border-top">
            <form method="POST" action="{{ route('documents.versions.store', $document) }}" enctype="multipart/form-data" class="row g-2">@csrf
                <div class="col-md-6"><input type="file" name="file" class="form-control" required></div>
                <div class="col-md-4"><input name="note" class="form-control" placeholder="Qué cambió"></div>
                <div class="col-md-2"><button class="btn btn-primary w-100">Subir</button></div>
            </form>
        </div>
        @endif
    </div></div>
    <div class="col-lg-5"><div class="card"><div class="card-body">
        @if($document->description)<p>{{ $document->description }}</p>@endif
        @if($canManage)
        <form method="POST" action="{{ route('documents.update', $document) }}" class="row g-2">@csrf @method('PUT')
            <div class="col-12"><input name="title" class="form-control" value="{{ $document->title }}" required></div>
            <div class="col-12"><textarea name="description" class="form-control" rows="2" placeholder="Descripción">{{ $document->description }}</textarea></div>
            <div class="col-6"><select name="category" class="form-select">@foreach(\App\Models\Document::CATEGORIES as $k => $l)<option value="{{ $k }}" @selected($document->category === $k)>{{ $l }}</option>@endforeach</select></div>
            <div class="col-6"><select name="visibility" class="form-select">@foreach(\App\Models\Document::VISIBILITIES as $k => $l)<option value="{{ $k }}" @selected($document->visibility === $k)>{{ $l }}</option>@endforeach</select></div>
            <div class="col-12"><select name="status" class="form-select">@foreach(['BORRADOR' => 'Borrador', 'PUBLICADO' => 'Publicado', 'ARCHIVADO' => 'Archivado'] as $k => $l)<option value="{{ $k }}" @selected($document->status === $k)>{{ $l }}</option>@endforeach</select></div>
            <div class="col-12"><button class="btn btn-primary w-100">Guardar</button></div>
        </form>
        @else
        <div class="small text-muted">Publicado {{ $document->published_at?->format('d/m/Y') }}</div>
        @endif
    </div></div></div>
</div>
@endsection
