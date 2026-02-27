<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo Mesa {{ $table->number }}</title>
    @include('partials.print-ticket-styles')
</head>
<body>
    <div class="ticket">
        <div class="border-asterisk">********************************</div>
        <div class="header">
            <div class="logo-container">
                <img src="{{ public_path('logo.png') }}" alt="Logo" onerror="this.style.display='none';">
            </div>
            <h1>Detalle de la Mesa</h1>
        </div>
        <div class="border-asterisk">********************************</div>
        <div class="dashed-line"></div>
        <div class="order-info">
            <p><strong>Mesa:</strong> {{ $table->number }}</p>
            <p><strong>Sector:</strong> {{ $table->sector->name ?? 'N/A' }}</p>
            @if($closedOrders->count() > 0 && $closedOrders->first()->user)
                <p><strong>Mozo:</strong> {{ $closedOrders->first()->user->name }}</p>
            @endif
            @if($closedOrders->count() > 0)
                <p><strong>Pedidos:</strong> {{ $closedOrders->count() }}</p>
            @endif
        </div>
        <div class="dashed-line"></div>
        <div class="items">
            @forelse($consolidatedItems as $item)
            <div class="item">
                <div class="item-line">
                    <span class="item-quantity">{{ $item['quantity'] ?? 0 }} x</span>
                    <span class="item-name">{{ $item['product_name'] ?? 'Producto' }}</span>
                    <span class="item-price">${{ number_format($item['subtotal'] ?? 0, 2) }}</span>
                </div>
                @if(isset($item['unit_price']) && ($item['quantity'] ?? 0) > 1)
                <div class="item-line item-details" style="margin-left: 26px;">
                    <span>@ ${{ number_format($item['unit_price'], 2) }} c/u</span>
                </div>
                @endif
            </div>
            @empty
            <div class="item">
                <div class="item-line" style="color: #999; font-style: italic;">
                    <span>No hay items</span>
                </div>
            </div>
            @endforelse
        </div>
        <div class="dashed-line"></div>
        <div class="totals">
            @php
                $calculatedSubtotal = $consolidatedItems->sum('subtotal');
                $calculatedDiscount = $totalDiscount ?? 0;
                $calculatedTotal = $calculatedSubtotal - $calculatedDiscount;
            @endphp
            <div class="total-line">
                <span>Subtotal:</span>
                <span>${{ number_format($calculatedSubtotal, 2) }}</span>
            </div>
            @if($calculatedDiscount > 0)
            <div class="total-line">
                <span>Descuento:</span>
                <span>-${{ number_format($calculatedDiscount, 2) }}</span>
            </div>
            @endif
            <div class="total-line final">
                <span>TOTAL:</span>
                <span>${{ number_format($calculatedTotal, 2) }}</span>
            </div>
        </div>
        @if($payments && $payments->count() > 0)
        <div class="payments">
            @php
                $calculatedSubtotal = $consolidatedItems->sum('subtotal');
                $calculatedDiscount = $totalDiscount ?? 0;
                $calculatedTotal = $calculatedSubtotal - $calculatedDiscount;
                $totalPaid = $payments->sum('amount');
                $change = $totalPaid - $calculatedTotal;
            @endphp
            @foreach($payments as $payment)
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
