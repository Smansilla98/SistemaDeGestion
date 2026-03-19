@extends('layouts.app')

@section('title', 'Página Principal')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
                <i class="bi bi-grid-3x3-gap"></i> Página Principal
            </h1>
            <div class="text-white-50">Resumen del día — {{ now()->translatedFormat('l d \d\e F, Y') }}</div>
        </div>
    </div>
</div>

<div class="page">
    <div class="sg">
        <a href="{{ route('tables.index') }}" class="sc">
            <div class="si"><i class="bi bi-table"></i></div>
            <div class="sl">Mesas disponibles</div>
            <div class="sv">{{ $stats['mesas_libres'] }} <span>de {{ $stats['total_tables'] }}</span></div>
            <span class="badge bg-green">
                <i class="bi bi-circle-fill" style="font-size: 6px;"></i>
                {{ $stats['mesas_ocupadas'] }} ocupadas
            </span>
        </a>
        <a href="{{ route('orders.index') }}" class="sc">
            <div class="si"><i class="bi bi-receipt"></i></div>
            <div class="sl">Pedidos pendientes</div>
            <div class="sv">{{ $stats['pedidos_pendientes'] }}</div>
            <span class="badge bg-teal">
                <i class="bi bi-circle" style="font-size: 6px;"></i>
                En proceso
            </span>
        </a>
        <a href="{{ route('cash-register.index') }}" class="sc">
            <div class="si"><i class="bi bi-currency-dollar"></i></div>
            <div class="sl flex ia jb g2">
                <span>Ventas de sesión</span>
                <button type="button" class="btn btn-g btn-sm p-0 min-w-auto" style="font-size: 0.9rem;" onclick="event.preventDefault(); event.stopPropagation(); toggleVentasSesion();" title="Mostrar/ocultar monto">
                    <i class="bi bi-eye" id="ventasSesionToggleIcon"></i>
                </button>
            </div>
            <div class="sv" id="ventasSesionValue">${{ number_format($stats['ventas_sesion'] ?? 0, 0) }}</div>
            <span class="badge bg-green">
                <i class="bi bi-circle-fill" style="font-size: 6px;"></i>
                {{ ($stats['tiene_sesion_abierta'] ?? false) ? 'Sesión abierta' : 'Sin sesión' }}
            </span>
        </a>
    </div>

    @if(isset($stats['low_stock_products']) && $stats['low_stock_products'] > 0)
    <div class="sg mt4">
        <div class="sc">
            <div class="si"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="sl">Productos bajo stock</div>
            <div class="sv">{{ $stats['low_stock_products'] }}</div>
            <span class="badge bg-red">Requieren atención</span>
        </div>
    </div>
    @endif

    <div class="dashboard-grid">
        <div class="card">
            <div class="ch">
                <div class="ch-t">
                    <i class="bi bi-receipt"></i>
                    Pedidos Recientes
                </div>
                <a href="{{ route('orders.index') }}" class="btn btn-o btn-sm">Ver todos</a>
            </div>
            @if($recentOrders->count() > 0)
            <div class="tw">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Mesa/Cliente</th>
                            <th>Mozo</th>
                            <th>Estado</th>
                            <th>Total</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $order)
                        <tr>
                            <td class="td-mono td-b">
                                @if($order->table_id === null)
                                    <a href="{{ route('orders.quick.show', $order) }}" class="text-decoration-none" style="color: var(--teal-600);">{{ $order->number }}</a>
                                @else
                                    <a href="{{ route('orders.show', $order) }}" class="text-decoration-none" style="color: var(--teal-600);">{{ $order->number }}</a>
                                @endif
                            </td>
                            <td>
                                @if($order->table)
                                    Mesa {{ $order->table->number }}
                                @elseif($order->customer_name)
                                    <span class="text-muted">{{ $order->customer_name }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $order->user->name }}</td>
                            <td><span class="badge {{ $order->status === 'CERRADO' ? 'bg-gray' : ($order->status === 'LISTO' ? 'bg-blue' : 'bg-amber') }}">{{ $order->status }}</span></td>
                            <td class="td-amt">${{ number_format($order->total, 2) }}</td>
                            <td class="td-mono text-sm">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="empty">
                <i class="bi bi-inbox"></i>
                <div class="empty-t">No hay pedidos recientes</div>
                <div class="empty-s">Los pedidos del día aparecerán aquí</div>
            </div>
            @endif
        </div>

        <div class="card">
            <div class="ch">
                <div class="ch-t">
                    <i class="bi bi-star"></i>
                    Top Productos Hoy
                </div>
            </div>
            <div class="cb" style="padding: 12px 16px;">
                @if($topProducts->count() > 0)
                    @foreach($topProducts as $index => $product)
                    <div class="top-product">
                        <div class="tp-rank @if($index == 1) r2 @elseif($index == 2) r3 @endif">{{ $index + 1 }}</div>
                        <div class="tp-name">{{ $product->name }}</div>
                        <div class="tp-count">×{{ $product->total_quantity }}</div>
                    </div>
                    @endforeach
                @else
                <div class="empty" style="padding: 20px;">
                    <div class="empty-t">No hay datos</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    @if(isset($salesByWaiter) && $salesByWaiter->count() > 0 || isset($incomeByMethod) && $incomeByMethod->count() > 0)
    <div class="grid2 mt4">
        @if(isset($salesByWaiter) && $salesByWaiter->count() > 0)
        <div class="card">
            <div class="ch">
                <div class="ch-t">
                    <i class="bi bi-person-badge"></i>
                    Ventas por Mozo (Hoy)
                </div>
            </div>
            <div class="cb" style="padding: 0;">
                @foreach($salesByWaiter as $index => $waiter)
                <div style="display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-bottom: 1px solid var(--gray-50);">
                    <div style="width: 24px; height: 24px; background: var(--teal-500); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; color: white;">{{ $index + 1 }}</div>
                    <div style="flex: 1;">
                        <div style="font-size: 13px; font-weight: 500; color: var(--gray-800);">{{ $waiter->name }}</div>
                        <div style="font-size: 11px; color: var(--gray-400);">{{ $waiter->payment_count }} pagos</div>
                    </div>
                    <span class="badge bg-teal font-mono">${{ number_format($waiter->total_sales, 2) }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        @if(isset($incomeByMethod) && $incomeByMethod->count() > 0)
        <div class="card">
            <div class="ch">
                <div class="ch-t">
                    <i class="bi bi-wallet2"></i>
                    Ingresos por Método (Hoy)
                </div>
            </div>
            <div class="cb" style="padding: 0;">
                @foreach($incomeByMethod as $method)
                <div style="display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-bottom: 1px solid var(--gray-50);">
                    <div style="display: flex; align-items: center; gap: 6px; flex: 1; font-size: 13px; color: var(--gray-700); font-weight: 500;">
                        @if($method->payment_method === 'EFECTIVO')<i class="bi bi-cash"></i>
                        @elseif($method->payment_method === 'DEBITO')<i class="bi bi-credit-card"></i>
                        @elseif($method->payment_method === 'CREDITO')<i class="bi bi-credit-card-2-front"></i>
                        @elseif($method->payment_method === 'TRANSFERENCIA')<i class="bi bi-bank"></i>
                        @elseif($method->payment_method === 'QR')<i class="bi bi-qr-code"></i>
                        @else<i class="bi bi-wallet2"></i>
                        @endif
                        {{ $method->payment_method }}
                    </div>
                    <span class="badge bg-green font-mono">${{ number_format($method->total, 2) }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    @if(isset($activeTables) && $activeTables->count() > 0)
    <div class="card mt4">
        <div class="ch">
            <div class="ch-t">
                <i class="bi bi-table"></i>
                Mesas Activas
            </div>
            <a href="{{ route('tables.index') }}" class="btn btn-o btn-sm">Ver todas</a>
        </div>
        <div class="cb">
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px;">
                @foreach($activeTables as $table)
                <div style="padding: 12px; border: 1px solid var(--gray-100); border-radius: var(--r-md);">
                    <div style="font-weight: 600; color: var(--gray-800);">Mesa {{ $table->number }}</div>
                    @if($table->currentSession && $table->currentSession->waiter)
                    <div class="text-sm text-muted">{{ $table->currentSession->waiter->name }}</div>
                    @endif
                    @if($table->sector)
                    <div class="text-sm text-muted">{{ $table->sector->name }}</div>
                    @endif
                    @if($table->currentSession && $table->currentSession->started_at)
                    <div class="text-sm text-muted">{{ $table->currentSession->started_at->format('H:i') }}</div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @if($outOfStockProducts->count() > 0 || $lowStockProducts->count() > 0)
    <div class="grid2 mt4">
        @if($outOfStockProducts->count() > 0)
        <div class="card" style="border-left: 4px solid var(--red-500);">
            <div class="ch">
                <div class="ch-t">
                    <i class="bi bi-exclamation-triangle-fill" style="color: var(--red-500);"></i>
                    Productos sin stock
                </div>
                <span class="badge bg-red">{{ $outOfStockProducts->count() }}</span>
            </div>
            <div class="cb">
                @foreach($outOfStockProducts as $product)
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--gray-50);">
                    <div>
                        <div class="font-600">{{ $product->name }}</div>
                        <small class="text-muted">{{ $product->category->name ?? 'Sin categoría' }}</small>
                    </div>
                    <span class="badge bg-red">Sin stock</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        @if($lowStockProducts->count() > 0)
        <div class="card" style="border-left: 4px solid var(--amber-500);">
            <div class="ch">
                <div class="ch-t">
                    <i class="bi bi-exclamation-circle" style="color: var(--amber-500);"></i>
                    Productos con stock bajo
                </div>
                <span class="badge bg-amber">{{ $lowStockProducts->count() }}</span>
            </div>
            <div class="cb">
                @foreach($lowStockProducts as $product)
                @php $currentStock = $product->getCurrentStock(auth()->user()->restaurant_id); @endphp
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--gray-50);">
                    <div>
                        <div class="font-600">{{ $product->name }}</div>
                        <small class="text-muted">Stock: {{ $currentStock }} (mín: {{ $product->stock_minimum }})</small>
                    </div>
                    <span class="badge bg-amber">{{ $currentStock }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif
</div>

@push('scripts')
<script>
(function() {
    var STORAGE_KEY = 'dashboard_ventas_sesion_oculto';
    var el = document.getElementById('ventasSesionValue');
    var icon = document.getElementById('ventasSesionToggleIcon');
    if (!el || !icon) return;

    function aplicarEstado(oculto) {
        el.style.visibility = oculto ? 'hidden' : 'visible';
        el.style.opacity = oculto ? '0' : '1';
        el.setAttribute('aria-hidden', oculto ? 'true' : 'false');
        icon.classList.remove('bi-eye', 'bi-eye-slash');
        icon.classList.add(oculto ? 'bi-eye-slash' : 'bi-eye');
        icon.setAttribute('title', oculto ? 'Mostrar monto' : 'Ocultar monto');
    }

    window.toggleVentasSesion = function() {
        var oculto = localStorage.getItem(STORAGE_KEY) === '1';
        oculto = !oculto;
        localStorage.setItem(STORAGE_KEY, oculto ? '1' : '0');
        aplicarEstado(oculto);
    };

    aplicarEstado(localStorage.getItem(STORAGE_KEY) === '1');
})();
</script>
@endpush
@endsection
