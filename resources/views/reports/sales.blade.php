@extends('layouts.app')

@section('title', 'Reporte de Ventas')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('reports.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1><i class="bi bi-currency-dollar"></i> Reporte de Ventas</h1>
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
        <a href="{{ route('reports.sales.export', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-sm btn-success ms-3">
            <i class="bi bi-file-earmark-excel"></i> Exportar Excel
        </a>
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
        <div class="table-responsive">
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
    </div>
</div>
@endsection

