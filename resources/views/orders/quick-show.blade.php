@extends('layouts.app')

@section('title', 'Pedido Rápido: ' . $order->number)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('orders.quick.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver a Pedidos Rápidos
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
            <i class="bi bi-receipt"></i> Pedido Rápido: {{ $order->number }}
        </h1>
        <p class="text-muted">
            Cliente: <strong>{{ $order->customer_name }}</strong> | 
            Estado: <span class="badge bg-{{ 
                $order->status === 'CERRADO' ? 'success' : 
                ($order->status === 'LISTO' ? 'info' : 
                ($order->status === 'ABIERTO' ? 'secondary' : 'warning')) 
            }}">{{ $order->status }}</span> |
            Creado por: {{ $order->user->name }} | 
            Fecha: {{ $order->created_at->format('d/m/Y H:i') }}
        </p>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Items del Pedido</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio Unit.</th>
                                <th>Subtotal</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item->product->name }}</strong>
                                    @if($item->product->category)
                                        <br><small class="text-muted">{{ $item->product->category->name }}</small>
                                    @endif
                                    @if($item->modifiers->count() > 0)
                                        <br><small class="text-info">
                                            @foreach($item->modifiers as $modifier)
                                                + {{ $modifier->name }} 
                                            @endforeach
                                        </small>
                                    @endif
                                </td>
                                <td>{{ $item->quantity }}</td>
                                <td>${{ number_format($item->unit_price, 2) }}</td>
                                <td><strong>${{ number_format($item->subtotal, 2) }}</strong></td>
                                <td>
                                    @if($item->observations)
                                        <small class="text-muted">{{ $item->observations }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Subtotal:</th>
                                <th>${{ number_format($order->subtotal, 2) }}</th>
                                <th></th>
                            </tr>
                            @if($order->discount > 0)
                            <tr>
                                <th colspan="3" class="text-end">Descuento:</th>
                                <th class="text-danger">-${{ number_format($order->discount, 2) }}</th>
                                <th></th>
                            </tr>
                            @endif
                            <tr class="table-primary">
                                <th colspan="3" class="text-end">Total:</th>
                                <th class="fs-5">${{ number_format($order->total, 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($order->observations)
                <div class="mt-3">
                    <strong>Observaciones del pedido:</strong>
                    <p class="text-muted">{{ $order->observations }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Información</h5>
            </div>
            <div class="card-body">
                <p><strong>Número:</strong> {{ $order->number }}</p>
                <p><strong>Cliente:</strong> {{ $order->customer_name }}</p>
                <p><strong>Estado:</strong> 
                    <span class="badge bg-{{ 
                        $order->status === 'CERRADO' ? 'success' : 
                        ($order->status === 'LISTO' ? 'info' : 
                        ($order->status === 'ABIERTO' ? 'secondary' : 'warning')) 
                    }}">{{ $order->status }}</span>
                </p>
                <p><strong>Creado por:</strong> {{ $order->user->name }}</p>
                <p><strong>Fecha creación:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                @if($order->closed_at)
                <p><strong>Fecha cierre:</strong> {{ $order->closed_at->format('d/m/Y H:i') }}</p>
                @endif
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-cash-coin"></i> Pagos</h5>
            </div>
            <div class="card-body">
                @if($order->payments->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Método</th>
                                <th>Monto</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->payments as $payment)
                            <tr>
                                <td>
                                    <span class="badge bg-{{ 
                                        $payment->payment_method === 'EFECTIVO' ? 'success' : 
                                        ($payment->payment_method === 'DEBITO' ? 'primary' : 
                                        ($payment->payment_method === 'CREDITO' ? 'info' : 'secondary')) 
                                    }}">
                                        {{ $payment->payment_method }}
                                    </span>
                                </td>
                                <td><strong>${{ number_format($payment->amount, 2) }}</strong></td>
                                <td>{{ $payment->created_at->format('d/m H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total pagado:</th>
                                <th>${{ number_format($order->payments->sum('amount'), 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @else
                <p class="text-muted text-center">No hay pagos registrados</p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-printer"></i> Imprimir</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('orders.print.kitchen', $order) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i> Ver PDF (Cocina)
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
        </div>

        @if($order->status !== 'CERRADO')
        <div class="card mt-4">
            <div class="card-body">
                <a href="{{ route('orders.quick.close', $order) }}" class="btn btn-success w-100">
                    <i class="bi bi-cash-coin"></i> Cerrar Cuenta
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

