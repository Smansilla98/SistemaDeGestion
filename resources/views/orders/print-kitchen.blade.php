<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ticket Cocina - {{ $order->number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
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
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .order-info {
            margin-bottom: 10px;
        }
        .order-info p {
            margin: 2px 0;
        }
        .items {
            margin: 10px 0;
        }
        .item {
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 1px dashed #ccc;
        }
        .item-header {
            font-weight: bold;
            font-size: 13px;
        }
        .item-details {
            margin-left: 10px;
            font-size: 11px;
        }
        .item-observations {
            margin-left: 10px;
            font-style: italic;
            color: #666;
        }
        .footer {
            margin-top: 15px;
            text-align: center;
            border-top: 2px dashed #000;
            padding-top: 5px;
            font-size: 10px;
        }
        .timestamp {
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>COCINA</h1>
        <p>Pedido #{{ $order->number }}</p>
    </div>

    <div class="order-info">
        @if($order->table)
            <p><strong>Mesa:</strong> {{ $order->table->number }}</p>
        @elseif($order->customer_name)
            <p><strong>Consumidor:</strong> {{ $order->customer_name }}</p>
        @endif
        <p><strong>Mozo:</strong> {{ $order->user->name }}</p>
        <p class="timestamp"><strong>Hora:</strong> {{ $order->created_at->format('H:i') }}</p>
        @if($order->observations)
        <p><strong>Observaciones:</strong> {{ $order->observations }}</p>
        @endif
    </div>

    <div class="items">
        @foreach($groupedItems as $item)
        <div class="item">
            <div class="item-header">
                {{ $item['quantity'] }}x {{ $item['product']->name }}
            </div>
            @if(isset($item['modifiers']) && $item['modifiers']->count() > 0)
            <div class="item-details">
                @foreach($item['modifiers'] as $modifier)
                - {{ $modifier->name }}<br>
                @endforeach
            </div>
            @endif
            @if(!empty($item['observations']))
            <div class="item-observations">
                Nota: {{ $item['observations'] }}
            </div>
            @endif
        </div>
        @endforeach
    </div>

    <div class="footer">
        <p>{{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>

