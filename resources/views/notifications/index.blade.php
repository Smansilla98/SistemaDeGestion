@extends('layouts.app')

@section('title', 'Notificaciones')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-bell"></i> Notificaciones</h1>
        @if($notifications->where('read_at', null)->count() > 0)
        <form action="{{ route('notifications.read-all') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-outline-light btn-sm">Marcar todas como leídas</button>
        </form>
        @endif
    </div>
</div>

<div class="card">
    <div class="list-group list-group-flush">
        @forelse($notifications as $n)
        <a href="{{ route('notifications.read', $n->id) }}" class="list-group-item list-group-item-action {{ $n->read_at ? '' : 'list-group-item-light' }}">
            <div class="d-flex w-100 justify-content-between">
                <p class="mb-1">{{ $n->data['message'] ?? 'Notificación' }}</p>
                <small>{{ $n->created_at->diffForHumans() }}</small>
            </div>
            @if($n->read_at)
            <small class="text-muted">Leída {{ \Carbon\Carbon::parse($n->read_at)->diffForHumans() }}</small>
            @else
            <small class="text-primary">Nueva</small>
            @endif
        </a>
        @empty
        <div class="list-group-item text-center text-muted py-5">No tenés notificaciones.</div>
        @endforelse
    </div>
    @if($notifications->hasPages())
    <div class="card-footer">
        {{ $notifications->links() }}
    </div>
    @endif
</div>
@endsection
