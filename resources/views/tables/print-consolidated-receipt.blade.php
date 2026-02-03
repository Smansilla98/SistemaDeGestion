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
            font-size: 9px;
            width: 58mm;
            padding: 2mm;
            line-height: 1.2;
            margin: 0 auto;
        }
        .border-asterisk {
            text-align: center;
            font-size: 8px;
            letter-spacing: 0.5px;
            margin: 2px 0;
        }
        .header {
            text-align: center;
            margin-bottom: 3px;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 2px;
        }
        .logo-container img {
            max-width: 40mm;
            max-height: 15mm;
            object-fit: contain;
        }
        .header h1 {
            font-size: 12px;
            font-weight: bold;
            margin: 2px 0;
            text-transform: uppercase;
        }
        .header-info {
            display: flex;
            justify-content: space-between;
            font-size: 7px;
            margin: 2px 0;
        }
        .dashed-line {
            border-top: 1px dashed #000;
            margin: 2px 0;
        }
        .order-info {
            margin-bottom: 3px;
            font-size: 8px;
        }
        .order-info p {
            margin: 1px 0;
            line-height: 1.1;
        }
        .items {
            margin: 3px 0;
        }
        .item {
            margin-bottom: 2px;
            padding-bottom: 1px;
        }
        .item-line {
            display: flex;
            justify-content: space-between;
            font-size: 8px;
            line-height: 1.1;
        }
        .item-quantity {
            margin-right: 3px;
            font-weight: bold;
            min-width: 15px;
        }
        .item-name {
            flex: 1;
            word-break: break-word;
        }
        .item-price {
            text-align: right;
            min-width: 35px;
            font-weight: bold;
        }
        .totals {
            margin-top: 3px;
            padding-top: 2px;
        }
        .total-line {
            display: flex;
            justify-content: space-between;
            margin: 1px 0;
            font-size: 8px;
        }
        .total-line.final {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px dashed #000;
            padding-top: 2px;
            margin-top: 2px;
        }
        .payments {
            margin-top: 3px;
            padding-top: 2px;
            border-top: 1px dashed #000;
            font-size: 8px;
        }
        .payment-line {
            display: flex;
            justify-content: space-between;
            margin: 1px 0;
            font-size: 8px;
        }
        .payment-total {
            border-top: 1px dashed #000;
            padding-top: 2px;
            margin-top: 2px;
            font-weight: bold;
        }
        .change-line {
            display: flex;
            justify-content: space-between;
            margin: 1px 0;
            font-size: 8px;
        }
        .footer {
            margin-top: 4px;
            text-align: center;
            border-top: 1px dashed #000;
            padding-top: 2px;
            font-size: 7px;
        }
        .thank-you {
            text-align: center;
            font-weight: bold;
            margin: 2px 0;
            font-size: 9px;
        }
        .barcode-placeholder {
            text-align: center;
            margin-top: 3px;
            font-size: 7px;
            color: #666;
        }
        .orders-info {
            margin-top: 3px;
            padding-top: 2px;
            border-top: 1px dashed #000;
            font-size: 7px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="border-asterisk">************************</div>
    
    <div class="header">
        <div class="logo-container">
            <img src="{{ public_path('logo.png') }}" alt="Logo" onerror="this.style.display='none';">
        </div>
        <h1>RECIBO</h1>
    </div>
    
    <div class="border-asterisk">************************</div>
    
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
            @if(isset($item['unit_price']) && ($item['quantity'] ?? 0) > 1)
            <div class="item-line" style="font-size: 7px; color: #666; margin-left: 15px;">
                <span>@ ${{ number_format($item['unit_price'], 2) }} c/u</span>
            </div>
            @endif
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
            // SIEMPRE calcular desde items consolidados para asegurar precisión
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
            // Usar el total calculado desde items consolidados
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
        <div class="payment-line" style="font-size: 7px; color: #666;">
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
        <div class="border-asterisk">************************</div>
        <div class="thank-you">¡MUCHAS GRACIAS!</div>
        <div class="border-asterisk">************************</div>
        <div class="barcode-placeholder">
            <div style="height: 25px; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 6px;">
                [BARCODE]
            </div>
        </div>
        <p>{{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>

