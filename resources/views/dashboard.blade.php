@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1><i class="bi bi-speedometer2"></i> Dashboard</h1>
        <p class="text-muted">Resumen general del restaurante</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2">Mesas Ocupadas</h6>
                        <h2 class="card-title mb-0">{{ $stats['mesas_ocupadas'] }}</h2>
                    </div>
                    <i class="bi bi-table" style="font-size: 3rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2">Mesas Libres</h6>
                        <h2 class="card-title mb-0">{{ $stats['mesas_libres'] }}</h2>
                    </div>
                    <i class="bi bi-check-circle" style="font-size: 3rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2">Pedidos Pendientes</h6>
                        <h2 class="card-title mb-0">{{ $stats['pedidos_pendientes'] }}</h2>
                    </div>
                    <i class="bi bi-clock-history" style="font-size: 3rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-subtitle mb-2">Ventas de Hoy</h6>
                        <h2 class="card-title mb-0">${{ number_format($stats['ventas_hoy'], 2) }}</h2>
                    </div>
                    <i class="bi bi-currency-dollar" style="font-size: 3rem; opacity: 0.5;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-receipt"></i> Pedidos Recientes</h5>
            </div>
            <div class="card-body">
                @if($recentOrders->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
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
                                        <a href="{{ route('orders.show', $order) }}">{{ $order->number }}</a>
                                    </td>
                                    <td>{{ $order->table->number }}</td>
                                    <td>{{ $order->user->name }}</td>
                                    <td>
                                        <span class="badge bg-{{ $order->status === 'CERRADO' ? 'success' : ($order->status === 'LISTO' ? 'info' : 'warning') }}">
                                            {{ $order->status }}
                                        </span>
                                    </td>
                                    <td>${{ number_format($order->total, 2) }}</td>
                                    <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="{{ route('orders.index') }}" class="btn btn-primary">Ver todos los pedidos</a>
                    </div>
                @else
                    <p class="text-muted text-center">No hay pedidos recientes</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-trophy"></i> Productos Más Vendidos Hoy</h5>
            </div>
            <div class="card-body">
                @if($topProducts->count() > 0)
                    <ol class="list-group list-group-numbered">
                        @foreach($topProducts as $product)
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold">{{ $product->name }}</div>
                            </div>
                            <span class="badge bg-primary rounded-pill">{{ $product->total_quantity }}</span>
                        </li>
                        @endforeach
                    </ol>
                @else
                    <p class="text-muted text-center">No hay datos disponibles</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

