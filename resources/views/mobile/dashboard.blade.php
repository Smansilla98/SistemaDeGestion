@extends('layouts.mobile')

@section('title', 'Inicio')

@section('content')
@php
    $isAdmin = $isAdmin ?? (($rol ?? '') === 'ADMIN');
    $isSupervisor = $isSupervisor ?? in_array($rol ?? '', ['GERENTE', 'SUPERADMIN'], true);
    $mgmt = $management ?? [];
@endphp

<div class="mob-dash">

@if($isAdmin)
    {{-- ADMIN: ABM y control total --}}
    <a href="{{ route('stock.index') }}" class="md-card md-card--wide {{ ($mgmt['low_stock_products'] ?? 0) > 0 ? 'md-card--amber' : 'md-card--teal' }}">
        <i class="bi bi-box-seam" aria-hidden="true"></i>
        <span class="md-label">Stock bajo</span>
        <span class="md-value">
            {{ $mgmt['low_stock_products'] ?? 0 }}
            <small>/ {{ $mgmt['stock_ok_products'] ?? 0 }} ok</small>
        </span>
        <span class="md-sub">
            @if(($mgmt['low_stock_products'] ?? 0) > 0)
                Hay productos por debajo del mínimo
            @else
                Sin alertas de stock bajo
            @endif
        </span>
    </a>

    <a href="{{ route('cash-register.index') }}" class="md-card md-card--wide md-card--teal">
        <i class="bi bi-cash-coin" aria-hidden="true"></i>
        <span class="md-label">Cajas abiertas ahora</span>
        <span class="md-value">{{ $mgmt['open_cash_sessions'] ?? 0 }}</span>
        <span class="md-sub">
            @if(!empty($mgmt['open_cash_session_labels']))
                {{ implode(' · ', $mgmt['open_cash_session_labels']) }}
            @else
                Ninguna sesión abierta
            @endif
        </span>
    </a>

    <p class="md-section-label">Acciones rápidas</p>
    <div class="md-actions">
        <a href="{{ route('products.create') }}" class="md-action-btn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo producto
        </a>
        <a href="{{ route('stock.index') }}" class="md-action-btn">
            <i class="bi bi-box-seam" aria-hidden="true"></i> Ver stock
        </a>
        <a href="{{ route('cash-register.index') }}" class="md-action-btn">
            <i class="bi bi-cash-coin" aria-hidden="true"></i> Gestionar cajas
        </a>
    </div>

@elseif($isSupervisor)
    {{-- GERENTE / SUPERADMIN: panorama, sin altas --}}
    <a href="{{ route('reports.index') }}" class="md-card md-card--wide md-card--teal">
        <i class="bi bi-graph-up" aria-hidden="true"></i>
        <span class="md-label">Ventas del día</span>
        <span class="md-value md-value--lg">${{ number_format($mgmt['ventas_hoy'] ?? 0, 0) }}</span>
        <span class="md-sub">
            @if($mgmt['tiene_sesion_abierta'] ?? false)
                Sesión: ${{ number_format($mgmt['ventas_sesion'] ?? 0, 0) }}
            @else
                Pedidos cerrados hoy
            @endif
        </span>
    </a>

    <div class="md-alert">
        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
        <div>
            <span class="md-alert-title">Stock bajo</span>
            <span class="md-alert-sub">
                @if(($mgmt['low_stock_products'] ?? 0) > 0)
                    {{ $mgmt['low_stock_products'] }} {{ ($mgmt['low_stock_products'] ?? 0) === 1 ? 'producto' : 'productos' }} por debajo del mínimo
                @else
                    Sin alertas de stock
                @endif
            </span>
        </div>
    </div>

    <div class="md-card md-card--wide md-card--teal">
        <i class="bi bi-cash-coin" aria-hidden="true"></i>
        <span class="md-label">Cajas abiertas ahora</span>
        <span class="md-value">{{ $mgmt['open_cash_sessions'] ?? 0 }}</span>
        <span class="md-sub">
            @if(!empty($mgmt['open_cash_session_labels']))
                {{ implode(' · ', $mgmt['open_cash_session_labels']) }}
            @else
                Ninguna sesión abierta
            @endif
        </span>
    </div>

