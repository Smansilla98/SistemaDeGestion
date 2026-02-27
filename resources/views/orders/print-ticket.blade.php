<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket - {{ $order->number }}</title>
    @include('partials.print-ticket-styles')
</head>
<body>
    <div class="ticket">
        <div class="border-asterisk">********************************</div>
        <div class="header">
            <div class="logo-container">
                <img src="{{ public_path('logo.png') }}" alt="Logo" onerror="this.style.display='none';">
            </div>
            <h1>Detalle del Pedido</h1>
        </div>
        <div class="border-asterisk">********************************</div>
        <div class="dashed-line"></div>
        <div class="order-info">
            <p><strong>Ticket:</strong> {{ $order->number }}</p>
            @if($order->table)
                <p><strong>Mesa:</strong> {{ $order->table->number }}</p>
            @elseif($order->customer_name)
                <p><strong>Consumidor:</strong> {{ $order->customer_name }}</p>
            @endif
            @if($order->user)
                <p><strong>Mozo:</strong> {{ $order->user->name }}</p>
            @endif
        </div>
        <div class="dashed-line"></div>
        <div class="items">
            @foreach($groupedItems as $item)
            <div class="item">
                <div class="item-line">
                    <span class="item-quantity">{{ $item['quantity'] }} x</span>
                    <span class="item-name">{{ $item['product']->name }}</span>
                    <span class="item-price">${{ number_format($item['subtotal'], 2) }}</span>
                </div>
            </div>
            @endforeach
        </div>
        <div class="dashed-line"></div>
        <div class="totals">
            <div class="total-line">
                <span>Subtotal:</span>
                <span>${{ number_format($order->subtotal, 2) }}</span>
            </div>
            @if($order->discount > 0)
            <div class="total-line">
                <span>Descuento:</span>
                <span>-${{ number_format($order->discount, 2) }}</span>
            </div>
            @endif
            <div class="total-line final">
                <span>TOTAL A PAGAR:</span>
                <span>${{ number_format($order->total, 2) }}</span>
            </div>
        </div>
        @if($order->payments && $order->payments->count() > 0)
        <div class="payments">
            @php
                $totalPaid = $order->payments->sum('amount');
                $change = $totalPaid - $order->total;
            @endphp
            @foreach($order->payments as $payment)
            <div class="payment-line">
                <span>{{ $payment->payment_method }}:</span>
                <span>${{ number_format($payment->amount, 2) }}</span>
            </div>
            @if($payment->operation_number)
            <div class="payment-line" style="font-size: 11px; color: #666;">
                <span>Nº op:</span>
                <span>{{ $payment->operation_number }}</span>
            </div>
            @endif
            @endforeach
            <div class="payment-total">
                <div class="payment-line">
                    <span>Total Pagado:</span>
                    <span>${{ number_format($totalPaid, 2) }}</span>
                </div>
            </div>
            @if($change > 0)
            <div class="change-line">
                <span>Vuelto:</span>
                <span>${{ number_format($change, 2) }}</span>
            </div>
            @endif
        </div>
        @endif
        <div class="dashed-line"></div>
        <div class="footer">
            <div class="border-asterisk">********************************</div>
            <div class="thank-you">¡MUCHAS GRACIAS!</div>
            <div class="border-asterisk">********************************</div>
            <p>{{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
