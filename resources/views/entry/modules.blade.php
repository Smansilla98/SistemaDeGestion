<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Configurar módulos</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
            background: #f4f5f7;
            color: #1c2430;
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .panel {
            width: min(560px, 100%);
            background: #fff;
            border: 1px solid #e7e9ee;
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
            padding: 28px 28px 22px;
        }
        h1 {
            margin: 0 0 6px;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        p.lead {
            margin: 0 0 20px;
            color: #8b93a1;
            font-size: 13px;
        }
        label.row {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            border: 1px solid #eef0f4;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 8px;
            cursor: pointer;
        }
        label.row strong { display: block; font-size: 14px; }
        label.row span { display: block; color: #8b93a1; font-size: 12px; margin-top: 2px; }
        input { margin-top: 3px; }
        .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 18px;
        }
        a.back { color: #8b93a1; font-size: 13px; text-decoration: none; }
        a.back:hover { color: #1c2430; }
        button.go {
            background: #1c2430;
            color: #fff;
            border: 0;
            border-radius: 10px;
            padding: 10px 16px;
            font: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <form class="panel" method="POST" action="{{ route('entry.modules.store') }}">
        @csrf
        <h1>Configurar módulos</h1>
        <p class="lead">Solo la venta de comida. Lo que desmarques no aparece en el menú ni se puede abrir en esta sesión. La demo completa sigue disponible al volver a entrar.</p>
        @foreach($offered as $key => $module)
            <label class="row">
                <input type="hidden" name="modules[{{ $key }}]" value="0">
                <input
                    type="checkbox"
                    name="modules[{{ $key }}]"
                    value="1"
                    @checked($selected === null || ($selected[$key] ?? false))
                >
                <span>
                    <strong>{{ $module['label'] }}</strong>
                    <span>{{ $module['description'] }}</span>
                </span>
            </label>
        @endforeach
        <div class="actions">
            <a class="back" href="{{ route('entry.choose') }}">Volver</a>
            <button class="go" type="submit">Entrar así</button>
        </div>
    </form>
</body>
</html>
