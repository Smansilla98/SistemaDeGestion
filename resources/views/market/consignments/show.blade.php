@extends('layouts.app')

@section('title', 'Consignación #'.$consignment->id)

@php $money = fn ($c) => \App\Services\Finance\ActivityFinanceService::money((int) $c); $qty = fn ($q) => \App\Services\Market\MarketStockService::qty((float) $q); @endphp

@section('content')
@include('community._head', ['icon' => 'bi-box-arrow-in-down', 'title' => 'Consignación #'.$consignment->id.' — '.$consignment->entrepreneur?->business_name,
    'back' => route('market.consignments.index'),
    'subtitle' => 'Recibida el '.$consignment->received_on->format('d/m/Y').' · comisión '.rtrim(rtrim(number_format($consignment->commission_percent, 2, ',', ''), '0'), ',').'% · '.$consignment->status])

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Vendido (bruto)</div><h4 class="mb-0">{{ $money($balance['gross_cents']) }}</h4></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Comisión del espacio</div><h4 class="mb-0">{{ $money($balance['commission_cents']) }}</h4></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Ya liquidado</div><h4 class="mb-0">{{ $money($balance['paid_cents']) }}</h4></div></div></div>
    <div class="col-md-3"><div class="card h-100 border-warning"><div class="card-body"><div class="small text-muted">Se le debe</div><h4 class="mb-0">{{ $money($balance['owed_cents']) }}</h4></div></div></div>
</div>

<div class="card mb-4"><div class="table-responsive"><table class="table mb-0 align-middle">
    <thead><tr><th>Producto</th><th class="text-end">Precio</th><th class="text-end">Entregado</th><th class="text-end">Vendido</th><th class="text-end">Devuelto</th><th class="text-end">En local</th>@if($canMove)<th>Devolver</th>@endif</tr></thead>
    <tbody>
    @foreach($consignment->items as $it)
    <tr>
        <td><a href="{{ route('market.products.show', $it->market_product_id) }}">{{ $it->product?->name }}</a></td>
        <td class="text-end">{{ $money($it->unit_price_cents) }}</td>
        <td class="text-end">{{ $qty($it->qty_delivered) }}</td><td class="text-end">{{ $qty($it->qty_sold) }}</td>
        <td class="text-end">{{ $qty($it->qty_returned) }}</td><td class="text-end fw-bold">{{ $qty($it->pending()) }}</td>
        @if($canMove)<td>@if($it->pending() > 0)
            <form method="POST" action="{{ route('market.consignments.return', [$consignment, $it]) }}" class="d-flex gap-1">@csrf
                <input type="number" step="0.001" min="0" max="{{ $it->pending() }}" name="quantity" class="form-control form-control-sm" style="width: 90px" required>
                <button class="btn btn-sm btn-outline-secondary">Devolver</button></form>@endif</td>@endif
    </tr>
    @endforeach
    </tbody>
</table></div>
<div class="card-footer small text-muted">Las ventas se registran desde el producto («Venta») o al entregar pedidos de un ciclo: se descuentan primero de la consignación más vieja.</div></div>

<div class="row g-4">
    @if($canSettle && $balance['owed_cents'] > 0)
    <div class="col-lg-5"><div class="card"><div class="card-header"><h6 class="mb-0">Liquidar al emprendedor</h6></div><div class="card-body">
        <p>Se registra el pago de <strong>{{ $money($balance['owed_cents']) }}</strong> (vendido menos comisión menos lo ya pagado).
            @if($consignment->entrepreneur?->payment_alias)<br>Alias: <strong>{{ $consignment->entrepreneur->payment_alias }}</strong>@endif</p>
        <form method="POST" action="{{ route('market.consignments.settle', $consignment) }}" class="row g-2" onsubmit="return confirm('¿Registrar la liquidación?')">@csrf
            <div class="col-6"><input type="date" name="paid_on" class="form-control" value="{{ today()->toDateString() }}" required></div>
            <div class="col-6"><input name="method" class="form-control" placeholder="Medio (transferencia…)"></div>
            <div class="col-12"><input name="note" class="form-control" placeholder="Nota"></div>
            <div class="col-12"><button class="btn btn-primary w-100">Registrar liquidación</button></div>
        </form>
    </div></div></div>
    @endif
    <div class="col-lg-7"><div class="card"><div class="card-header"><h6 class="mb-0">Liquidaciones</h6></div>
        <ul class="list-group list-group-flush">
        @forelse($consignment->settlements as $s)
            <li class="list-group-item d-flex justify-content-between small"><span>{{ $s->paid_on->format('d/m/Y') }} · {{ $s->method }} {{ $s->note }}</span>
                <span>bruto {{ $money($s->gross_cents) }} − comisión {{ $money($s->commission_cents) }} = <strong>{{ $money($s->paid_cents) }}</strong></span></li>
        @empty
            <li class="list-group-item text-muted">Todavía no se liquidó nada.</li>
        @endforelse
        </ul>
    </div></div>
</div>
@endsection
