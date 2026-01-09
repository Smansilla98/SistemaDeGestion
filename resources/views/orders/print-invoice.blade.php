<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Factura - {{ $order->number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            padding: 15mm;
        }
        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header-left h1 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .header-right {
            text-align: right;
        }
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .info-box {
            flex: 1;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
        .info-box h3 {
            font-size: 14px;
            margin-bottom: 8px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .info-box p {
            margin: 3px 0;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #333;
            color: white;
            padding: 10px;
            text-align: left;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            margin-left: auto;
            width: 300px;
            margin-top: 20px;
        }
        .totals table {
            margin: 0;
        }
        .totals td {
            padding: 5px 10px;
        }
        .totals .total-row {
            font-weight: bold;
            font-size: 16px;
            border-top: 2px solid #000;
        }
        .payments {
            margin-top: 30px;
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
        .payments h3 {
            margin-bottom: 10px;
        }
        .payment-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h1>FACTURA</h1>
            <p>Pedido #{{ $order->number }}</p>
        </div>
        <div class="header-right">
            <p><strong>Fecha:</strong> {{ $order->created_at->format('d/m/Y') }}</p>
            <p><strong>Hora:</strong> {{ $order->created_at->format('H:i') }}</p>
        </div>
    </div>

    <div class="invoice-info">
        <div class="info-box">
            <h3>Información del Pedido</h3>
            <p><strong>Mesa:</strong> {{ $order->table->number }}</p>
            <p><strong>Mozo:</strong> {{ $order->user->name }}</p>
            @if($order->observations)
            <p><strong>Observaciones:</strong> {{ $order->observations }}</p>
            @endif
        </div>
        <div class="info-box">
            <h3>Restaurante</h3>
            <p>{{ $order->restaurant->name ?? 'Restaurante' }}</p>
            <p>{{ $order->restaurant->address ?? '' }}</p>
            <p>{{ $order->restaurant->phone ?? '' }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Cant.</th>
                <th>Descripción</th>
                <th class="text-right">Precio Unit.</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->quantity }}</td>
                <td>
                    <strong>{{ $item->product->name }}</strong>
                    @if($item->modifiers->count() > 0)
                    <div style="font-size: 10px; color: #666; margin-left: 10px;">
                        @foreach($item->modifiers as $modifier)
                        • {{ $modifier->name }}<br>
                        @endforeach
                    </div>
                    @endif
                    @if($item->observations)
                    <div style="font-size: 10px; font-style: italic; color: #666; margin-left: 10px;">
                        Nota: {{ $item->observations }}
                    </div>
                    @endif
                </td>
                <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right">${{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">${{ number_format($order->subtotal, 2) }}</td>
            </tr>
            @if($order->discount > 0)
            <tr>
                <td>Descuento:</td>
                <td class="text-right">-${{ number_format($order->discount, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>TOTAL:</td>
                <td class="text-right">${{ number_format($order->total, 2) }}</td>
            </tr>
        </table>
    </div>

    @if($order->payments->count() > 0)
    <div class="payments">
        <h3>Pagos Realizados</h3>
        @foreach($order->payments as $payment)
        <div class="payment-item">
            <span>{{ $payment->payment_method }}:</span>
            <strong>${{ number_format($payment->amount, 2) }}</strong>
        </div>
        @endforeach
        <div class="payment-item" style="margin-top: 10px; padding-top: 10px; border-top: 2px solid #000;">
            <span><strong>Total Pagado:</strong></span>
            <strong>${{ number_format($order->payments->sum('amount'), 2) }}</strong>
        </div>
    </div>
    @endif

    <div class="footer">
        <p>Factura generada el {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>Gracias por su visita</p>
    </div>
</body>
</html>

