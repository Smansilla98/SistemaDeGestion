@extends('layouts.app')

@section('title', 'Pedidos')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-receipt"></i> Pedidos</h1>
            <p class="text-muted">Gestión de pedidos del restaurante</p>
        </div>
        @can('create', App\Models\Order::class)
        <a href="{{ route('orders.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo Pedido
        </a>
        @endcan
    </div>
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" action="{{ route('orders.index') }}" class="row g-3">
            <div class="col-md-3">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">Todos los estados</option>
                    <option value="ABIERTO" {{ request('status') === 'ABIERTO' ? 'selected' : '' }}>Abierto</option>
                    <option value="ENVIADO" {{ request('status') === 'ENVIADO' ? 'selected' : '' }}>Enviado</option>
                    <option value="EN_PREPARACION" {{ request('status') === 'EN_PREPARACION' ? 'selected' : '' }}>En Preparación</option>
                    <option value="LISTO" {{ request('status') === 'LISTO' ? 'selected' : '' }}>Listo</option>
                    <option value="CERRADO" {{ request('status') === 'CERRADO' ? 'selected' : '' }}>Cerrado</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" name="table_id" class="form-control" placeholder="ID Mesa" value="{{ request('table_id') }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ route('orders.index') }}" class="btn btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Mesa</th>
                        <th>Mozo</th>
                        <th>Estado</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td><strong>{{ $order->number }}</strong></td>
                        <td>{{ $order->table->number }}</td>
                        <td>{{ $order->user->name }}</td>
                        <td>
                            <span class="badge bg-{{ 
                                $order->status === 'CERRADO' ? 'success' : 
                                ($order->status === 'LISTO' ? 'info' : 
                                ($order->status === 'ABIERTO' ? 'secondary' : 'warning')) 
                            }}">
                                {{ $order->status }}
                            </span>
                        </td>
                        <td>{{ $order->items->count() }}</td>
                        <td><strong>${{ number_format($order->total, 2) }}</strong></td>
                        <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-eye"></i> Ver
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No hay pedidos</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection

