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
            <p><strong>Mozo:</strong> {{ $order->user->name ?? '-' }}</p>
            <p><strong>Hora:</strong> {{ $order->created_at->format('H:i') }}</p>
            @if($order->observations)
                <p><strong>Obs:</strong> {{ $order->observations }}</p>
            @endif
        </div>
        <div class="dashed-line"></div>
        <div class="items">
            <table class="ticket-table">
                <thead>
                    <tr>
                        <th class="cant">CANT</th>
                        <th class="prod">PRODUCTO</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupedItems as $item)
                    <tr>
                        <td class="cant">{{ $item['quantity'] }}x</td>
                        <td class="prod">
                            {{ $item['product']->name }}
                            @if(isset($item['modifiers']) && $item['modifiers']->count() > 0)
                                <br><span class="item-details">@foreach($item['modifiers'] as $modifier) + {{ $modifier->name }} @endforeach</span>
                            @endif
                            @if(!empty($item['observations']))
                                <br><span class="item-observations">Nota: {{ $item['observations'] }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
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
