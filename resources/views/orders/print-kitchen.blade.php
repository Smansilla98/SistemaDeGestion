<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket Cocina - {{ $order->number }}</title>
    @include('partials.print-ticket-styles')
</head>
<body>
    <div class="ticket">
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
                <p><strong>Obs:</strong> {{ $order->observations }}</p>
            @endif
        </div>
        <div class="dashed-line"></div>
        <div class="items">
            @foreach($groupedItems as $item)
            <div class="item">
                <div class="item-line">
                    <span class="item-quantity">{{ $item['quantity'] }}x</span>
                    <span class="item-name">{{ $item['product']->name }}</span>
                </div>
                @if(isset($item['modifiers']) && $item['modifiers']->count() > 0)
                <div class="item-details">
                    @foreach($item['modifiers'] as $modifier)
                    + {{ $modifier->name }}
                    @endforeach
                </div>
                @endif
                @if(!empty($item['observations']))
                <div class="item-observations">Nota: {{ $item['observations'] }}</div>
                @endif
            </div>
            @endforeach
        </div>
        <div class="dashed-line"></div>
        <div class="footer">
            <p>{{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
