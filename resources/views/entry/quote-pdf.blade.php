<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1c2430; line-height: 1.4; }
        h1 { font-size: 20px; margin: 0 0 2px; }
        h2 { font-size: 13px; margin: 16px 0 6px; }
        .kicker { letter-spacing: 1px; font-size: 9px; color: #5c6570; margin: 0 0 6px; }
        .tag { color: #5c6570; margin: 0 0 12px; }
        table.meta { width: 100%; margin-bottom: 10px; }
        table.meta td { vertical-align: top; padding: 2px 8px 2px 0; }
        table.meta td.k { width: 90px; color: #5c6570; }
        .note { background: #f4f5f7; padding: 8px 10px; margin: 8px 0 4px; }
        table.prices { width: 100%; border-collapse: collapse; }
        table.prices th { text-align: left; font-size: 9px; color: #5c6570; border-bottom: 1px solid #ccc; padding: 4px; }
        table.prices td { border-bottom: 1px solid #eee; padding: 5px 4px; }
        .num { text-align: right; }
        .totals td { font-weight: bold; border-bottom: 0; }
        ul { margin: 4px 0 0; padding-left: 16px; }
    </style>
</head>
<body>
    <p class="kicker">PROPUESTA COMERCIAL MODULAR</p>
    <h1>{{ $quote['product'] }}</h1>
    <p class="tag">{{ $quote['tagline'] }}<br>{{ $quote['modules'] }}</p>

    <table class="meta">
        <tr><td class="k">De</td><td>{{ $quote['from'] }}</td></tr>
        <tr><td class="k">Fecha</td><td>{{ $quote['issued_label'] }}</td></tr>
        <tr><td class="k">Validez</td><td>30 días corridos, hasta el {{ $quote['valid_label'] }}</td></tr>
        <tr><td class="k">Moneda</td><td>USD. Conversión a pesos al tipo de cambio del día de emisión.</td></tr>
        <tr><td class="k">Cliente</td><td>Sin cliente asignado.</td></tr>
        <tr><td class="k">Alcance</td><td>Números de referencia. Todo es modificable: módulos, precios, abono y forma de pago.</td></tr>
    </table>

    <p class="note">
        Los importes de este documento son números de referencia y no constituyen una oferta cerrada.
        Todo es modificable antes de destinarse a un cliente: módulos incluidos, licencia, puesta en marcha, abono mensual y forma de pago.
        Dashboard, usuarios y notificaciones van incluidos. No se cotizan actividades recurrentes, eventos ni gastos fijos.
    </p>

    <h2>Precios de referencia por módulo</h2>
    <table class="prices">
        <thead>
            <tr>
                <th>Módulo</th>
                <th class="num">Licencia</th>
                <th class="num">Puesta en marcha</th>
                <th class="num">Oferta</th>
            </tr>
        </thead>
        <tbody>
            @forelse($quote['lines'] as $line)
                <tr>
                    <td>{{ $line['label'] }}<br><span style="color:#5c6570;">{{ $line['description'] }}</span></td>
                    <td class="num">USD {{ number_format($line['license_usd'], 0, ',', '.') }}</td>
                    <td class="num">USD {{ number_format($line['setup_usd'], 0, ',', '.') }}</td>
                    <td class="num">USD {{ number_format($line['offer_usd'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Ningún módulo marcado.</td></tr>
            @endforelse
            <tr class="totals">
                <td>Total único</td>
                <td class="num">USD {{ number_format($quote['license_usd'], 0, ',', '.') }}</td>
                <td class="num">USD {{ number_format($quote['setup_usd'], 0, ',', '.') }}</td>
                <td class="num">USD {{ number_format($quote['offer_usd'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Soporte y hosting</h2>
    <p>{{ $quote['hosting_label'] }} — USD {{ number_format($quote['hosting_usd'], 0, ',', '.') }} / mes.</p>
    <ul>
        <li>Hasta 2 módulos: USD 90</li>
        <li>3 o 4 módulos: USD 140</li>
        <li>5 o más: USD 220</li>
    </ul>

    <h2>Armados de referencia</h2>
    <ul>
        @foreach($quote['bundles'] as $bundle)
            <li>{{ $bundle['name'] }} — USD {{ number_format($bundle['total_usd'], 0, ',', '.') }}</li>
        @endforeach
    </ul>

    <h2>Forma de pago</h2>
    <ul>
        <li>40% al aceptar el alcance</li>
        <li>40% con el entorno piloto de los módulos elegidos</li>
        <li>20% en la salida en vivo</li>
        <li>El abono mensual empieza el mes del pase a producción</li>
    </ul>
</body>
</html>
