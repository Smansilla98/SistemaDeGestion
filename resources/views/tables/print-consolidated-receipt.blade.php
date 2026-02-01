<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>RECEIPT - Mesa {{ $table->number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            width: 80mm;
            padding: 5mm;
            line-height: 1.3;
        }
        .border-asterisk {
            text-align: center;
            font-size: 10px;
            letter-spacing: 1px;
            margin: 5px 0;
        }
        .header {
            text-align: center;
            margin-bottom: 8px;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 5px;
        }
        .logo-container img {
            max-width: 60mm;
            max-height: 30mm;
            object-fit: contain;
        }
        .header h1 {
            font-size: 16px;
            font-weight: bold;
            margin: 5px 0;
            text-transform: uppercase;
        }
        .header-info {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            margin: 5px 0;
        }
        .dashed-line {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        .order-info {
            margin-bottom: 8px;
            font-size: 10px;
        }
        .order-info p {
            margin: 2px 0;
        }
        .items {
            margin: 8px 0;
        }
        .item {
            margin-bottom: 5px;
            padding-bottom: 3px;
        }
        .item-line {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
        }
        .item-quantity {
            margin-right: 5px;
            font-weight: bold;
        }
        .item-name {
            flex: 1;
        }
        .item-price {
            text-align: right;
            min-width: 50px;
            font-weight: bold;
        }
        .totals {
            margin-top: 8px;
            padding-top: 5px;
        }
        .total-line {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
            font-size: 10px;
        }
        .total-line.final {
            font-weight: bold;
            font-size: 13px;
            border-top: 1px dashed #000;
            padding-top: 5px;
            margin-top: 5px;
        }
        .payments {
            margin-top: 8px;
            padding-top: 5px;
            border-top: 1px dashed #000;
            font-size: 10px;
        }
        .payment-line {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        .payment-total {
            border-top: 1px dashed #000;
            padding-top: 5px;
            margin-top: 5px;
            font-weight: bold;
        }
        .change-line {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
            font-size: 10px;
        }
        .footer {
            margin-top: 10px;
            text-align: center;
            border-top: 1px dashed #000;
            padding-top: 5px;
            font-size: 9px;
        }
        .thank-you {
            text-align: center;
            font-weight: bold;
            margin: 5px 0;
        }
        .barcode-placeholder {
            text-align: center;
            margin-top: 10px;
            font-size: 8px;
            color: #666;
        }
        .orders-info {
            margin-top: 8px;
            padding-top: 5px;
            border-top: 1px dashed #000;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="border-asterisk">********************************</div>
    
    <div class="header">
        <div class="logo-container">
            <img src="{{ public_path('logo.png') }}" alt="Logo" onerror="this.style.display='none';">
        </div>
        <h1>RECEIPT</h1>
    </div>
    
    <div class="border-asterisk">********************************</div>
    
    <div class="header-info">
        <span>Terminal#1</span>
        <span>{{ now()->format('d-m-Y') }} {{ now()->format('h:iA') }}</span>
    </div>
    
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
        </div>
        @empty
        <div class="item">
            <div class="item-line" style="color: #999; font-style: italic;">
                <span>No hay items en este pedido</span>
            </div>
        </div>
        @endforelse
    </div>

    <div class="dashed-line"></div>

    <div class="totals">
        @php
            // Calcular subtotal desde items si no está disponible
            $calculatedSubtotal = $totalSubtotal > 0 ? $totalSubtotal : $consolidatedItems->sum('subtotal');
            $calculatedTotal = $totalAmount > 0 ? $totalAmount : ($calculatedSubtotal - $totalDiscount);
        @endphp
        <div class="total-line">
            <span>Subtotal:</span>
            <span>${{ number_format($calculatedSubtotal, 2) }}</span>
        </div>
        @if($totalDiscount > 0)
        <div class="total-line">
            <span>Descuento:</span>
            <span>-${{ number_format($totalDiscount, 2) }}</span>
        </div>
        @endif
        <div class="total-line final">
            <span>TOTAL AMOUNT:</span>
            <span>${{ number_format($calculatedTotal, 2) }}</span>
        </div>
    </div>

    @if($payments && $payments->count() > 0)
    <div class="payments">
        @php
            $calculatedTotal = $totalAmount > 0 ? $totalAmount : ($consolidatedItems->sum('subtotal') - $totalDiscount);
            $totalPaid = $payments->sum('amount');
            $change = $totalPaid - $calculatedTotal;
        @endphp
        @foreach($payments as $payment)
        <div class="payment-line">
            <span>{{ $payment->payment_method }}:</span>
            <span>${{ number_format($payment->amount, 2) }}</span>
        </div>
        @if($payment->operation_number)
        <div class="payment-line" style="font-size: 9px; color: #666;">
            <span>Approval#:</span>
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
            <span>CHANGE:</span>
            <span>${{ number_format($change, 2) }}</span>
        </div>
        @endif
    </div>
    @endif

    @if($closedOrders->count() > 0)
    <div class="orders-info">
        <p><strong>Pedidos:</strong> 
        @foreach($closedOrders as $order)
            {{ $order->number }}{{ !$loop->last ? ', ' : '' }}
        @endforeach
        </p>
    </div>
    @endif

    <div class="dashed-line"></div>

    <div class="footer">
        <div class="border-asterisk">********************************</div>
        <div class="thank-you">THANK YOU!</div>
        <div class="border-asterisk">********************************</div>
        <div class="barcode-placeholder">
            <div style="height: 40px; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center;">
                [BARCODE]
            </div>
        </div>
        <p>{{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>

