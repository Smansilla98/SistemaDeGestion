@extends('layouts.app')

@section('title', $product->name)

@php $money = fn ($c) => \App\Services\Finance\ActivityFinanceService::money((int) $c); $qty = fn ($q) => \App\Services\Market\MarketStockService::qty((float) $q); @endphp

@section('content')
@include('community._head', ['icon' => 'bi-basket', 'title' => $product->name, 'back' => route('market.products.index'),
    'subtitle' => ($product->entrepreneur?->business_name ?? 'Producto propio').' · '.$money($product->price_cents).' / '.$product->unit])

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card mb-4"><div class="card-body text-center">
            <div class="small text-muted">Stock actual</div>
            <div class="display-6 {{ $product->isLow() ? 'text-danger' : '' }}">{{ $qty($product->stock_quantity) }} <small class="fs-6">{{ $product->unit }}</small></div>
            @if($product->min_stock !== null)<div class="small text-muted">Mínimo: {{ $qty($product->min_stock) }}</div>@endif
        </div></div>
        @if($canMove)
        <div class="card mb-4"><div class="card-header"><h6 class="mb-0">Registrar movimiento</h6></div><div class="card-body">
            <form method="POST" action="{{ route('market.products.move', $product) }}" class="row g-2">
                @csrf
                <div class="col-12"><select name="type" class="form-select">
                    <option value="INGRESO">Ingreso (compra / producción propia)</option>
                    <option value="VENTA">Venta</option>
                    <option value="AJUSTE_MAS">Ajuste + (conteo)</option>
                    <option value="AJUSTE_MENOS">Ajuste − (rotura, vencimiento)</option>
                </select></div>
                <div class="col-6"><input type="number" step="0.001" min="0" name="quantity" class="form-control" placeholder="Cantidad" required></div>
                <div class="col-6"><input name="note" class="form-control" placeholder="Nota"></div>
                <div class="col-12"><button class="btn btn-primary w-100">Registrar</button></div>
            </form>
            <div class="small text-muted mt-2">Para mercadería en consignación usá «Consignaciones»: así queda claro de quién es y cuánto se le debe.</div>
        </div></div>
        <div class="card"><div class="card-header"><h6 class="mb-0">Datos</h6></div><div class="card-body">
            <form method="POST" action="{{ route('market.products.update', $product) }}" class="row g-2">
                @csrf @method('PUT')
                <div class="col-12"><input name="name" class="form-control" value="{{ $product->name }}" required></div>
                <div class="col-6"><input name="category" class="form-control" value="{{ $product->category }}" placeholder="Categoría"></div>
                <div class="col-6"><input name="unit" class="form-control" value="{{ $product->unit }}" required></div>
                <div class="col-6"><input name="price" class="form-control" value="{{ number_format($product->price_cents / 100, 2, ',', '.') }}" required></div>
                <div class="col-6"><input name="min_stock" type="number" step="0.001" min="0" class="form-control" value="{{ $product->min_stock }}" placeholder="Mínimo"></div>
                <div class="col-12 form-check ms-1"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="ia" @checked($product->is_active)><label for="ia" class="form-check-label">Activo</label></div>
                <div class="col-12"><button class="btn btn-outline-primary w-100">Guardar</button></div>
            </form>
        </div></div>
        @endif
    </div>
    <div class="col-lg-8"><div class="card">
        <div class="card-header"><h5 class="mb-0">Movimientos</h5></div>
        <div class="table-responsive"><table class="table mb-0 align-middle small">
            <thead><tr><th>Fecha</th><th>Tipo</th><th class="text-end">Cantidad</th><th class="text-end">Saldo</th><th>Detalle</th></tr></thead>
            <tbody>
            @forelse($movements as $m)
            <tr><td>{{ $m->created_at->format('d/m/Y H:i') }}</td><td>{{ \App\Models\MarketStockMovement::TYPES[$m->type] ?? $m->type }}</td>
                <td class="text-end {{ $m->quantity < 0 ? 'text-danger' : 'text-success' }}">{{ $m->quantity > 0 ? '+' : '' }}{{ $qty($m->quantity) }}</td>
                <td class="text-end">{{ $qty($m->balance_after) }}</td>
                <td>{{ $m->note }} <span class="text-muted">{{ $m->user?->name }}</span></td></tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Sin movimientos.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="card-footer">{{ $movements->links() }}</div>
    </div></div>
</div>
@endsection
