@extends('layouts.app')

@section('title', 'Emprendedores')

@section('content')
@include('community._head', ['icon' => 'bi-shop-window', 'title' => 'Emprendedores', 'subtitle' => 'Perfiles comerciales, grupos, productos, consignaciones y manuales de marca.',
    'actions' => $canCreate ? '<a href="'.route('market.entrepreneurs.create').'" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nuevo emprendedor</a>' : null])

@if($groups->isNotEmpty())
<div class="mb-3 d-flex gap-2 flex-wrap">
    <a href="{{ route('market.entrepreneurs.index') }}" class="btn btn-sm btn-{{ request('group_id') ? 'outline-light' : 'primary' }}">Todos</a>
    @foreach($groups as $g)<a href="{{ route('market.entrepreneurs.index', ['group_id' => $g->id]) }}" class="btn btn-sm btn-{{ (int) request('group_id') === $g->id ? 'primary' : 'outline-light' }}">{{ $g->name }} ({{ $g->entrepreneurs_count }})</a>@endforeach
</div>
@endif

<div class="row g-3">
@forelse($entrepreneurs as $e)
    <div class="col-md-6 col-xl-4">
        <a href="{{ route('market.entrepreneurs.show', $e) }}" class="card h-100 text-decoration-none"><div class="card-body">
            <div class="d-flex justify-content-between"><h5 class="mb-1">{{ $e->business_name }}</h5>@unless($e->is_active)<span class="badge bg-secondary">Inactivo</span>@endunless</div>
            <div class="small text-muted mb-2">{{ $e->products_count }} productos @if($e->payment_alias)· alias {{ $e->payment_alias }}@endif</div>
            <div class="d-flex gap-1">@foreach(($e->brand['colors'] ?? []) as $c)<span style="width:18px;height:18px;border-radius:4px;background:{{ $c }};display:inline-block;border:1px solid #0002"></span>@endforeach</div>
            <div class="small mt-2">@foreach($e->groups as $g)<span class="badge bg-info text-dark">{{ $g->name }}</span> @endforeach</div>
        </div></a>
    </div>
@empty
    <div class="col-12"><div class="card"><div class="card-body text-center text-muted py-5">Todavía no hay emprendedores.</div></div></div>
@endforelse
</div>

@if($canCreate)
<div class="card mt-4" style="max-width: 640px"><div class="card-body">
    <h6>Nuevo grupo de emprendedores</h6>
    <form method="POST" action="{{ route('market.entrepreneurs.groups.store') }}" class="row g-2">@csrf
        <div class="col-md-5"><input name="name" class="form-control" placeholder="Ej. Feria del sábado" required></div>
        <div class="col-md-5"><input name="description" class="form-control" placeholder="Descripción (opcional)"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Crear</button></div>
    </form>
</div></div>
@endif
@endsection
