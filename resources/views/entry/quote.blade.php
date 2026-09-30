<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presupuesto modular — {{ $quote['product'] }}</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: #f4f5f7;
            color: #1c2430;
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            padding: 28px 16px 48px;
        }
        .top {
            width: min(880px, 100%);
            margin: 0 auto 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        a.back, a.pdf {
            color: #5c6570;
            font-size: 13px;
            text-decoration: none;
        }
        a.pdf {
            background: #1c2430;
            color: #fff;
            border-radius: 10px;
            padding: 8px 12px;
            font-weight: 600;
        }
        .paper {
            width: min(880px, 100%);
            margin: 0 auto;
            background: #fff;
            border: 1px solid #e7e9ee;
            border-radius: 16px;
            padding: 32px 28px 28px;
        }
        .kicker {
            letter-spacing: .14em;
            font-size: 11px;
            font-weight: 700;
            color: #8b93a1;
            margin: 0 0 8px;
        }
        h1 { margin: 0; font-size: 28px; letter-spacing: -0.03em; }
        .tag { margin: 6px 0 0; color: #5c6570; }
        .meta {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 6px 12px;
            margin: 22px 0;
            font-size: 14px;
        }
        .meta dt { color: #8b93a1; margin: 0; }
        .meta dd { margin: 0; }
        .note {
            background: #f7f8fa;
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 14px;
            line-height: 1.45;
            color: #3a4350;
        }
        h2 { font-size: 16px; margin: 26px 0 10px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th {
            text-align: left;
            font-size: 11px;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #8b93a1;
            padding: 8px 6px;
            border-bottom: 1px solid #e7e9ee;
        }
        td { padding: 10px 6px; border-bottom: 1px solid #f0f2f5; vertical-align: top; }
        td.num, th.num { text-align: right; white-space: nowrap; }
        tr.off td { color: #b0b6c0; }
        tr.off td.num { text-decoration: line-through; }
        .desc { display: block; color: #8b93a1; font-size: 12px; margin-top: 2px; }
        tr.off .desc { color: #c5cad3; }
        .totals td { font-weight: 700; border-bottom: 0; }
        .side {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        ul { margin: 0; padding-left: 18px; color: #3a4350; font-size: 14px; }
        li { margin: 6px 0; }
        @media (max-width: 720px) {
            .side { grid-template-columns: 1fr; }
            .paper { padding: 22px 14px; }
        }
    </style>
</head>
<body>
    <div class="top">
        <a class="back" href="{{ route('entry.choose') }}">Volver</a>
        <a class="pdf" id="pdf" href="{{ route('entry.quote.pdf', ['modules' => $selected]) }}">Descargar PDF</a>
    </div>
    <article class="paper">
        <p class="kicker">PROPUESTA COMERCIAL MODULAR</p>
        <h1>{{ $quote['product'] }}</h1>
        <p class="tag">{{ $quote['tagline'] }}</p>

        <dl class="meta">
            <dt>De</dt>
            <dd>{{ $quote['from'] }}</dd>
            <dt>Fecha</dt>
            <dd>{{ $quote['issued_label'] }}</dd>
            <dt>Validez</dt>
            <dd>30 días corridos, hasta el {{ $quote['valid_label'] }}</dd>
            <dt>Moneda</dt>
            <dd>Dólares estadounidenses (USD). Conversión a pesos al tipo de cambio del día de emisión.</dd>
        </dl>

        <p class="note">
            Los importes son números de referencia, no una oferta cerrada. No hay cliente asignado. Licencias, puesta en marcha, abono, armados, forma de pago y qué módulos entran se pueden modificar antes de enviar una propuesta real. Cada módulo se muestra con licencia de uso (pago único) más puesta en marcha. El soporte y el hosting son un solo abono mensual, según cuántos módulos queden activos. Dashboard, usuarios y notificaciones van incluidos y no se cotizan. No se ofrecen actividades recurrentes, eventos ni gastos fijos.
        </p>

        <h2>Precios de referencia por módulo</h2>
        <table>
            <thead>
                <tr>
                    <th></th>
                    <th>Módulo</th>
                    <th class="num">Licencia</th>
                    <th class="num">Puesta en marcha</th>
                    <th class="num">Oferta</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quote['catalog'] as $line)
                    <tr data-module="{{ $line['key'] }}" data-license="{{ $line['license_usd'] }}" data-setup="{{ $line['setup_usd'] }}" @class(['off' => ! in_array($line['key'], $selected, true)])>
                        <td>
                            <input type="checkbox" value="{{ $line['key'] }}" @checked(in_array($line['key'], $selected, true))>
                        </td>
                        <td>
                            {{ $line['label'] }}
                            <span class="desc">{{ $line['description'] }}</span>
                        </td>
                        <td class="num">USD {{ number_format($line['license_usd'], 0, ',', '.') }}</td>
                        <td class="num">USD {{ number_format($line['setup_usd'], 0, ',', '.') }}</td>
                        <td class="num">USD {{ number_format($line['offer_usd'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="totals">
                    <td></td>
                    <td>Total único de lo marcado</td>
                    <td class="num" id="sum-license">USD {{ number_format($quote['license_usd'], 0, ',', '.') }}</td>
                    <td class="num" id="sum-setup">USD {{ number_format($quote['setup_usd'], 0, ',', '.') }}</td>
                    <td class="num" id="sum-offer">USD {{ number_format($quote['offer_usd'], 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="side">
            <div>
                <h2>Armados de referencia</h2>
                <ul>
                    @foreach($quote['bundles'] as $bundle)
                        <li>{{ $bundle['name'] }} — USD {{ number_format($bundle['total_usd'], 0, ',', '.') }}</li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h2>Soporte y hosting</h2>
                <p class="tag" id="hosting-label">{{ $quote['hosting_label'] }}</p>
                <p><strong id="hosting-usd">USD {{ number_format($quote['hosting_usd'], 0, ',', '.') }} / mes</strong></p>
                <ul>
                    <li>Hasta 2 módulos — USD 90</li>
                    <li>3 o 4 módulos — USD 140</li>
                    <li>5 o más — USD 220</li>
                </ul>
            </div>
        </div>

        <h2>Forma de pago</h2>
        <ul>
            <li>40% al aceptar el alcance</li>
            <li>40% con el entorno piloto de los módulos elegidos</li>
            <li>20% en la salida en vivo</li>
            <li>El abono mensual empieza el mes del pase a producción</li>
        </ul>
    </article>
    <script>
        const pdfBase = @json(route('entry.quote.pdf'));
        const money = (n) => 'USD ' + new Intl.NumberFormat('es-AR').format(n);

        function sync() {
            let license = 0, setup = 0, n = 0;
            const params = new URLSearchParams();
            document.querySelectorAll('[data-module]').forEach((row) => {
                const on = row.querySelector('input').checked;
                row.classList.toggle('off', !on);
                if (!on) return;
                n += 1;
                license += Number(row.dataset.license);
                setup += Number(row.dataset.setup);
                params.append('modules[]', row.dataset.module);
            });
            let hosting = 0;
            let label = 'Sin módulos';
            if (n > 0 && n <= 2) { hosting = 90; label = 'Escalón 1 · hasta 2 módulos'; }
            else if (n <= 4 && n > 2) { hosting = 140; label = 'Escalón 2 · 3 o 4 módulos'; }
            else if (n >= 5) { hosting = 220; label = 'Escalón 3 · 5 o más módulos'; }
            document.getElementById('sum-license').textContent = money(license);
            document.getElementById('sum-setup').textContent = money(setup);
            document.getElementById('sum-offer').textContent = money(license + setup);
            document.getElementById('hosting-usd').textContent = money(hosting) + ' / mes';
            document.getElementById('hosting-label').textContent = label;
            document.getElementById('pdf').href = pdfBase + '?' + params.toString();
        }

        document.querySelectorAll('[data-module] input').forEach((input) => {
            input.addEventListener('change', sync);
        });
    </script>
</body>
</html>
