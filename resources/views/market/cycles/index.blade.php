@extends('layouts.app')

@section('title', 'Ciclos de pedidos')

@section('content')
@include('community._head', ['icon' => 'bi-arrow-repeat', 'title' => 'Ciclos de pedidos', 'subtitle' => 'Nodo o mercado: se abren pedidos, se cierran, se consolida qué pedir a cada productor y se entregan.'])
<div class="row g-4">
    <div class="{{ $canCreate ? 'col-lg-8' : 'col-12' }}"><div class="card"><ul class="list-group list-group-flush">
        @forelse($cycles as $c)
        <a href="{{ route('market.cycles.show', $c) }}" class="list-group-item list-group-item-action d-flex justify-content-between">
            <span><strong>{{ $c->name }}</strong> <span class="small text-muted">cierra {{ $c->closes_at->format('d/m H:i') }} · entrega {{ $c->delivery_on->format('d/m/Y') }}</span></span>
            <span><span class="badge bg-{{ $c->status === 'ABIERTO' ? 'success' : ($c->status === 'CERRADO' ? 'warning' : 'secondary') }}">{{ \App\Models\OrderCycle::STATUSES[$c->status] }}</span> <span class="small">{{ $c->orders_count }} pedidos</span></span>
        </a>
        @empty
        <li class="list-group-item text-center text-muted py-5">Todavía no hay ciclos.</li>
        @endforelse
    </ul></div><div class="mt-3">{{ $cycles->links() }}</div></div>
    @if($canCreate)
    <div class="col-lg-4"><div class="card"><div class="card-header"><h6 class="mb-0">Nuevo ciclo</h6></div><div class="card-body">
        <form method="POST" action="{{ route('market.cycles.store') }}" class="row g-2">@csrf
            <div class="col-12"><input name="name" class="form-control" placeholder="Ej. Bolsones 2ª quincena de noviembre" required></div>
            <div class="col-12"><label class="form-label small">Abre</label><input type="datetime-local" name="opens_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required></div>
            <div class="col-12"><label class="form-label small">Cierra</label><input type="datetime-local" name="closes_at" class="form-control" required></div>
            <div class="col-12"><label class="form-label small">Entrega</label><input type="date" name="delivery_on" class="form-control" required></div>
            @if($previous->isNotEmpty())
            <div class="col-12"><label class="form-label small">Productos</label><select name="copy_from" class="form-select">
                <option value="">Cargarlos después</option>
                @foreach($previous as $p)<option value="{{ $p->id }}" @selected($loop->first)>Copiar de «{{ $p->name }}» ({{ $p->delivery_on->format('d/m') }})</option>@endforeach
            </select></div>
            @endif
            <div class="col-12"><textarea name="notes" class="form-control" rows="2" placeholder="Notas (punto de retiro, horario…)"></textarea></div>
            <div class="col-12"><button class="btn btn-primary w-100">Crear</button></div>
        </form>
    </div></div></div>
    @endif
</div>
@endsection
