@extends('layouts.app')

@section('title', 'Consignaciones')

@php $money = fn ($c) => \App\Services\Finance\ActivityFinanceService::money((int) $c); $qty = fn ($q) => \App\Services\Market\MarketStockService::qty((float) $q); @endphp

@section('content')
@include('community._head', ['icon' => 'bi-box-arrow-in-down', 'title' => 'Consignaciones',
    'subtitle' => 'Mercadería ajena: entregada, vendida, devuelta, pendiente y lo que se le debe a cada emprendedor.',
    'actions' => $canCreate ? '<a href="'.route('market.consignments.create').'" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Recibir mercadería</a>' : null])

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="btn-group">
        <a href="{{ route('market.consignments.index') }}" class="btn btn-{{ $status === 'CERRADA' ? 'outline-light' : 'primary' }}">Abiertas</a>
        <a href="{{ route('market.consignments.index', ['estado' => 'CERRADA']) }}" class="btn btn-{{ $status === 'CERRADA' ? 'primary' : 'outline-light' }}">Cerradas</a>
    </div>
    @if($status !== 'CERRADA')<span class="text-white">Pendiente de liquidar: <strong>{{ $money($totalOwed) }}</strong></span>@endif
</div>
<div class="card"><div class="table-responsive"><table class="table mb-0 align-middle">
    <thead><tr><th>#</th><th>Emprendedor</th><th>Recibida</th><th class="text-end">Entregado</th><th class="text-end">Vendido</th><th class="text-end">Devuelto</th><th class="text-end">En local</th><th class="text-end">A liquidar</th></tr></thead>
    <tbody>
    @forelse($rows as $r)
    <tr>
        <td><a href="{{ route('market.consignments.show', $r['c']) }}">#{{ $r['c']->id }}</a></td>
        <td>{{ $r['c']->entrepreneur?->business_name }}</td>
        <td>{{ $r['c']->received_on->format('d/m/Y') }}</td>
        <td class="text-end">{{ $qty($r['b']['delivered']) }}</td><td class="text-end">{{ $qty($r['b']['sold']) }}</td>
        <td class="text-end">{{ $qty($r['b']['returned']) }}</td><td class="text-end">{{ $qty($r['b']['pending_qty']) }}</td>
        <td class="text-end fw-bold">{{ $money($r['b']['owed_cents']) }}</td>
    </tr>
    @empty
    <tr><td colspan="8" class="text-center text-muted py-5">No hay consignaciones {{ $status === 'CERRADA' ? 'cerradas' : 'abiertas' }}.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
@endsection
