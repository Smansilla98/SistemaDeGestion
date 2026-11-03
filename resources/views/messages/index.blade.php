@extends('layouts.app')

@section('title', 'Mensajes')

@section('content')
@include('community._head', ['icon' => 'bi-chat-dots', 'title' => 'Mensajes',
    'actions' => $canSend ? '<a href="'.route('messages.create').'" class="btn btn-primary"><i class="bi bi-pencil-square"></i> Nuevo mensaje</a>' : null])
<ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link {{ $sent ? '' : 'active' }}" href="{{ route('messages.index') }}">Recibidos</a></li>
    <li class="nav-item"><a class="nav-link {{ $sent ? 'active' : '' }}" href="{{ route('messages.index', ['carpeta' => 'enviados']) }}">Enviados</a></li>
</ul>
<div class="card"><ul class="list-group list-group-flush">
    @forelse($messages as $m)
    <a href="{{ route('messages.show', $m) }}" class="list-group-item list-group-item-action {{ ! $sent && ! $m->read_at ? 'fw-bold' : '' }}">
        <div class="d-flex justify-content-between"><span>{{ $sent ? 'Para '.$m->recipient?->name : $m->sender?->name }}</span><small class="text-muted">{{ $m->created_at->diffForHumans() }}</small></div>
        <div class="small text-muted text-truncate">{{ $m->subject ?: \Illuminate\Support\Str::limit($m->body, 90) }}</div>
    </a>
    @empty
    <li class="list-group-item text-center text-muted py-5">No hay mensajes.</li>
    @endforelse
</ul></div>
<div class="mt-3">{{ $messages->links() }}</div>
@endsection
