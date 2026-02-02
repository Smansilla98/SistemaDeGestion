@extends('layouts.app')

@section('title', 'Recibo Consolidado - Mesa ' . $table->number)

@push('styles')
<style>
    .receipt-container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        padding: 2rem;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    }

    .receipt-header {
        text-align: center;
        border-bottom: 3px solid #1e8081;
        padding-bottom: 1.5rem;
        margin-bottom: 2rem;
    }

    .receipt-header h1 {
        background: linear-gradient(135deg, #1e8081, #22565e);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .receipt-info {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 2rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, rgba(30, 128, 129, 0.1), rgba(34, 86, 94, 0.1));
        border-radius: 15px;
    }

    .receipt-info-item {
        display: flex;
        flex-direction: column;
    }

    .receipt-info-label {
        font-size: 0.875rem;
        color: #718096;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 0.25rem;
    }

    .receipt-info-value {
        font-size: 1.125rem;
        color: #1a202c;
        font-weight: 700;
    }

    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 2rem;
    }

    .items-table thead {
        background: linear-gradient(135deg, #1e8081, #22565e);
        color: white;
    }

    .items-table thead th {
        padding: 1rem;
        text-align: left;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.875rem;
        letter-spacing: 1px;
    }

    .items-table tbody td {
        padding: 1rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .items-table tbody tr:hover {
        background: rgba(30, 128, 129, 0.05);
    }

    .items-table tbody tr:last-child td {
        border-bottom: none;
    }

    .totals-section {
        background: linear-gradient(135deg, rgba(30, 128, 129, 0.1), rgba(34, 86, 94, 0.1));
        padding: 1.5rem;
        border-radius: 15px;
        margin-bottom: 2rem;
    }

    .total-line {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        font-size: 1.125rem;
    }

    .total-line.final {
        border-top: 2px solid #1e8081;
        margin-top: 0.5rem;
        padding-top: 1rem;
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e8081;
    }

    .orders-list {
        margin-top: 2rem;
        padding: 1.5rem;
        background: #f7fafc;
        border-radius: 15px;
    }

    .order-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, #1e8081, #22565e);
        color: white;
        border-radius: 20px;
        font-weight: 600;
        margin: 0.25rem;
    }
</style>
@endpush

@section('content')
<div class="receipt-container">
    <div class="receipt-header">
        <h1 style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-receipt-cutoff"></i> Recibo Consolidado</h1>
        <p class="text-muted mb-0">{{ $table->restaurant->name ?? 'Restaurante' }}</p>
        <p class="text-muted"><small>{{ now()->format('d/m/Y H:i:s') }}</small></p>
    </div>

    <div class="receipt-info">
        <div class="receipt-info-item">
            <span class="receipt-info-label">Mesa</span>
            <span class="receipt-info-value">{{ $table->number }}</span>
        </div>
        <div class="receipt-info-item">
            <span class="receipt-info-label">Sector</span>
            <span class="receipt-info-value">{{ $table->sector->name }}</span>
        </div>
        <div class="receipt-info-item">
            <span class="receipt-info-label">Pedidos</span>
            <span class="receipt-info-value">{{ $closedOrders->count() }}</span>
        </div>
        <div class="receipt-info-item">
            <span class="receipt-info-label">Total</span>
            <span class="receipt-info-value">${{ number_format($totalAmount, 2) }}</span>
        </div>
    </div>

    <h4 class="mb-3"><i class="bi bi-list-ul"></i> Items Consolidados</h4>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 10%;">Cant.</th>
                <th>Descripción</th>
                <th style="width: 15%;" class="text-end">Precio Unit.</th>
                <th style="width: 15%;" class="text-end">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($consolidatedItems as $item)
            <tr>
                <td class="text-center"><strong>{{ $item['quantity'] }}</strong></td>
                <td>
                    <strong>{{ $item['product_name'] }}</strong>
                    @if(isset($item['modifiers']) && count($item['modifiers']) > 0)
                    <div class="mt-1">
                        @foreach($item['modifiers'] as $modifier)
                        <small class="badge bg-info me-1">{{ $modifier->name }}</small>
                        @endforeach
                    </div>
                    @endif
                    @if(isset($item['observations']) && $item['observations'])
                    <div class="mt-1">
                        <small class="text-muted"><em>{{ $item['observations'] }}</em></small>
                    </div>
                    @endif
                </td>
                <td class="text-end">${{ number_format($item['unit_price'], 2) }}</td>
                <td class="text-end"><strong>${{ number_format($item['subtotal'], 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-section">
        <div class="total-line">
            <span>Subtotal:</span>
            <strong>${{ number_format($totalSubtotal, 2) }}</strong>
        </div>
        @if($totalDiscount > 0)
        <div class="total-line">
            <span>Descuento:</span>
            <strong class="text-danger">-${{ number_format($totalDiscount, 2) }}</strong>
        </div>
        @endif
        <div class="total-line final">
            <span>TOTAL:</span>
            <strong>${{ number_format($totalAmount, 2) }}</strong>
        </div>
    </div>

    @if($payments && $payments->count() > 0)
    <div class="orders-list" style="margin-top: 2rem;">
        <h5 class="mb-3"><i class="bi bi-credit-card"></i> Métodos de Pago</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Método</th>
                    <th>Monto</th>
                    <th>N° Operación</th>
                    <th>Notas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                <tr>
                    <td>
                        @if($payment->payment_method === 'EFECTIVO')
                            <span class="badge bg-success"><i class="bi bi-cash"></i> Efectivo</span>
                        @elseif($payment->payment_method === 'DEBITO')
                            <span class="badge bg-primary"><i class="bi bi-credit-card"></i> Débito</span>
                        @elseif($payment->payment_method === 'CREDITO')
                            <span class="badge bg-info"><i class="bi bi-credit-card-2-front"></i> Crédito</span>
                        @elseif($payment->payment_method === 'TRANSFERENCIA')
                            <span class="badge bg-secondary"><i class="bi bi-bank"></i> Transferencia</span>
                        @endif
                    </td>
                    <td><strong>${{ number_format($payment->amount, 2) }}</strong></td>
                    <td>{{ $payment->operation_number ?? '-' }}</td>
                    <td>{{ $payment->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th>Total Pagado:</th>
                    <th colspan="3" class="text-end">${{ number_format($payments->sum('amount'), 2) }}</th>
                </tr>
            </tfoot>
        </table>
        <p class="text-muted mt-2 mb-0">
            <small>Procesado por: {{ $payments->first()->user->name ?? 'N/A' }} - {{ $payments->first()->created_at->format('d/m/Y H:i') }}</small>
        </p>
    </div>
    @endif

    @if($closedOrders->count() > 0)
    <div class="orders-list">
        <h5 class="mb-3"><i class="bi bi-receipt"></i> Pedidos Incluidos</h5>
        <div>
            @foreach($closedOrders as $order)
            <span class="order-badge">
                {{ $order->number }}
                <small>(${{ number_format($order->total, 2) }})</small>
            </span>
            @endforeach
        </div>
        <p class="text-muted mt-3 mb-0">
            <small>Mozo: {{ $closedOrders->first()->user->name ?? 'N/A' }}</small>
        </p>
    </div>
    @endif

    <div class="text-center mt-4">
        <div class="btn-group" role="group">
            <a href="{{ route('tables.print-consolidated-receipt', $table) }}" target="_blank" class="btn btn-primary">
                <i class="bi bi-printer"></i> Imprimir Recibo (Ticket)
            </a>
            <button onclick="window.print()" class="btn btn-outline-primary">
                <i class="bi bi-printer"></i> Imprimir Vista
            </button>
            <a href="{{ route('tables.index') }}" class="btn btn-success">
                <i class="bi bi-check-circle"></i> Finalizar
            </a>
        </div>
    </div>
</div>

@push('styles')
<style>
@media print {
    .nova-sidebar,
    .nova-header,
    .btn,
    nav {
        display: none !important;
    }
    .receipt-container {
        box-shadow: none;
        padding: 1rem;
    }
    body {
        background: white;
    }
}
</style>
@endpush
@endsection

