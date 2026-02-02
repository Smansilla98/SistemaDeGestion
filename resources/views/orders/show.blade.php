@extends('layouts.app')

@section('title', 'Pedido #' . $order->number)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('orders.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-receipt"></i> Pedido: {{ $order->number }}</h1>
        <p class="text-muted">
            Mesa: {{ $order->table->number }} | 
            Mozo: {{ $order->user->name }} | 
            Estado: <span class="badge bg-{{ $order->status === 'CERRADO' ? 'success' : 'warning' }}">{{ $order->status }}</span>
        </p>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Items del Pedido</h5>
                @if($order->status === 'ABIERTO')
                <a href="{{ route('orders.create', ['tableId' => $order->table_id]) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus"></i> Agregar Item
                </a>
                @endif
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->product->name }}</strong>
                                @if($item->observations)
                                <br><small class="text-muted">{{ $item->observations }}</small>
                                @endif
                            </td>
                            <td>{{ $item->quantity }}</td>
                            <td>${{ number_format($item->unit_price, 2) }}</td>
                            <td>${{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3">Subtotal</th>
                            <th>${{ number_format($order->subtotal, 2) }}</th>
                        </tr>
                        @if($order->discount > 0)
                        <tr>
                            <th colspan="3">Descuento</th>
                            <th>-${{ number_format($order->discount, 2) }}</th>
                        </tr>
                        @endif
                        <tr>
                            <th colspan="3">Total</th>
                            <th>${{ number_format($order->total, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if($order->observations)
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Observaciones</h5>
            </div>
            <div class="card-body">
                <p>{{ $order->observations }}</p>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Acciones</h5>
            </div>
            <div class="card-body">
                @if(in_array(auth()->user()->role, ['ADMIN', 'MOZO']))
                    @if($order->status === 'ABIERTO')
                        <form action="{{ route('orders.update-status', $order) }}" method="POST" class="mb-2">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="EN_PREPARACION">
                            <button type="submit" class="btn btn-warning w-100" onclick="return confirm('¿Marcar pedido como EN PREPARACIÓN?')">
                                <i class="bi bi-gear"></i> Marcar en Preparación
                            </button>
                        </form>
                    @elseif($order->status === 'EN_PREPARACION')
                        <form action="{{ route('orders.update-status', $order) }}" method="POST" class="mb-2">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="ENTREGADO">
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('¿Marcar pedido como ENTREGADO?')">
                                <i class="bi bi-check-circle"></i> Marcar como Entregado
                            </button>
                        </form>
                    @endif
                @endif

                @if($order->status === 'ENTREGADO' || $order->status === 'CERRADO')
                @can('update', $order)
                <form action="{{ route('orders.close', $order) }}" method="POST" class="mb-2">
                    @csrf
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check-circle"></i> Cerrar Pedido
                    </button>
                </form>
                @endcan
                @endif

                <div class="mt-3">
                    <h6>Imprimir:</h6>
                    <div class="d-grid gap-2">
                        <div class="btn-group" role="group">
                            <a href="{{ route('orders.print.kitchen', $order) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> Ver PDF
                            </a>
                            <a href="{{ route('orders.print.kitchen', ['order' => $order, 'print' => 'true']) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-printer"></i> Imprimir
                            </a>
                        </div>
                        <a href="{{ route('orders.print.comanda', $order) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-printer"></i> Comanda
                        </a>
                        @if($order->status === 'CERRADO')
                        <a href="{{ route('orders.print.invoice', $order) }}" target="_blank" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-printer"></i> Factura
                        </a>
                        <a href="{{ route('orders.print.ticket', $order) }}" target="_blank" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-printer"></i> Ticket Simple
                        </a>
                        @endif
                    </div>
                </div>

                @if($order->status === 'CERRADO' && $order->payments->count() > 0)
                <div class="mt-3">
                    <h6>Pagos:</h6>
                    @foreach($order->payments as $payment)
                    <div class="small mb-2">
                        <strong>{{ $payment->payment_method }}:</strong> 
                        ${{ number_format($payment->amount, 2) }}
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Información</h5>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    <strong>Fecha:</strong><br>
                    {{ $order->created_at->format('d/m/Y H:i') }}
                </p>
                @if($order->sent_at)
                <p class="mb-2">
                    <strong>Enviado:</strong><br>
                    {{ $order->sent_at->format('d/m/Y H:i') }}
                </p>
                @endif
                @if($order->closed_at)
                <p class="mb-2">
                    <strong>Cerrado:</strong><br>
                    {{ $order->closed_at->format('d/m/Y H:i') }}
                </p>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Mostrar alerta de error si hay un error de stock
@if(session('error'))
    @if(str_contains(session('error'), 'Stock insuficiente'))
        Swal.fire({
            icon: 'error',
            title: 'Stock Insuficiente',
            text: '{{ session('error') }}',
            confirmButtonColor: '#c94a2d',
            confirmButtonText: 'Entendido'
        });
    @else
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '{{ session('error') }}',
            confirmButtonColor: '#c94a2d',
            confirmButtonText: 'Entendido'
        });
    @endif
@endif

// Mostrar alerta de éxito
@if(session('success'))
    @if(session('order_delivered'))
        // MÓDULO 2: Alerta flotante especial para pedidos entregados
        Swal.fire({
            icon: 'success',
            title: '✅ Pedido Entregado',
            html: `Pedido #{{ session('order_delivered.order_number') }} entregado en Mesa {{ session('order_delivered.table_number') }}`,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
    @else
        Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: '{{ session('success') }}',
            confirmButtonColor: '#1e8081',
            confirmButtonText: 'Entendido'
        });
    @endif
@endif
</script>
@endpush
@endsection

