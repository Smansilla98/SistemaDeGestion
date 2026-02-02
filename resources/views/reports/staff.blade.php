@extends('layouts.app')

@section('title', 'Reporte por Personal')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('reports.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-people"></i> Ventas por Mozo</h1>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <form method="GET" action="{{ route('reports.staff') }}" class="row g-3">
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
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Mozo</th>
                        <th>Pedidos</th>
                        <th>Ventas Totales</th>
                        <th>Promedio por Pedido</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salesByStaff as $sale)
                    <tr>
                        <td><strong>{{ $sale->name }}</strong></td>
                        <td>
                            <span class="badge bg-info">{{ $sale->total_orders }}</span>
                        </td>
                        <td><strong>${{ number_format($sale->total_sales, 2) }}</strong></td>
                        <td>${{ number_format($sale->total_sales / ($sale->total_orders ?: 1), 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">No hay datos para el período seleccionado</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

