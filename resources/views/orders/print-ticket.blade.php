<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ticket - {{ $order->number }}</title>
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
        }
        .header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .order-info {
            margin-bottom: 10px;
            font-size: 10px;
        }
        .order-info p {
            margin: 2px 0;
        }
        .items {
            margin: 10px 0;
        }
        .item {
            margin-bottom: 5px;
            padding-bottom: 3px;
        }
        .item-line {
            display: flex;
            justify-content: space-between;
        }
        .item-name {
            flex: 1;
        }
        .item-quantity {
            margin-right: 5px;
        }
        .item-price {
            text-align: right;
            min-width: 50px;
        }
        .totals {
            margin-top: 10px;
            border-top: 2px dashed #000;
            padding-top: 5px;
        }
        .total-line {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        .total-line.final {
            font-weight: bold;
            font-size: 13px;
            border-top: 1px dashed #000;
            padding-top: 5px;
            margin-top: 5px;
        }
        .footer {
            margin-top: 15px;
            text-align: center;
            border-top: 2px dashed #000;
            padding-top: 5px;
            font-size: 9px;
        }
        .payments {
            margin-top: 10px;
            font-size: 10px;
        }
        .payment-line {
            display: flex;
            justify-content: space-between;
            margin: 2px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $order->restaurant->name ?? 'RESTAURANTE' }}</h1>
        <p>Ticket #{{ $order->number }}</p>
    </div>

    <div class="order-info">
        <p>Mesa: {{ $order->table->number }}</p>
        <p>Fecha: {{ $order->created_at->format('d/m/Y H:i') }}</p>
    </div>

    <div class="items">
        @foreach($order->items as $item)
        <div class="item">
            <div class="item-line">
                <span class="item-quantity">{{ $item->quantity }}x</span>
                <span class="item-name">{{ $item->product->name }}</span>
                <span class="item-price">${{ number_format($item->subtotal, 2) }}</span>
            </div>
        </div>
        @endforeach
    </div>

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
            <span>TOTAL:</span>
            <span>${{ number_format($order->total, 2) }}</span>
        </div>
    </div>

    @if($order->payments->count() > 0)
    <div class="payments">
        <strong>Pagos:</strong>
        @foreach($order->payments as $payment)
        <div class="payment-line">
            <span>{{ $payment->payment_method }}:</span>
            <span>${{ number_format($payment->amount, 2) }}</span>
        </div>
        @endforeach
    </div>
    @endif

    <div class="footer">
        <p>{{ now()->format('d/m/Y H:i:s') }}</p>
        <p>Gracias por su visita</p>
    </div>
</body>
</html>

