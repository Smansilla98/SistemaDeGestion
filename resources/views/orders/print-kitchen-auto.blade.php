<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket Cocina - {{ $order->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-size: 12px; font-family: 'Times New Roman', 'Courier New', monospace; }
        body { padding: 2mm; }
        .ticket {
            width: 80mm;
            max-width: 80mm;
            margin: 0 auto;
        }
        td, th, tr, table {
            border-collapse: collapse;
            font-size: 12px;
            font-family: 'Times New Roman', 'Courier New', monospace;
        }
        .centrado { text-align: center; }
        .header { border-bottom: 1px dashed #000; padding-bottom: 4px; margin-bottom: 6px; text-align: center; }
        .header h1 { font-size: 14px; font-weight: bold; }
        .order-info { margin-bottom: 6px; font-size: 11px; }
        .order-info p { margin: 1px 0; }
        .items table { width: 100%; border-top: 1px solid #000; }
        .items td, .items th { padding: 2px 4px; border-bottom: 1px dashed #ccc; }
        .items th { text-align: left; font-size: 11px; }
        td.cant { width: 28px; max-width: 28px; text-align: right; }
        td.prod { word-break: break-word; }
        .footer { margin-top: 8px; text-align: center; border-top: 1px dashed #000; padding-top: 4px; font-size: 10px; }

        @media print {
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                width: 80mm !important;
                max-width: 80mm !important;
                overflow: visible !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .ticket {
                width: 80mm !important;
                max-width: 80mm !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            body * { visibility: visible; }
        }
    </style>
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
        <div class="items">
            <table>
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
                                <br><small>@foreach($item['modifiers'] as $modifier) + {{ $modifier->name }} @endforeach</small>
                            @endif
                            @if(!empty($item['observations']))
                                <br><small>Nota: {{ $item['observations'] }}</small>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="footer centrado">
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
                setTimeout(function() { try { window.close(); } catch (e) {} }, 800);
            }
            if (document.readyState === 'complete') {
                doPrint();
            } else {
                window.onload = function() { setTimeout(doPrint, 150); };
            }
            setTimeout(doPrint, 1200);
        })();
    </script>
</body>
</html>
