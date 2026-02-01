@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
    .mosaic-dashboard {
        background: linear-gradient(135deg, #1e8081 0%, #22565e 50%, #262c3b 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }

    .mosaic-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        border: none;
        position: relative;
        overflow: hidden;
    }

    .mosaic-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--card-gradient-start), var(--card-gradient-end));
    }

    .mosaic-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
    }

    .mosaic-card-primary {
        --card-gradient-start: #1e8081;
        --card-gradient-end: #22565e;
    }

    .mosaic-card-success {
        --card-gradient-start: #1e8081;
        --card-gradient-end: #22565e;
    }

    .mosaic-card-warning {
        --card-gradient-start: #7b7d84;
        --card-gradient-end: #22565e;
    }

    .mosaic-card-info {
        --card-gradient-start: #1e8081;
        --card-gradient-end: #22565e;
    }

    .mosaic-card-danger {
        --card-gradient-start: #c94a2d;
        --card-gradient-end: #e67e51;
    }

    .mosaic-card-purple {
        --card-gradient-start: #a8edea;
        --card-gradient-end: #fed6e3;
    }

    .mosaic-icon {
        width: 80px;
        height: 80px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        background: linear-gradient(135deg, var(--card-gradient-start), var(--card-gradient-end));
        color: white;
        margin-bottom: 1.5rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .mosaic-value {
        font-size: 3rem;
        font-weight: 700;
        color: #1a202c;
        margin: 0.5rem 0;
        line-height: 1;
    }

    .mosaic-label {
        font-size: 0.875rem;
        color: #718096;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .mosaic-trend {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.875rem;
        font-weight: 600;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        margin-top: 0.5rem;
    }

    .mosaic-trend-up {
        background: #c6f6d5;
        color: #22543d;
    }

    .mosaic-trend-down {
        background: #fed7d7;
        color: #742a2a;
    }

    .mosaic-section-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        border: none;
        height: 100%;
    }

    .mosaic-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #e2e8f0;
    }

    .mosaic-section-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1a202c;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .mosaic-section-title i {
        font-size: 1.5rem;
        background: linear-gradient(135deg, #1e8081, #22565e);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .mosaic-table {
        width: 100%;
    }

    .mosaic-table thead th {
        background: #f7fafc;
        color: #4a5568;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 1px;
        padding: 1rem;
        border: none;
    }

    .mosaic-table tbody td {
        padding: 1rem;
        border-bottom: 1px solid #e2e8f0;
        color: #2d3748;
    }

    .mosaic-table tbody tr:hover {
        background: #f7fafc;
    }

    .mosaic-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .mosaic-list-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        border-bottom: 1px solid #e2e8f0;
        transition: all 0.2s ease;
    }

    .mosaic-list-item:hover {
        background: #f7fafc;
        transform: translateX(5px);
    }

    .mosaic-list-item:last-child {
        border-bottom: none;
    }

    .mosaic-list-number {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #1e8081, #22565e);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        margin-right: 1rem;
        flex-shrink: 0;
    }

    .mosaic-list-content {
        flex: 1;
    }

    .mosaic-list-title {
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 0.25rem;
    }

    .mosaic-list-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .mosaic-empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #a0aec0;
    }

    .mosaic-empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    @media (max-width: 768px) {
        .mosaic-dashboard {
            padding: 1rem 0;
        }

        .mosaic-card {
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .mosaic-value {
            font-size: 2rem;
        }

        .mosaic-icon {
            width: 60px;
            height: 60px;
            font-size: 2rem;
        }
    }
</style>
@endpush

@section('content')
<div class="mosaic-dashboard">
    <div class="container-fluid">
        <!-- Header -->
<div class="row mb-4">
    <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </h1>
                        <p class="text-white-50 mb-0">Resumen general del restaurante</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="mosaic-card mosaic-card-primary">
                    <div class="mosaic-icon">
                        <i class="bi bi-table"></i>
                    </div>
                    <div class="mosaic-label">Mesas Ocupadas</div>
                    <div class="mosaic-value">{{ $stats['mesas_ocupadas'] }}</div>
                    <div class="mosaic-trend mosaic-trend-up">
                        <i class="bi bi-arrow-up"></i>
                        <span>Activas</span>
            </div>
        </div>
    </div>

            <div class="col-md-6 col-lg-3">
                <div class="mosaic-card mosaic-card-success">
                    <div class="mosaic-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="mosaic-label">Mesas Libres</div>
                    <div class="mosaic-value">{{ $stats['mesas_libres'] }}</div>
                    <div class="mosaic-trend mosaic-trend-up">
                        <i class="bi bi-check"></i>
                        <span>Disponibles</span>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="mosaic-card mosaic-card-warning">
                    <div class="mosaic-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="mosaic-label">Pedidos Pendientes</div>
                    <div class="mosaic-value">{{ $stats['pedidos_pendientes'] }}</div>
                    <div class="mosaic-trend mosaic-trend-up">
                        <i class="bi bi-hourglass-split"></i>
                        <span>En proceso</span>
            </div>
        </div>
    </div>

            <div class="col-md-6 col-lg-3">
                <div class="mosaic-card mosaic-card-info">
                    <div class="mosaic-icon">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="mosaic-label">Ventas de Hoy</div>
                    <div class="mosaic-value">${{ number_format($stats['ventas_hoy'], 0) }}</div>
                    <div class="mosaic-trend mosaic-trend-up">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span>Hoy</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Stats -->
        @if(isset($stats['low_stock_products']) && $stats['low_stock_products'] > 0)
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="mosaic-card mosaic-card-danger">
                    <div class="mosaic-icon">
                        <i class="bi bi-exclamation-triangle"></i>
    </div>
                    <div class="mosaic-label">Productos Bajo Stock</div>
                    <div class="mosaic-value">{{ $stats['low_stock_products'] }}</div>
                    <div class="mosaic-trend mosaic-trend-down">
                        <i class="bi bi-arrow-down"></i>
                        <span>Requieren atención</span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Main Content -->
        <div class="row g-4">
            <!-- Pedidos Recientes -->
            <div class="col-lg-8">
                <div class="mosaic-section-card">
                    <div class="mosaic-section-header">
                        <div class="mosaic-section-title">
                            <i class="bi bi-receipt"></i>
                            <span>Pedidos Recientes</span>
    </div>
                        <a href="{{ route('orders.index') }}" class="btn btn-sm" style="background: linear-gradient(135deg, #1e8081, #22565e); color: white; border: none; border-radius: 10px;">
                            Ver todos
                        </a>
            </div>
                    <div class="table-responsive">
                        @if($recentOrders->count() > 0)
                            <table class="mosaic-table">
                            <thead>
                                <tr>
                                    <th>Número</th>
                                    <th>Mesa</th>
                                    <th>Mozo</th>
                                    <th>Estado</th>
                                    <th>Total</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentOrders as $order)
                                <tr>
                                    <td>
                                            <a href="{{ route('orders.show', $order) }}" style="color: #1e8081; font-weight: 600; text-decoration: none;">
                                                {{ $order->number }}
                                            </a>
                                    </td>
                                    <td>{{ $order->table->number }}</td>
                                    <td>{{ $order->user->name }}</td>
                                    <td>
                                            <span class="mosaic-badge" style="background: {{ $order->status === 'CERRADO' ? '#c6f6d5' : ($order->status === 'LISTO' ? '#bee3f8' : '#fed7d7') }}; color: {{ $order->status === 'CERRADO' ? '#22543d' : ($order->status === 'LISTO' ? '#2c5282' : '#742a2a') }};">
                                            {{ $order->status }}
                                        </span>
                                    </td>
                                        <td style="font-weight: 600; color: #2d3748;">${{ number_format($order->total, 2) }}</td>
                                        <td style="color: #718096;">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                            <div class="mosaic-empty-state">
                                <i class="bi bi-inbox"></i>
                                <p>No hay pedidos recientes</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Productos Más Vendidos -->
            <div class="col-lg-4">
                <div class="mosaic-section-card">
                    <div class="mosaic-section-header">
                        <div class="mosaic-section-title">
                            <i class="bi bi-trophy"></i>
                            <span>Top Productos Hoy</span>
                        </div>
                    </div>
                    @if($topProducts->count() > 0)
                        <div>
                            @foreach($topProducts as $index => $product)
                            <div class="mosaic-list-item">
                                <div class="mosaic-list-number">{{ $index + 1 }}</div>
                                <div class="mosaic-list-content">
                                    <div class="mosaic-list-title">{{ $product->name }}</div>
                                </div>
                                <span class="mosaic-list-badge" style="background: linear-gradient(135deg, #1e8081, #22565e); color: white;">
                                    {{ $product->total_quantity }}
                                </span>
                            </div>
                            @endforeach
                    </div>
                @else
                        <div class="mosaic-empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>No hay datos disponibles</p>
                        </div>
                @endif
            </div>
        </div>
    </div>

        <!-- Alertas de Stock -->
        @if($outOfStockProducts->count() > 0 || $lowStockProducts->count() > 0)
        <div class="row g-4 mt-4">
            @if($outOfStockProducts->count() > 0)
            <div class="col-lg-6">
                <div class="mosaic-section-card" style="border-left: 6px solid #dc3545;">
                    <div class="mosaic-section-header">
                        <div class="mosaic-section-title">
                            <i class="bi bi-exclamation-triangle-fill" style="color: #dc3545;"></i>
                            <span style="color: #dc3545;">Productos Sin Stock</span>
                        </div>
                        <span class="badge bg-danger fs-6">{{ $outOfStockProducts->count() }}</span>
                    </div>
                    <div>
                        @foreach($outOfStockProducts as $product)
                        <div class="mosaic-list-item" style="border-left: 3px solid #dc3545;">
                            <div class="mosaic-list-content">
                                <div class="mosaic-list-title">{{ $product->name }}</div>
                                <small class="text-muted">{{ $product->category->name ?? 'Sin categoría' }}</small>
                            </div>
                            <span class="badge bg-danger">
                                <i class="bi bi-x-circle-fill"></i> Sin Stock
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @if($lowStockProducts->count() > 0)
            <div class="col-lg-6">
                <div class="mosaic-section-card" style="border-left: 6px solid #ffc107;">
                    <div class="mosaic-section-header">
                        <div class="mosaic-section-title">
                            <i class="bi bi-exclamation-circle-fill" style="color: #ffc107;"></i>
                            <span style="color: #856404;">Productos con Stock Bajo</span>
                        </div>
                        <span class="badge bg-warning text-dark fs-6">{{ $lowStockProducts->count() }}</span>
                    </div>
                    <div>
                        @foreach($lowStockProducts as $product)
                        @php
                            $currentStock = $product->getCurrentStock(auth()->user()->restaurant_id);
                        @endphp
                        <div class="mosaic-list-item" style="border-left: 3px solid #ffc107;">
                            <div class="mosaic-list-content">
                                <div class="mosaic-list-title">{{ $product->name }}</div>
                                <small class="text-muted">{{ $product->category->name ?? 'Sin categoría' }}</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-warning text-dark">
                                    Stock: {{ $currentStock }}
                                </span>
                                <small class="text-muted">(Mín: {{ $product->stock_minimum }})</small>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
