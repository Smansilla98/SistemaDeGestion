<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket ítem - {{ $order->number }}</title>
    @include('partials.print-ticket-styles')
</head>
<body>
    <div class="ticket">
        <div class="border-asterisk">********************************</div>
        <div class="header">
            <h1>COMANDA - 1 ÍTEM</h1>
            <p>Pedido #{{ $order->number }}</p>
        </div>
        <div class="border-asterisk">********************************</div>
        <div class="dashed-line"></div>
        <div class="order-info">
            @if($order->table)
                <p><strong>Mesa:</strong> {{ $order->table->number }}</p>
            @elseif($order->customer_name)
                <p><strong>Consumidor:</strong> {{ $order->customer_name }}</p>
            @endif
            @if($order->user)
                <p><strong>Mozo:</strong> {{ $order->user->name }}</p>
            @endif
            <p class="timestamp"><strong>Hora:</strong> {{ $order->created_at->format('H:i') }}</p>
        </div>
        <div class="dashed-line"></div>
        <div class="items">
            <div class="item">
                <div class="item-line">
                    <span class="item-quantity">{{ $item->quantity }}x</span>
                    <span class="item-name">{{ $item->product->name }}</span>
                    <span class="item-price">${{ number_format($item->subtotal, 2) }}</span>
                </div>
                @if($item->modifiers && $item->modifiers->count() > 0)
                <div class="item-details">
                    @foreach($item->modifiers as $modifier)
                    + {{ $modifier->name }}
                    @endforeach
                </div>
                @endif
                @if($item->observations)
                <div class="item-observations">Nota: {{ $item->observations }}</div>
                @endif
            </div>
        </div>
        <div class="dashed-line"></div>
        <div class="footer">
            <p>{{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
    <script>
        (function() {
            var printed = false;
            function doPrint() {
                if (printed) return;
                printed = true;
                window.focus();
                window.print();
                setTimeout(function() { try { window.close(); } catch (e) {} }, 1500);
            }
            if (document.readyState === 'complete') doPrint();
            else window.onload = function() { setTimeout(doPrint, 150); };
            setTimeout(doPrint, 1200);
        })();
    </script>
</body>
</html>
