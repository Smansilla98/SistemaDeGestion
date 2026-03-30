@extends('layouts.app')

@section('title', 'Reporte de Ventas')

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
@endif
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('reports.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-currency-dollar"></i> Reporte de Ventas</h1>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <form method="GET" action="{{ route('reports.sales') }}" class="row g-3 flex-grow-1">
            <div class="col-md-4">
                <label class="form-label">Fecha Desde</label>
                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Fecha Hasta</label>
                <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
        <div class="d-flex flex-wrap gap-2 ms-3">
            <a href="{{ route('reports.sales.export', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-sm btn-success">
                <i class="bi bi-file-earmark-excel"></i> Ventas Excel
            </a>
            <a href="{{ route('reports.sales.export-pdf', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-sm btn-danger">
                <i class="bi bi-file-earmark-pdf"></i> Ventas PDF
            </a>
            <a href="{{ route('reports.orders.export', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-file-earmark-spreadsheet"></i> Pedidos Excel
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5>Total de Ventas</h5>
                        <h2>${{ number_format($totalSales, 2) }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5>Total de Pedidos</h5>
                        <h2>{{ $totalOrders }}</h2>
                    </div>
                </div>
            </div>
        </div>

        <h5>Ventas por Método de Pago</h5>
        <div class="table-responsive mb-4">
            <table class="table">
                <thead>
                    <tr>
                        <th>Método de Pago</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($salesByMethod as $sale)
                    <tr>
                        <td>{{ $sale->payment_method }}</td>
                        <td><strong>${{ number_format($sale->total, 2) }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <h5>Ventas por Día</h5>
        <div class="table-responsive mb-4">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($salesByDay as $sale)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($sale->date)->format('d/m/Y') }}</td>
                        <td><strong>${{ number_format($sale->total, 2) }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(isset($cashSessions) && $cashSessions->count() > 0)
        <hr class="my-4">
        <h5 class="mb-3"><i class="bi bi-cash-stack"></i> Detalle de movimientos por caja</h5>
        <p class="text-muted small">Sesiones con actividad en el período. Ventas (pagos) e ingresos/egresos de cada caja.</p>
        @foreach($cashSessions as $session)
        @php
            $paymentsRows = $session->payments->map(fn($p) => (object)[
                'created_at' => $p->created_at,
                'type' => 'Venta',
                'description' => $p->order ? 'Pedido ' . $p->order->number . ($p->order->table ? ' · Mesa ' . $p->order->table->number : ($p->order->customer_name ? ' · ' . $p->order->customer_name : '')) : 'Pago',
                'amount' => $p->amount,
                'payment_method' => $p->payment_method,
                'order_id' => $p->order_id,
                'order' => $p->order,
                'cash_movement_id' => null,
            ]);
            $movementsRows = $session->cashMovements->map(fn($m) => (object)[
                'created_at' => $m->created_at,
                'type' => $m->type,
                'description' => $m->description,
                'amount' => $m->amount,
                'payment_method' => null,
                'order_id' => null,
                'order' => null,
                'cash_movement_id' => $m->id,
            ]);
            $allRows = $paymentsRows->concat($movementsRows)->sortBy('created_at');
            $totalVentas = $session->payments->sum('amount');
            $totalIngresos = $session->cashMovements->where('type', 'INGRESO')->sum('amount');
            $totalEgresos = $session->cashMovements->where('type', 'EGRESO')->sum('amount');
        @endphp
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 cursor-pointer py-3"
                 role="button"
                 data-bs-toggle="collapse"
                 data-bs-target="#session-detail-{{ $session->id }}"
                 aria-expanded="false"
                 aria-controls="session-detail-{{ $session->id }}">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <i class="bi bi-chevron-down session-chevron text-muted" style="transition: transform 0.2s ease;"></i>
                    <strong>{{ $session->cashRegister->name ?? 'Caja' }}</strong>
                    <span class="text-muted">· Abierta por {{ $session->user->name ?? 'N/A' }}</span>
                    <span class="badge bg-{{ $session->status === 'ABIERTA' ? 'success' : 'secondary' }}">{{ $session->status }}</span>
                </div>
                <div class="small d-flex align-items-center gap-2 flex-wrap">
                    <span class="text-muted">{{ $session->opened_at->format('d/m/Y H:i') }}</span>
                    @if($session->closed_at)
                        <span>— Cierre: {{ $session->closed_at->format('d/m/Y H:i') }}</span>
                    @else
                        <span class="text-success">Sesión abierta</span>
                    @endif
                    @if(auth()->user()->isSuperAdmin())
                    <form action="{{ route('reports.sales.destroy-cash-session', $session) }}"
                          method="POST"
                          class="d-inline ms-1 js-delete-cash-session-form"
                          data-session-label="{{ e(($session->cashRegister->name ?? 'Caja').' · '.$session->opened_at->format('d/m/Y H:i')) }}">
                        @csrf
                        @method('DELETE')
                        @foreach(request()->only(['date_from', 'date_to']) as $k => $v)
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endforeach
                        <button type="button" class="btn btn-sm btn-outline-danger js-delete-cash-session-btn" title="Eliminar sesión de caja (solo superadmin)">
                            <i class="bi bi-trash"></i> Eliminar sesión
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            <div class="collapse" id="session-detail-{{ $session->id }}">
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-auto"><span class="badge bg-light text-dark">Inicial: ${{ number_format($session->initial_amount, 2) }}</span></div>
                        <div class="col-auto"><span class="badge bg-success">Ventas: ${{ number_format($totalVentas, 2) }}</span></div>
                        <div class="col-auto"><span class="badge bg-info">Ingresos: ${{ number_format($totalIngresos, 2) }}</span></div>
                        <div class="col-auto"><span class="badge bg-danger">Egresos: ${{ number_format($totalEgresos, 2) }}</span></div>
                        @if($session->closed_at && $session->final_amount !== null)
                        <div class="col-auto"><span class="badge bg-primary">Cierre: ${{ number_format($session->final_amount, 2) }}</span></div>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha / Hora</th>
                                    <th>Tipo</th>
                                    <th>Descripción</th>
                                    <th class="text-end">Monto</th>
                                    @if(auth()->user()->isAdminLevel())
                                    <th class="text-center text-nowrap">Acciones</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($allRows as $row)
                                <tr>
                                    <td>{{ $row->created_at->format('d/m H:i') }}</td>
                                    <td>
                                        @if($row->type === 'Venta')
                                            <span class="badge bg-success">{{ $row->type }}</span>
                                            @if($row->payment_method)<small class="text-muted">· {{ $row->payment_method }}</small>@endif
                                        @elseif($row->type === 'INGRESO')
                                            <span class="badge bg-info">{{ $row->type }}</span>
                                        @else
                                            <span class="badge bg-danger">{{ $row->type }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($row->type === 'Venta' && $row->order)
                                            <a href="{{ $row->order->table_id ? route('orders.show', $row->order) : route('orders.quick.show', $row->order) }}" class="text-decoration-none fw-medium" title="Ver detalle del pedido (todo lo consumido)">
                                                {{ $row->description }}
                                                <i class="bi bi-box-arrow-up-right small ms-1"></i>
                                            </a>
                                        @else
                                            {{ $row->description }}
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold">${{ number_format($row->amount, 2) }}</td>
                                    @if(auth()->user()->isAdminLevel())
                                    <td class="text-center">
                                        @if(!empty($row->cash_movement_id))
                                        <form action="{{ route('cash-register.destroy-movement', $row->cash_movement_id) }}"
                                              method="POST"
                                              class="d-inline"
                                              id="deleteMovementFormSales{{ $row->cash_movement_id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger js-delete-movement-sales"
                                                    data-movement-id="{{ $row->cash_movement_id }}"
                                                    title="Eliminar: {{ \Illuminate\Support\Str::limit($row->description, 80) }}">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        @else
                                        <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
        @else
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-2"><i class="bi bi-cash-stack"></i> Detalle de movimientos por caja</h5>
                <p class="text-muted mb-0">No hay sesiones de caja con actividad en el período seleccionado.</p>
            </div>
        </div>
        @endif
    </div>
</div>

@if(isset($cashSessions) && $cashSessions->count() > 0)
@push('scripts')
<script>
function confirmDeleteMovementSales(movementId) {
    Swal.fire({
        icon: 'warning',
        title: '¿Eliminar movimiento de caja?',
        html: '<p>Se eliminará este ingreso o egreso manual de la sesión.</p>' +
            '<p class="text-danger small mt-2 mb-0"><strong>Esta acción no se puede deshacer.</strong></p>',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-trash"></i> Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then(function(result) {
        if (result.isConfirmed) {
            Swal.fire({ title: 'Eliminando...', allowOutsideClick: false, allowEscapeKey: false, didOpen: function() { Swal.showLoading(); } });
            document.getElementById('deleteMovementFormSales' + movementId).submit();
        }
    });
}
document.body.addEventListener('click', function(e) {
    var btn = e.target.closest('.js-delete-movement-sales');
    if (!btn) return;
    e.preventDefault();
    var id = btn.getAttribute('data-movement-id');
    if (id) confirmDeleteMovementSales(parseInt(id, 10));
});
document.querySelectorAll('.js-delete-cash-session-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var form = btn.closest('.js-delete-cash-session-form');
        var label = form ? form.getAttribute('data-session-label') : '';
        Swal.fire({
            icon: 'warning',
            title: '¿Eliminar sesión de caja?',
            html: '<p>Se eliminará la sesión <strong>' + (label || '') + '</strong> y sus movimientos manuales.</p>' +
                '<p class="small text-muted mb-0">Los pagos de ventas se conservan en los pedidos pero ya no quedarán vinculados a esta sesión.</p>' +
                '<p class="text-danger small mt-2 mb-0"><strong>Irreversible.</strong></p>',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar sesión',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then(function(result) {
            if (result.isConfirmed && form) {
                Swal.fire({ title: 'Eliminando...', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });
                form.submit();
            }
        });
    });
});
document.querySelectorAll('[data-bs-toggle="collapse"][data-bs-target^="#session-detail-"]').forEach(function(header) {
    var targetId = header.getAttribute('data-bs-target');
    var target = document.querySelector(targetId);
    if (!target) return;
    target.addEventListener('show.bs.collapse', function() {
        var chevron = header.querySelector('.session-chevron');
        if (chevron) { chevron.classList.remove('bi-chevron-down'); chevron.classList.add('bi-chevron-up'); chevron.style.transform = 'rotate(0deg)'; }
    });
    target.addEventListener('hide.bs.collapse', function() {
        var chevron = header.querySelector('.session-chevron');
        if (chevron) { chevron.classList.remove('bi-chevron-up'); chevron.classList.add('bi-chevron-down'); }
    });
});
</script>
@endpush
<style>
.cursor-pointer { cursor: pointer; }
.card-header[role="button"]:hover { background-color: rgba(0,0,0,.03); }
</style>
@endif
@endsection

