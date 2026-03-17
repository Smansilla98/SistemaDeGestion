@extends('layouts.app')

@section('title', 'Reporte de Ventas')

@section('content')
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
            ]);
            $movementsRows = $session->cashMovements->map(fn($m) => (object)[
                'created_at' => $m->created_at,
                'type' => $m->type,
                'description' => $m->description,
                'amount' => $m->amount,
                'payment_method' => null,
            ]);
            $allRows = $paymentsRows->concat($movementsRows)->sortBy('created_at');
            $totalVentas = $session->payments->sum('amount');
            $totalIngresos = $session->cashMovements->where('type', 'INGRESO')->sum('amount');
            $totalEgresos = $session->cashMovements->where('type', 'EGRESO')->sum('amount');
        @endphp
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <strong>{{ $session->cashRegister->name ?? 'Caja' }}</strong>
                    <span class="text-muted ms-2">· Abierta por {{ $session->user->name ?? 'N/A' }}</span>
                    <span class="badge bg-{{ $session->status === 'ABIERTA' ? 'success' : 'secondary' }} ms-2">{{ $session->status }}</span>
                </div>
                <div class="small">
                    {{ $session->opened_at->format('d/m/Y H:i') }}
                    @if($session->closed_at)
                        — Cierre: {{ $session->closed_at->format('d/m/Y H:i') }}
                    @else
                        — <span class="text-success">Sesión abierta</span>
                    @endif
                </div>
            </div>
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
                                <td>{{ $row->description }}</td>
                                <td class="text-end fw-bold">${{ number_format($row->amount, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
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
@endsection

