@extends('layouts.app')

@section('title', 'Reporte de Productos')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('reports.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1 class="text-white mb-2 page-hero-title"><i class="bi bi-box-seam"></i> Productos Más Vendidos</h1>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex flex-column flex-md-row flex-wrap justify-content-between align-items-stretch align-items-md-center gap-3 report-filters-header">
        <form method="GET" action="{{ route('reports.products') }}" class="row g-3 flex-grow-1 w-100 mb-0">
            <div class="col-12 col-md-4">
                <label class="form-label">Fecha Desde</label>
                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Fecha Hasta</label>
                <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
            </div>
            <div class="col-12 col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
        <a href="{{ route('reports.products.export') }}" class="btn btn-sm btn-success w-100 w-md-auto text-center">
            <i class="bi bi-file-earmark-excel"></i> Exportar Excel
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th>Cantidad Vendida</th>
                        <th>Ingresos Totales</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topProducts as $index => $product)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $product->name }}</strong></td>
                        <td>
                            <span class="badge bg-primary">{{ $product->total_quantity }}</span>
                        </td>
                        <td><strong>${{ number_format($product->total_revenue, 2) }}</strong></td>
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

