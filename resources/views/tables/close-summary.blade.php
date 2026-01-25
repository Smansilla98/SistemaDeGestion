@extends('layouts.app')

@section('title', 'Resumen de Cierre de Mesa')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('tables.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver a Mesas
        </a>
        <div class="alert alert-success">
            <h2 class="mb-0"><i class="bi bi-check-circle-fill"></i> Mesa Cerrada Exitosamente</h2>
            <p class="mb-0 mt-2">Mesa: <strong>{{ $table->number }}</strong> - Total: <strong>${{ number_format($totalAmount, 2) }}</strong></p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-receipt-cutoff"></i> Pedidos Cerrados</h4>
            </div>
            <div class="card-body">
                @forelse($closedOrders as $order)
                <div class="card mb-3 border">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="mb-1">
                                    <span class="badge bg-secondary">{{ $order->number }}</span>
                                    Pedido #{{ $order->id }}
                                </h5>
                                <p class="text-muted mb-0">
                                    <small>
                                        <i class="bi bi-clock"></i> {{ $order->created_at->format('d/m/Y H:i') }}
                                        @if($order->closed_at)
                                        - Cerrado: {{ $order->closed_at->format('H:i') }}
                                        @endif
                                    </small>
                                </p>
                                <p class="text-muted mb-0">
                                    <small><i class="bi bi-person"></i> Mozo: {{ $order->user->name }}</small>
                                </p>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0 text-primary">${{ number_format($order->total, 2) }}</h4>
                                <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-primary mt-2">
                                    <i class="bi bi-eye"></i> Ver Detalles
                                </a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 10%;">Cant.</th>
                                        <th>Producto</th>
                                        <th class="text-end" style="width: 15%;">Precio</th>
                                        <th class="text-end" style="width: 15%;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->items as $item)
                                    <tr>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td>
                                            {{ $item->product->name }}
                                            @if($item->modifiers->count() > 0)
                                            <div>
                                                @foreach($item->modifiers as $modifier)
                                                <small class="badge bg-info">{{ $modifier->name }}</small>
                                                @endforeach
                                            </div>
                                            @endif
                                            @if($item->observations)
                                            <div><small class="text-muted"><em>{{ $item->observations }}</em></small></div>
                                            @endif
                                        </td>
                                        <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-end">${{ number_format($item->subtotal, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3" class="text-end">Subtotal:</th>
                                        <th class="text-end">${{ number_format($order->subtotal, 2) }}</th>
                                    </tr>
                                    @if($order->discount > 0)
                                    <tr>
                                        <th colspan="3" class="text-end">Descuento:</th>
                                        <th class="text-end text-danger">-${{ number_format($order->discount, 2) }}</th>
                                    </tr>
                                    @endif
                                    <tr>
                                        <th colspan="3" class="text-end">Total:</th>
                                        <th class="text-end">${{ number_format($order->total, 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        @if($order->payments->count() > 0)
                        <div class="mt-2">
                            <small class="text-muted"><strong>Pagos:</strong></small>
                            @foreach($order->payments as $payment)
                            <span class="badge bg-{{ $payment->payment_method === 'EFECTIVO' ? 'success' : 'primary' }} me-1">
                                {{ $payment->payment_method }}: ${{ number_format($payment->amount, 2) }}
                            </span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
                @empty
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No hay pedidos cerrados para esta mesa.
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-calculator"></i> Resumen</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <p class="mb-1"><strong>Mesa:</strong></p>
                    <p class="mb-0">{{ $table->number }}</p>
                </div>
                <div class="mb-3">
                    <p class="mb-1"><strong>Sector:</strong></p>
                    <p class="mb-0">{{ $table->sector->name }}</p>
                </div>
                <div class="mb-3">
                    <p class="mb-1"><strong>Capacidad:</strong></p>
                    <p class="mb-0">{{ $table->capacity }} personas</p>
                </div>
                <hr>
                <div class="mb-3">
                    <p class="mb-1"><strong>Pedidos Cerrados:</strong></p>
                    <p class="mb-0"><span class="badge bg-primary">{{ $closedOrders->count() }}</span></p>
                </div>
                <div class="mb-3">
                    <p class="mb-1"><strong>Total de Items:</strong></p>
                    <p class="mb-0">{{ $closedOrders->sum(fn($order) => $order->items->sum('quantity')) }}</p>
                </div>
                <hr>
                <div class="mb-3">
                    <h4 class="mb-0">
                        <strong>Total General:</strong>
                        <span class="text-success float-end">${{ number_format($totalAmount, 2) }}</span>
                    </h4>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-printer"></i> Acciones</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    @foreach($closedOrders as $order)
                    <div class="btn-group" role="group">
                        <a href="{{ route('orders.print.invoice', $order) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-file-pdf"></i> Factura {{ $order->number }}
                        </a>
                    </div>
                    @endforeach
                    <a href="{{ route('tables.index') }}" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Finalizar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

