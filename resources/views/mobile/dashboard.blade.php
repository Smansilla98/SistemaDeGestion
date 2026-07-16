@extends('layouts.mobile')

@section('title', 'Inicio')

@section('content')
@php
    $canKitchen = auth()->check() && app(\App\Services\PermissionService::class)->allowed(auth()->user(), 'kitchen.view');
@endphp

<div class="m-stats">
    <a href="{{ route('m.pedidos.index') }}" class="m-stat">
        <div class="m-stat-top">
            <i class="bi bi-grid-3x3-gap m-stat-ico" aria-hidden="true"></i>
            <span class="m-pill m-pill--ok">{{ $stats['mesas_libres'] }} libres</span>
        </div>
        <div class="m-stat-value">{{ $stats['mesas_libres'] }}<span> / {{ $stats['total_tables'] }}</span></div>
        <div class="m-stat-label">Mesas libres</div>
    </a>

    <a href="{{ route('m.pedidos.index') }}" class="m-stat">
        <div class="m-stat-top">
            <i class="bi bi-receipt m-stat-ico" aria-hidden="true"></i>
            @if($stats['pedidos_pendientes'] > 0)
                <span class="m-pill m-pill--warn">en curso</span>
            @endif
        </div>
        <div class="m-stat-value">{{ $stats['pedidos_pendientes'] }}</div>
        <div class="m-stat-label">Pedidos pendientes</div>
    </a>

    <a href="{{ Route::has('m.caja.resumen') ? route('m.caja.resumen') : route('cash-register.index') }}" class="m-stat m-stat--wide">
        <div class="m-stat-top">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-cash-coin m-stat-ico" aria-hidden="true"></i>
                <span class="m-stat-label mb-0">Ventas de sesión</span>
            </div>
            <i class="bi bi-eye m-stat-ico" aria-hidden="true"></i>
        </div>
        <div class="m-stat-value" style="font-size: 22px;">
            ${{ number_format($stats['ventas_sesion'] ?? 0, 0, ',', '.') }}
        </div>
    </a>
</div>

@if(($stats['low_stock_products'] ?? 0) > 0)
    <a href="{{ route('stock.index') }}" class="m-alert">
        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
        <span>{{ $stats['low_stock_products'] }} productos con stock bajo</span>
    </a>
@endif

<div class="m-section-label">Accesos rápidos</div>
<div class="m-quick">
    <a href="{{ route('m.pedidos.index') }}" class="m-quick-item">
        <i class="bi bi-clipboard-plus" aria-hidden="true"></i>
        <span>Tomar pedido</span>
    </a>
    @if($canKitchen)
        <a href="{{ route('kitchen.index') }}" class="m-quick-item">
            <i class="bi bi-egg-fried" aria-hidden="true"></i>
            <span>Ver cocina</span>
        </a>
    @else
        <a href="{{ route('orders.index') }}" class="m-quick-item">
            <i class="bi bi-list-check" aria-hidden="true"></i>
            <span>Ver pedidos</span>
        </a>
    @endif
</div>
@endsection
