<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas {{ $dateFrom }} - {{ $dateTo }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        h1 { font-size: 18px; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>Reporte de Ventas</h1>
    <p>Período: {{ $dateFrom }} al {{ $dateTo }}</p>
    <p><strong>Total ventas: ${{ number_format($totalSales, 2) }}</strong></p>

    <h2>Ventas por método de pago</h2>
    <table>
        <thead>
            <tr><th>Método</th><th class="text-right">Total</th></tr>
        </thead>
        <tbody>
            @foreach($salesByMethod as $s)
            <tr>
                <td>{{ $s->payment_method }}</td>
                <td class="text-right">${{ number_format($s->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Ventas por día</h2>
    <table>
        <thead>
            <tr><th>Fecha</th><th class="text-right">Total</th></tr>
        </thead>
        <tbody>
            @foreach($salesByDay as $s)
            <tr>
                <td>{{ $s->date }}</td>
                <td class="text-right">${{ number_format($s->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
