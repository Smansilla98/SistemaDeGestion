<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Comanda - {{ $order->number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            padding: 10mm;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h1 {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .order-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .info-column {
            flex: 1;
        }
        .info-column p {
            margin: 3px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th {
            background-color: #f0f0f0;
            padding: 8px;
            text-align: left;
            border-bottom: 2px solid #000;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #ddd;
        }
        .modifiers {
            font-size: 10px;
            color: #666;
            margin-left: 15px;
        }
        .observations {
            font-style: italic;
            color: #666;
        }
        .total {
            text-align: right;
            font-weight: bold;
            font-size: 14px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #000;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>COMANDA</h1>
        <p>Pedido #{{ $order->number }}</p>
    </div>

    <div class="order-info">
        <div class="info-column">
            @if($order->table)
                <p><strong>Mesa:</strong> {{ $order->table->number }}</p>
            @elseif($order->customer_name)
                <p><strong>Consumidor:</strong> {{ $order->customer_name }}</p>
            @endif
            <p><strong>Mozo:</strong> {{ $order->user->name }}</p>
        </div>
        <div class="info-column">
            <p><strong>Fecha:</strong> {{ $order->created_at->format('d/m/Y') }}</p>
            <p><strong>Hora:</strong> {{ $order->created_at->format('H:i') }}</p>
        </div>
    </div>

    @if($order->observations)
    <div style="margin-bottom: 15px; padding: 8px; background-color: #fff3cd; border-left: 4px solid #ffc107;">
        <strong>Observaciones Generales:</strong> {{ $order->observations }}
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Cant.</th>
                <th>Producto</th>
                <th>Precio</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groupedItems as $item)
            <tr>
                <td>{{ $item['quantity'] }}</td>
                <td>
                    <strong>{{ $item['product']->name }}</strong>
                    @if(isset($item['modifiers']) && $item['modifiers']->count() > 0)
                    <div class="modifiers">
                        @foreach($item['modifiers'] as $modifier)
                        • {{ $modifier->name }}<br>
                        @endforeach
                    </div>
                    @endif
                    @if(!empty($item['observations']))
                    <div class="observations">Nota: {{ $item['observations'] }}</div>
                    @endif
                </td>
                <td>${{ number_format($item['unit_price'], 2) }}</td>
                <td>${{ number_format($item['subtotal'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        <p>Subtotal: ${{ number_format($order->subtotal, 2) }}</p>
        @if($order->discount > 0)
        <p>Descuento: -${{ number_format($order->discount, 2) }}</p>
        @endif
        <p style="font-size: 16px;">TOTAL: ${{ number_format($order->total, 2) }}</p>
    </div>

    <div class="footer">
        <p>Impreso el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>

