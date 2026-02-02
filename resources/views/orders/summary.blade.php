@extends('layouts.app')

@section('title', 'Resumen del Pedido')

@section('content')
<div class="row mb-4">
    <div class="col-12 text-center">
        <div class="alert alert-success">
            <h2 class="mb-0"><i class="bi bi-check-circle-fill"></i> Pedido Cerrado</h2>
            <p class="mb-0 mt-2">Gracias por su visita. Aquí está el resumen de su pedido.</p>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-8">
        <div class="card shadow-lg border-0">
            <div class="card-header bg-primary text-white text-center py-4">
                <h1 class="mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-receipt-cutoff"></i> Resumen del Pedido</h1>
                <h3 class="mb-0">{{ $order->restaurant->name ?? 'Restaurante' }}</h3>
                @if($order->restaurant->address)
                <p class="mb-0 mt-2"><small>{{ $order->restaurant->address }}</small></p>
                @endif
            </div>
            <div class="card-body p-4">
                <!-- Información del Pedido -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="border rounded p-3 mb-3">
                            <h5 class="text-primary mb-3"><i class="bi bi-info-circle"></i> Información del Pedido</h5>
                            <p class="mb-2"><strong>Número de Pedido:</strong> <span class="badge bg-secondary">{{ $order->number }}</span></p>
                            <p class="mb-2"><strong>Mesa:</strong> {{ $order->table->number }}</p>
                            <p class="mb-2"><strong>Mozo:</strong> {{ $order->user->name }}</p>
                            <p class="mb-0"><strong>Fecha:</strong> {{ $order->created_at->format('d/m/Y') }}</p>
                            <p class="mb-0"><strong>Hora:</strong> {{ $order->created_at->format('H:i') }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 mb-3">
                            <h5 class="text-primary mb-3"><i class="bi bi-clock-history"></i> Tiempos</h5>
                            <p class="mb-2"><strong>Inicio:</strong> {{ $order->created_at->format('H:i') }}</p>
                            @if($order->sent_at)
                            <p class="mb-2"><strong>Enviado a Cocina:</strong> {{ $order->sent_at->format('H:i') }}</p>
                            @endif
                            @if($order->closed_at)
                            <p class="mb-0"><strong>Cerrado:</strong> {{ $order->closed_at->format('H:i') }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Items del Pedido -->
                <div class="mb-4">
                    <h4 class="text-primary mb-3"><i class="bi bi-list-ul"></i> Items Consumidos</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 10%;">Cant.</th>
                                    <th>Descripción</th>
                                    <th class="text-end" style="width: 15%;">Precio Unit.</th>
                                    <th class="text-end" style="width: 15%;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                <tr>
                                    <td class="text-center"><strong>{{ $item->quantity }}</strong></td>
                                    <td>
                                        <strong>{{ $item->product->name }}</strong>
                                        @if($item->modifiers->count() > 0)
                                        <div class="mt-1">
                                            @foreach($item->modifiers as $modifier)
                                            <small class="badge bg-info me-1">
                                                {{ $modifier->name }}
                                                @if($modifier->price_modifier != 0)
                                                    ({{ $modifier->price_modifier > 0 ? '+' : '' }}${{ number_format($modifier->price_modifier, 2) }})
                                                @endif
                                            </small>
                                            @endforeach
                                        </div>
                                        @endif
                                        @if($item->observations)
                                        <div class="mt-1">
                                            <small class="text-muted"><em>{{ $item->observations }}</em></small>
                                        </div>
                                        @endif
                                    </td>
                                    <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-end"><strong>${{ number_format($item->subtotal, 2) }}</strong></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($order->observations)
                <div class="alert alert-info mb-4">
                    <h6 class="mb-2"><i class="bi bi-chat-left-text"></i> Observaciones del Pedido</h6>
                    <p class="mb-0">{{ $order->observations }}</p>
                </div>
                @endif

                <!-- Totales -->
                <div class="row">
                    <div class="col-md-6 offset-md-6">
                        <div class="border rounded p-3 bg-light">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <strong>${{ number_format($order->subtotal, 2) }}</strong>
                            </div>
                            @if($order->discount > 0)
                            <div class="d-flex justify-content-between mb-2 text-danger">
                                <span>Descuento:</span>
                                <strong>-${{ number_format($order->discount, 2) }}</strong>
                            </div>
                            @endif
                            <hr>
                            <div class="d-flex justify-content-between">
                                <h4 class="mb-0">TOTAL:</h4>
                                <h4 class="mb-0 text-primary">${{ number_format($order->total, 2) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagos -->
                @if($order->payments->count() > 0)
                <div class="mt-4">
                    <h4 class="text-primary mb-3"><i class="bi bi-credit-card"></i> Pagos Realizados</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Método de Pago</th>
                                    <th class="text-end">Monto</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->payments as $payment)
                                <tr>
                                    <td>
                                        <span class="badge bg-{{ $payment->payment_method === 'EFECTIVO' ? 'success' : ($payment->payment_method === 'DEBITO' ? 'primary' : ($payment->payment_method === 'CREDITO' ? 'info' : 'warning')) }}">
                                            {{ $payment->payment_method }}
                                        </span>
                                    </td>
                                    <td class="text-end"><strong>${{ number_format($payment->amount, 2) }}</strong></td>
                                    <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th>Total Pagado:</th>
                                    <th class="text-end">${{ number_format($order->payments->sum('amount'), 2) }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Acciones -->
                <div class="mt-4 text-center">
                    <div class="btn-group" role="group">
                        <a href="{{ route('orders.print.invoice', $order) }}" target="_blank" class="btn btn-primary">
                            <i class="bi bi-file-pdf"></i> Ver Factura PDF
                        </a>
                        <a href="{{ route('orders.print.ticket', $order) }}" target="_blank" class="btn btn-outline-primary">
                            <i class="bi bi-printer"></i> Imprimir Ticket
                        </a>
                        <button onclick="window.print()" class="btn btn-outline-secondary">
                            <i class="bi bi-printer"></i> Imprimir Resumen
                        </button>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('tables.index') }}" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Finalizar
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light text-center py-3">
                <p class="mb-0 text-muted">
                    <small>Resumen generado el {{ now()->format('d/m/Y H:i:s') }}</small>
                </p>
                <p class="mb-0 mt-2">
                    <strong>¡Gracias por su visita!</strong>
                </p>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
@media print {
    .btn, .alert, nav, .card-footer {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .card-header {
        background-color: #000 !important;
        color: #fff !important;
    }
}
</style>
@endpush
@endsection

