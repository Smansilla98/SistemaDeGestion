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
            html, body { margin: 0; padding: 0; overflow: visible; width: auto; height: auto; }
            iframe {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                border: none;
            }
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
                try {
                    // Imprimir solo el contenido del iframe (el PDF del ticket), no la pantalla contenedora
                    if (frame.contentWindow && typeof frame.contentWindow.print === 'function') {
                        frame.contentWindow.focus();
                        frame.contentWindow.print();
                    } else {
                        window.print();
                    }
                } catch (e) {
                    window.print();
                }
                setTimeout(function() {
                    try { window.close(); } catch (err) {}
                }, 1000);
            }
            frame.onload = function() {
                setTimeout(doPrint, 800);
            };
            setTimeout(doPrint, 2500);
        })();
    </script>
</body>
</html>
