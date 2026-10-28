@extends('layouts.app')

@section('title', 'Comunicaciones')

@section('content')
@include('community._head', ['icon' => 'bi-megaphone', 'title' => 'Comunicaciones', 'subtitle' => 'Difusión de talleres, eventos y novedades a contactos que aceptaron recibirlas.',
    'actions' => '<a href="'.route('campaigns.create').'" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nueva comunicación</a>'])
<div class="card"><ul class="list-group list-group-flush">
    @forelse($campaigns as $c)
    <a href="{{ route('campaigns.show', $c) }}" class="list-group-item list-group-item-action d-flex justify-content-between">
        <span>{{ $c->title }}</span>
        <span class="small">@if($c->status === 'ENVIADA')<span class="badge bg-success">Enviada a {{ $c->recipients_count }}</span> {{ $c->sent_at?->format('d/m/Y H:i') }}@else<span class="badge bg-secondary">Borrador</span>@endif</span>
    </a>
    @empty
    <li class="list-group-item text-center text-muted py-5">Todavía no hay comunicaciones.</li>
    @endforelse
</ul></div>
<div class="mt-3">{{ $campaigns->links() }}</div>
@endsection
