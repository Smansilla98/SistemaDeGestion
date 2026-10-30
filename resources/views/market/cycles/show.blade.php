@extends('layouts.app')

@section('title', $cycle->name)

@php $money = fn ($c) => \App\Services\Finance\ActivityFinanceService::money((int) $c); $qty = fn ($q) => \App\Services\Market\MarketStockService::qty((float) $q); @endphp

@section('content')
@include('community._head', ['icon' => 'bi-arrow-repeat', 'title' => $cycle->name, 'back' => route('market.cycles.index'),
    'subtitle' => 'Pedidos del '.$cycle->opens_at->format('d/m H:i').' al '.$cycle->closes_at->format('d/m H:i').' · entrega '.$cycle->delivery_on->format('d/m/Y')])

@if($canManage)
<form method="POST" action="{{ route('market.cycles.status', $cycle) }}" class="mb-3 d-flex gap-2 align-items-center">@csrf
    <span class="text-white">Estado:</span>
    @foreach(\App\Models\OrderCycle::STATUSES as $k => $l)
    <button name="status" value="{{ $k }}" class="btn btn-sm btn-{{ $cycle->status === $k ? 'primary' : 'outline-light' }}">{{ $l }}</button>
    @endforeach
</form>
@endif

<div class="row g-4">
    <div class="col-xl-7">
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Pedidos</h5></div>
            <ul class="list-group list-group-flush">
            @forelse($orders as $o)
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div><strong>{{ $o->contact?->name }}</strong> <span class="badge bg-{{ $o->status === 'ENTREGADO' ? 'success' : ($o->status === 'CANCELADO' ? 'secondary' : 'warning') }}">{{ \App\Models\CycleOrder::LABELS[$o->status] }}</span>
                            <div class="small text-muted">@foreach($o->items as $it){{ $qty($it->quantity) }} {{ $it->product?->unit }} {{ $it->product?->name }}{{ $loop->last ? '' : ' · ' }}@endforeach</div></div>
                        <div class="d-flex gap-1 align-items-center">
                            <strong>{{ $money($o->total_cents) }}</strong>
                            @if($canManage && $o->status === 'PENDIENTE')
                            <form method="POST" action="{{ route('market.cycles.orders.deliver', [$cycle, $o]) }}" class="d-flex gap-1">@csrf
                                <input name="paid" class="form-control form-control-sm" style="width: 100px" placeholder="Cobrado">
                                <button class="btn btn-sm btn-success" title="Entregar"><i class="bi bi-check2"></i></button></form>
                            <form method="POST" action="{{ route('market.cycles.orders.cancel', [$cycle, $o]) }}" onsubmit="return confirm('¿Cancelar el pedido?')">@csrf
                                <button class="btn btn-sm btn-outline-danger" title="Cancelar"><i class="bi bi-x"></i></button></form>
                            @endif
                        </div>
                    </div>
                </li>
            @empty
                <li class="list-group-item text-muted">Todavía no hay pedidos.</li>
            @endforelse
            </ul>
        </div>

        @if($canManage && $cycle->status !== 'ENTREGADO' && $offer->isEmpty())
        <div class="alert alert-warning"><i class="bi bi-basket"></i> Este ciclo todavía no tiene productos. Cargá la oferta (abajo a la derecha) para empezar a tomar pedidos.</div>
        @elseif($canManage && $cycle->status !== 'ENTREGADO')
        <div class="card"><div class="card-header"><h6 class="mb-0">Cargar o modificar un pedido</h6></div><div class="card-body">
            <form method="POST" action="{{ route('market.cycles.orders.store', $cycle) }}">@csrf
                <div class="row g-2 mb-2">
                    <div class="col-md-5"><select name="contact_id" class="form-select"><option value="">— Persona nueva —</option>@foreach($contacts as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><input name="new_name" class="form-control" placeholder="o nombre nuevo"></div>
                    <div class="col-md-3"><input name="new_phone" class="form-control" placeholder="Teléfono"></div>
                </div>
                <div class="table-responsive" style="max-height: 340px"><table class="table table-sm mb-2">
                    @foreach($offer as $o)
                    @php $left = $o->max_quantity === null ? null : max(0, $o->max_quantity - ($taken[$o->market_product_id] ?? 0)); @endphp
                    <tr><td>{{ $o->product->name }} <span class="small text-muted">{{ $o->product->entrepreneur?->business_name }}</span>
                            @if($left !== null)<div class="small {{ $left <= 0 ? 'text-danger' : 'text-muted' }}">Quedan {{ $qty($left) }} {{ $o->product->unit }}</div>@endif</td>
                        <td class="text-nowrap">{{ $money($o->price_cents) }} / {{ $o->product->unit }}</td>
                        <td style="width: 110px"><input type="number" step="0.001" min="0" name="qty[{{ $o->market_product_id }}]" class="form-control form-control-sm" placeholder="0"></td></tr>
                    @endforeach
                </table></div>
                <button class="btn btn-primary">Guardar pedido</button>
                <span class="small text-muted">Si la persona ya tenía un pedido pendiente en este ciclo, se reemplaza.</span>
            </form>
        </div></div>
        @endif
    </div>
    <div class="col-xl-5"><div class="card">
        <div class="card-header"><h5 class="mb-0">Qué pedir a cada productor</h5></div>
        <div class="table-responsive"><table class="table mb-0 small align-middle">
            <thead><tr><th>Producto</th><th class="text-end">Pedido</th><th class="text-end">En stock</th><th class="text-end">Total</th></tr></thead>
            <tbody>
            @forelse($consolidated as $r)
            <tr><td>{{ $r['product']->name }} <div class="text-muted">{{ $r['product']->entrepreneur?->business_name ?? 'Propio' }}</div></td>
                <td class="text-end">{{ $qty($r['quantity']) }} {{ $r['product']->unit }}</td>
                <td class="text-end {{ $r['stock'] < $r['quantity'] ? 'text-danger' : 'text-success' }}">{{ $qty($r['stock']) }}</td>
                <td class="text-end">{{ $money($r['total_cents']) }}</td></tr>
            @empty
            <tr><td colspan="4" class="text-muted text-center py-4">Sin pedidos.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="card-footer small text-muted">Cuando llega la mercadería, cargala como ingreso o consignación: al entregar cada pedido se descuenta del stock.</div>
    </div>

    <div class="card mt-4" id="oferta">
        <div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0">Productos del ciclo</h5><span class="badge bg-secondary">{{ $offer->count() }}</span></div>
        @if($canManage && $cycle->status !== 'ENTREGADO')
        @php $inOffer = $offer->keyBy('market_product_id'); @endphp
        <form method="POST" action="{{ route('market.cycles.offer', $cycle) }}">@csrf
            <div class="table-responsive" style="max-height: 460px"><table class="table table-sm mb-0 small align-middle">
                <thead><tr><th></th><th>Producto</th><th style="width: 110px">Precio</th><th style="width: 90px">Cupo</th></tr></thead>
                <tbody>
                @forelse($products as $p)
                @php $o = $inOffer->get($p->id); @endphp
                <tr>
                    <td><input type="checkbox" class="form-check-input" name="offer[{{ $p->id }}]" value="1" @checked($o)></td>
                    <td>{{ $p->name }} <div class="text-muted">{{ $p->entrepreneur?->business_name ?? 'Propio' }} · {{ $p->unit }}</div></td>
                    <td><input name="price[{{ $p->id }}]" class="form-control form-control-sm" value="{{ number_format(($o?->price_cents ?? $p->price_cents) / 100, 2, ',', '.') }}"></td>
                    <td><input type="number" step="0.001" min="0" name="max[{{ $p->id }}]" class="form-control form-control-sm" value="{{ $o?->max_quantity }}" placeholder="—"></td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-muted text-center py-4">Primero cargá productos en <a href="{{ route('market.products.index') }}">Productos</a>.</td></tr>
                @endforelse
                </tbody>
            </table></div>
            <div class="card-footer d-flex justify-content-between align-items-center gap-2">
                <span class="small text-muted">Precio para este ciclo. Cupo: total que se puede pedir (vacío = sin tope).</span>
                <button class="btn btn-primary btn-sm">Guardar oferta</button>
            </div>
        </form>
        @else
        <ul class="list-group list-group-flush small">
            @forelse($offer as $o)<li class="list-group-item d-flex justify-content-between"><span>{{ $o->product->name }}</span><span>{{ $money($o->price_cents) }} / {{ $o->product->unit }}</span></li>
            @empty<li class="list-group-item text-muted">Sin productos cargados.</li>@endforelse
        </ul>
        @endif
    </div></div>
</div>
@endsection
