<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comanda - {{ $order->number }}</title>
    @include('partials.print-ticket-styles')
</head>
<body>
    <div class="ticket">
        <div class="header">
            <h1>COMANDA</h1>
            <p>Pedido #{{ $order->number }}</p>
        </div>
        <div class="order-info">
            @if($order->table)
                <p><strong>Mesa:</strong> {{ $order->table->number }}</p>
            @elseif($order->customer_name)
                <p><strong>Consumidor:</strong> {{ $order->customer_name }}</p>
            @endif
            <p><strong>Mozo:</strong> {{ $order->user->name }}</p>
            <p><strong>Fecha:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
        </div>
        @if($order->observations)
        <div class="order-info" style="padding: 2px 0; border-left: 3px solid #333;">
            <p><strong>Obs:</strong> {{ $order->observations }}</p>
        </div>
        @endif
        <div class="dashed-line"></div>
        <div class="items">
            <table class="ticket-table">
                <thead>
                    <tr>
                        <th class="cant">Cant</th>
                        <th class="prod">Producto</th>
                        <th style="text-align:right;width:38px;">Precio</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupedItems as $item)
                    <tr>
                        <td class="cant">{{ $item['quantity'] }}</td>
                        <td class="prod">
                            <strong>{{ $item['product']->name }}</strong>
                            @if(isset($item['modifiers']) && $item['modifiers']->count() > 0)
                                <div class="item-details">@foreach($item['modifiers'] as $modifier) + {{ $modifier->name }} @endforeach</div>
                            @endif
                            @if(!empty($item['observations']))
                                <div class="item-observations">Nota: {{ $item['observations'] }}</div>
                            @endif
                        </td>
                        <td style="text-align:right;">${{ number_format($item['subtotal'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
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
                <span>TOTAL:</span>
                <span>${{ number_format($order->total, 2) }}</span>
            </div>
        </div>
        <div class="dashed-line"></div>
        <div class="footer">
            <p>{{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