@else
    {{-- Dashboard operativo (mozo / cajero / encargado / default) --}}
    <div class="md-grid2">
        <a href="{{ route('tables.index') }}" class="md-card md-card--teal">
            <i class="bi bi-table" aria-hidden="true"></i>
            <span class="md-label">Mesas libres</span>
            <span class="md-value">{{ $stats['mesas_libres'] }} <small>de {{ $stats['total_tables'] }}</small></span>
        </a>
        <a href="{{ route('orders.index') }}" class="md-card md-card--amber">
            <i class="bi bi-receipt" aria-hidden="true"></i>
            <span class="md-label">Pedidos pendientes</span>
            <span class="md-value">{{ $stats['pedidos_pendientes'] }}</span>
        </a>
    </div>

    <a href="{{ route('cash-register.index') }}" class="md-card md-card--wide md-card--teal">
        <div class="md-wide-top">
            <span class="md-label">Ventas de sesión</span>
            <button
                type="button"
                class="md-eye"
                onclick="event.preventDefault(); event.stopPropagation(); toggleVentasSesionMobile();"
                aria-label="Mostrar u ocultar monto de ventas"
                aria-controls="ventasSesionValueMobile"
                title="Mostrar/ocultar monto"
            >
                <i class="bi bi-eye" id="ventasSesionToggleIconMobile" aria-hidden="true"></i>
            </button>
        </div>
        <span class="md-value md-value--lg" id="ventasSesionValueMobile">${{ number_format($stats['ventas_sesion'] ?? 0, 0) }}</span>
        <span class="md-sub">{{ ($stats['tiene_sesion_abierta'] ?? false) ? 'Sesión abierta' : 'Sin sesión' }}</span>
    </a>

    @if(isset($stats['low_stock_products']) && $stats['low_stock_products'] > 0)
    <a href="{{ route('stock.index') }}" class="md-alert">
        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
        <div>
            <span class="md-alert-title">Stock bajo</span>
            <span class="md-alert-sub">{{ $stats['low_stock_products'] }} {{ $stats['low_stock_products'] === 1 ? 'producto' : 'productos' }} por debajo del mínimo</span>
        </div>
    </a>
    @endif

    <p class="md-section-label">Acciones rápidas</p>
    <div class="md-actions">
        <a href="{{ route('orders.create') }}" class="md-action-btn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i> Tomar pedido
        </a>
        <a href="{{ route('tables.index') }}" class="md-action-btn">
            <i class="bi bi-table" aria-hidden="true"></i> Ver mesas
        </a>
        <a href="{{ route('cash-register.index') }}" class="md-action-btn">
            <i class="bi bi-cash-coin" aria-hidden="true"></i> Cerrar caja
        </a>
    </div>
@endif

</div>
@endsection

@push('scripts')
<script>
    function toggleVentasSesionMobile() {
        const valueEl = document.getElementById('ventasSesionValueMobile');
        const iconEl = document.getElementById('ventasSesionToggleIconMobile');
        if (!valueEl || !iconEl) return;

        const isHidden = valueEl.dataset.hidden === 'true';
        if (isHidden) {
            valueEl.textContent = valueEl.dataset.realValue;
            valueEl.dataset.hidden = 'false';
            iconEl.classList.replace('bi-eye-slash', 'bi-eye');
        } else {
            valueEl.dataset.realValue = valueEl.textContent;
            valueEl.textContent = '••••••';
            valueEl.dataset.hidden = 'true';
            iconEl.classList.replace('bi-eye', 'bi-eye-slash');
        }
    }
</script>
@endpush
