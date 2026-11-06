@extends('layouts.app')

@section('title', 'Consignación #'.$consignment->id)

@php $money = fn ($c) => \App\Services\Finance\ActivityFinanceService::money((int) $c); $qty = fn ($q) => \App\Services\Market\MarketStockService::qty((float) $q); @endphp

@section('content')
@include('community._head', ['icon' => 'bi-box-arrow-in-down', 'title' => 'Consignación #'.$consignment->id.' — '.$consignment->entrepreneur?->business_name,
    'back' => route('market.consignments.index'),
    'subtitle' => 'Recibida el '.$consignment->received_on->format('d/m/Y').' · comisión '.rtrim(rtrim(number_format($consignment->commission_percent, 2, ',', ''), '0'), ',').'% · '.$consignment->status])

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Vendido (lo que cobraste)</div><h4 class="mb-0">{{ $money($balance['gross_cents']) }}</h4></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Queda para el espacio</div><h4 class="mb-0">{{ $money($balance['commission_cents']) }}</h4></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Ya liquidado</div><h4 class="mb-0">{{ $money($balance['paid_cents']) }}</h4></div></div></div>
    <div class="col-md-3"><div class="card h-100 border-warning"><div class="card-body"><div class="small text-muted">Se le debe</div><h4 class="mb-0">{{ $money($balance['owed_cents']) }}</h4></div></div></div>
</div>

<div class="card mb-4">
<div class="card-header"><h5 class="mb-0">Planilla: su stock, su precio y el mío</h5><div class="small text-muted">Tocá «Vendí» cada vez que se vende algo; si te equivocaste, «Deshacer» resta una.</div></div>
<div class="table-responsive"><table class="table mb-0 align-middle">
    <thead><tr><th>Producto</th><th class="text-end">Su precio</th><th class="text-end">Mi precio</th><th class="text-end">Gano x u.</th><th class="text-end">Entregado</th><th class="text-end">Vendido</th><th class="text-end">Devuelto</th><th class="text-end">Quedan</th>@if($canMove)<th>Vender / devolver</th>@endif</tr></thead>
    <tbody>
    @foreach($consignment->items as $it)
    @php $sale = $it->salePriceCents(); $mine = $sale - (int) round($it->unit_price_cents * (1 - (float) $consignment->commission_percent / 100)); @endphp
    <tr class="{{ $it->pending() <= 0 ? 'text-muted' : '' }}">
        <td><a href="{{ route('market.products.show', $it->market_product_id) }}">{{ $it->product?->name }}</a>
            @if($it->qty_sold > 0)<div class="small">@for($n = 0; $n < min(20, (int) floor($it->qty_sold)); $n++)<span class="text-decoration-line-through text-muted me-1" aria-hidden="true">▮</span>@endfor<span class="visually-hidden">{{ $qty($it->qty_sold) }} vendidas</span></div>@endif</td>
        <td class="text-end">{{ $money($it->unit_price_cents) }}</td>
        <td class="text-end fw-semibold">{{ $money($sale) }}</td>
        <td class="text-end {{ $mine > 0 ? 'text-success' : '' }}">{{ $money($mine) }}</td>
        <td class="text-end">{{ $qty($it->qty_delivered) }}</td><td class="text-end">{{ $qty($it->qty_sold) }}</td>
        <td class="text-end">{{ $qty($it->qty_returned) }}</td><td class="text-end fw-bold">{{ $qty($it->pending()) }}</td>
        @if($canMove)<td class="text-nowrap">
            @if($consignment->status === 'ABIERTA')
            <div class="d-flex flex-wrap gap-1">
                @if($it->pending() > 0)
                <form method="POST" action="{{ route('market.consignments.sell', [$consignment, $it]) }}">@csrf<input type="hidden" name="quantity" value="1">
                    <button class="btn btn-sm btn-success" aria-label="Vendí una unidad de {{ $it->product?->name }}"><i class="bi bi-check2"></i> Vendí 1</button></form>
                @endif
                @if($it->qty_sold > 0)
                <form method="POST" action="{{ route('market.consignments.sell', [$consignment, $it]) }}">@csrf<input type="hidden" name="quantity" value="-1">
                    <button class="btn btn-sm btn-outline-secondary" aria-label="Deshacer una venta de {{ $it->product?->name }}">Deshacer</button></form>
                @endif
                @if($it->pending() > 0)
                <form method="POST" action="{{ route('market.consignments.return', [$consignment, $it]) }}" class="d-flex gap-1">@csrf
                    <input type="number" step="0.001" min="0" max="{{ $it->pending() }}" name="quantity" class="form-control form-control-sm" style="width: 80px" placeholder="cant." aria-label="Cantidad a devolver de {{ $it->product?->name }}" required>
                    <button class="btn btn-sm btn-outline-secondary">Devolver</button></form>
                @endif
            </div>
            @endif
        </td>@endif
    </tr>
    @endforeach
    </tbody>
</table></div>
<div class="card-footer small text-muted">«Su precio» es lo que se le paga al emprendedor por unidad vendida{{ (float) $consignment->commission_percent > 0 ? ' (menos la comisión del '.rtrim(rtrim(number_format($consignment->commission_percent, 2, ',', ''), '0'), ',').'%)' : '' }}; «Mi precio», al que vende el espacio. También se descuenta al vender desde el producto o al entregar pedidos de un ciclo.</div></div>

<div class="row g-4">
    @if($canSettle && $balance['owed_cents'] > 0)
    <div class="col-lg-5"><div class="card"><div class="card-header"><h6 class="mb-0">Liquidar al emprendedor</h6></div><div class="card-body">
        <p>Se registra el pago de <strong>{{ $money($balance['owed_cents']) }}</strong> (lo que le corresponde por lo vendido menos lo ya pagado).
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
                <span>vendido {{ $money($s->gross_cents) }} − para el espacio {{ $money($s->commission_cents) }} = <strong>{{ $money($s->paid_cents) }}</strong></span></li>
        @empty
            <li class="list-group-item text-muted">Todavía no se liquidó nada.</li>
        @endforelse
        </ul>
    </div></div>
</div>
@endsection
