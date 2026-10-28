@extends('layouts.app')

@section('title', 'Productos y stock')

@php $money = fn ($c) => \App\Services\Finance\ActivityFinanceService::money((int) $c); @endphp

@section('content')
@include('community._head', ['icon' => 'bi-basket', 'title' => 'Productos y stock', 'subtitle' => 'Mercadería de emprendedores y de la organización. El stock sólo cambia con movimientos registrados.'])

<div class="card mb-3"><div class="card-body py-3">
    <form class="row g-2 align-items-end">
        <div class="col-md-4"><input name="q" class="form-control" placeholder="Buscar producto" value="{{ request('q') }}"></div>
        <div class="col-md-4"><select name="entrepreneur_id" class="form-select"><option value="">Todos los emprendedores</option>@foreach($entrepreneurs as $e)<option value="{{ $e->id }}" @selected((int) request('entrepreneur_id') === $e->id)>{{ $e->business_name }}</option>@endforeach</select></div>
        <div class="col-md-2 form-check ms-2"><input type="checkbox" class="form-check-input" name="bajo" value="1" id="bajo" @checked(request('bajo'))><label for="bajo" class="form-check-label">Stock bajo</label></div>
        <div class="col-md-1"><button class="btn btn-primary w-100"><i class="bi bi-funnel"></i></button></div>
    </form>
</div></div>

<div class="card mb-4"><div class="table-responsive"><table class="table mb-0 align-middle">
    <thead><tr><th>Producto</th><th>Emprendedor</th><th>Precio</th><th>Stock</th></tr></thead>
    <tbody>
    @forelse($products as $p)
    <tr class="{{ $p->is_active ? '' : 'text-muted' }}">
        <td><a href="{{ route('market.products.show', $p) }}">{{ $p->name }}</a> <span class="small text-muted">{{ $p->category }}</span></td>
        <td>{{ $p->entrepreneur?->business_name ?? 'Propio' }}</td>
        <td>{{ $money($p->price_cents) }} / {{ $p->unit }}</td>
        <td class="{{ $p->isLow() ? 'text-danger fw-bold' : '' }}">{{ \App\Services\Market\MarketStockService::qty($p->stock_quantity) }} {{ $p->unit }}</td>
    </tr>
    @empty
    <tr><td colspan="4" class="text-center text-muted py-5">No hay productos.</td></tr>
    @endforelse
    </tbody>
</table></div><div class="card-footer">{{ $products->links() }}</div></div>

@if($canCreate)
<div class="card" style="max-width: 900px"><div class="card-header"><h5 class="mb-0">Nuevo producto</h5></div><div class="card-body">
    <form method="POST" action="{{ route('market.products.store') }}" class="row g-2">
        @csrf
        <div class="col-md-4"><input name="name" class="form-control" placeholder="Nombre" required></div>
        <div class="col-md-3"><select name="entrepreneur_id" class="form-select"><option value="">De la organización</option>@foreach($entrepreneurs as $e)<option value="{{ $e->id }}">{{ $e->business_name }}</option>@endforeach</select></div>
        <div class="col-md-2"><input name="category" class="form-control" placeholder="Categoría"></div>
        <div class="col-md-3"><select name="unit" class="form-select"><option>unidad</option><option>kg</option><option>g</option><option>litro</option><option>docena</option><option>bolsón</option><option>frasco</option></select></div>
        <div class="col-md-3"><input name="price" class="form-control" placeholder="Precio $" required></div>
        <div class="col-md-3"><input name="min_stock" type="number" step="0.001" min="0" class="form-control" placeholder="Stock mínimo (alerta)"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Crear</button></div>
    </form>
</div></div>
@endif
@endsection
