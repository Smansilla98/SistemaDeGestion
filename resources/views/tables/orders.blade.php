@extends('layouts.app')

@section('title', 'Pedidos de Mesa: ' . $table->number)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('tables.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver a Mesas
        </a>
        <h1><i class="bi bi-receipt"></i> Pedidos de Mesa: {{ $table->number }}</h1>
        <p class="text-muted">
            Sector: {{ $table->sector->name }} | 
            Estado: <span class="badge bg-{{ $table->status === 'LIBRE' ? 'success' : ($table->status === 'OCUPADA' ? 'warning' : 'secondary') }}">{{ $table->status }}</span> |
            Capacidad: {{ $table->capacity }} personas
        </p>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Todos los Pedidos</h5>
            </div>
            <div class="card-body">
                @if($table->current_session_id === null)
                <div class="text-center py-5">
                    <i class="bi bi-info-circle" style="font-size: 3rem; color: var(--conurbania-medium);"></i>
                    <p class="text-muted mt-3 mb-0">La mesa no tiene una sesión activa.</p>
                    <p class="text-muted">Solo se muestran pedidos de la ocupación actual.</p>
                </div>
                @elseif($orders->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Mozo</th>
                                <th>Estado</th>
                                <th>Items</th>
                                <th>Subtotal</th>
                                <th>Descuento</th>
                                <th>Total</th>
                                <th>Fecha Creación</th>
                                <th>Fecha Cierre</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                            <tr>
                                <td><strong>{{ $order->number }}</strong></td>
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
                                <td>${{ number_format($order->subtotal, 2) }}</td>
                                <td>${{ number_format($order->discount, 2) }}</td>
                                <td><strong>${{ number_format($order->total, 2) }}</strong></td>
                                <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    @if($order->closed_at)
                                        {{ $order->closed_at->format('d/m/Y H:i') }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> Ver Detalle
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <th colspan="3">TOTALES</th>
                                <th>{{ $orders->sum(function($order) { return $order->items->count(); }) }}</th>
                                <th>${{ number_format($orders->sum('subtotal'), 2) }}</th>
                                <th>${{ number_format($orders->sum('discount'), 2) }}</th>
                                <th><strong>${{ number_format($orders->sum('total'), 2) }}</strong></th>
                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: var(--conurbania-medium);"></i>
                    <p class="text-muted mt-3">No hay pedidos en la sesión actual de esta mesa</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Mostrar alerta de éxito
@if(session('success'))
    Swal.fire({
        icon: 'success',
        title: 'Éxito',
        text: '{{ session('success') }}',
        confirmButtonColor: '#1e8081',
        confirmButtonText: 'Entendido'
    });
@endif

// Mostrar alerta de error
@if(session('error'))
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '{{ session('error') }}',
        confirmButtonColor: '#c94a2d',
        confirmButtonText: 'Entendido'
    });
@endif
</script>
@endpush
@endsection

