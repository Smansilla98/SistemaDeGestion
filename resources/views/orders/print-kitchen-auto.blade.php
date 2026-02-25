<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir ticket - Pedido {{ $order->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; height: 100%; overflow: hidden; }
        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        @media print {
            body, html { overflow: visible; }
            iframe { position: absolute; left: 0; top: 0; width: 100%; height: 100%; }
        }
    </style>
</head>
<body>
    <iframe id="pdfFrame" src="{{ $pdf_url }}" title="Ticket cocina"></iframe>
    <script>
        (function() {
            var frame = document.getElementById('pdfFrame');
            var printed = false;
            function doPrint() {
                if (printed) return;
                printed = true;
                window.focus();
                window.print();
                setTimeout(function() {
                    try { window.close(); } catch (e) {}
                }, 800);
            }
            frame.onload = function() {
                setTimeout(doPrint, 700);
            };
            setTimeout(doPrint, 2000);
        })();
    </script>
</body>
</html>
