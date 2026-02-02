@extends('layouts.app')

@section('title', 'Pedidos')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-receipt"></i> Pedidos</h1>
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
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="{{ route('orders.show', $order) }}" class="btn btn-outline-primary" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('delete', $order)
                                <form action="{{ route('orders.destroy', $order) }}" method="POST" class="d-inline" id="deleteOrderForm{{ $order->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" 
                                            class="btn btn-outline-danger" 
                                            onclick="confirmDeleteOrder({{ $order->id }}, '{{ $order->number }}')"
                                            title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
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

@push('scripts')
<script>
function confirmDeleteOrder(orderId, orderNumber) {
    Swal.fire({
        icon: 'warning',
        title: '¿Eliminar Pedido?',
        html: `
            <p>¿Estás seguro de eliminar el pedido <strong>#${orderNumber}</strong>?</p>
            <div class="alert alert-warning mt-3">
                <small><i class="bi bi-info-circle"></i> Solo se pueden eliminar pedidos en estado <strong>ABIERTO</strong> o <strong>CANCELADO</strong> sin pagos asociados.</small>
            </div>
            <p class="text-danger small mt-2"><strong>Esta acción no se puede deshacer.</strong></p>
        `,
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-trash"></i> Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Eliminando...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Enviar formulario
            document.getElementById('deleteOrderForm' + orderId).submit();
        }
    });
}
</script>
@endpush
@endsection

