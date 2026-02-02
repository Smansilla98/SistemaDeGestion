@extends('layouts.app')

@section('title', 'Reportes')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-graph-up"></i> Reportes</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-currency-dollar" style="font-size: 3rem; color: #0d6efd;"></i>
                <h5 class="card-title mt-3">Ventas</h5>
                <p class="card-text text-muted">Reporte de ventas diarias y por método de pago</p>
                <a href="{{ route('reports.sales') }}" class="btn btn-primary">
                    <i class="bi bi-arrow-right"></i> Ver Reporte
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-box-seam" style="font-size: 3rem; color: #198754;"></i>
                <h5 class="card-title mt-3">Productos</h5>
                <p class="card-text text-muted">Productos más vendidos y estadísticas</p>
                <a href="{{ route('reports.products') }}" class="btn btn-success">
                    <i class="bi bi-arrow-right"></i> Ver Reporte
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-people" style="font-size: 3rem; color: #ffc107;"></i>
                <h5 class="card-title mt-3">Personal</h5>
                <p class="card-text text-muted">Ventas por mozo y rendimiento del personal</p>
                <a href="{{ route('reports.staff') }}" class="btn btn-warning">
                    <i class="bi bi-arrow-right"></i> Ver Reporte
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

