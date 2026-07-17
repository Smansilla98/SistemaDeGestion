@extends('layouts.mobile')

@section('title', 'Inicio')

{{--
  V2 del dashboard mobile — usa las mismas variables $stats que
  resources/views/dashboard.blade.php (web) para mantener paridad de datos.
  Requiere que $stats incluya: mesas_libres, total_tables, mesas_ocupadas,
  pedidos_pendientes, ventas_sesion, tiene_sesion_abierta, low_stock_products.
  Ver app/Http/Controllers/Mobile/MobileDashboardController.php
--}}

@section('content')
<div class="mob-dash">

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
