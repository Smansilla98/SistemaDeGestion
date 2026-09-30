<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>¿Qué querés hacer?</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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
        .wrap { width: min(920px, 100%); text-align: center; }
        h1 {
            margin: 0 0 28px;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .cards {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .card {
            width: 280px;
            text-align: left;
            background: #fff;
            border: 1px solid #e7e9ee;
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
            padding: 22px 22px 20px;
            color: inherit;
            text-decoration: none;
            cursor: pointer;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        button.card {
            font: inherit;
            appearance: none;
        }
        .card:hover {
            border-color: #d5d9e2;
            box-shadow: 0 8px 24px rgba(16, 24, 40, 0.06);
        }
        .ico {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 14px;
        }
        .ico.demo { color: #c4843a; }
        .ico.cfg { color: #5b8fd4; }
        .ico.quote { color: #4e8d99; }
        .card strong {
            display: block;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .card span {
            display: block;
            color: #8b93a1;
            font-size: 13px;
            line-height: 1.4;
        }
        .out {
            margin-top: 28px;
        }
        .out button {
            background: none;
            border: 0;
            color: #8b93a1;
            font: inherit;
            font-size: 13px;
            cursor: pointer;
        }
        .out button:hover { color: #1c2430; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>¿Qué querés hacer?</h1>
        <div class="cards">
            <form method="POST" action="{{ route('entry.demo') }}">
                @csrf
                <button type="submit" class="card">
                    <div class="ico demo"><i class="bi bi-clipboard"></i></div>
                    <strong>Ver la demo</strong>
                    <span>Recorré el producto completo, con todos los módulos.</span>
                </button>
            </form>
            <a class="card" href="{{ route('entry.modules') }}">
                <div class="ico cfg"><i class="bi bi-gear"></i></div>
                <strong>Configurar módulos</strong>
                <span>Elegí qué módulos se ven en esta visita.</span>
            </a>
            <a class="card" href="{{ route('entry.quote') }}">
                <div class="ico quote"><i class="bi bi-file-earmark-text"></i></div>
                <strong>Presupuesto</strong>
                <span>Números de referencia, sin cliente. Todo se puede cambiar.</span>
            </a>
        </div>
        <form class="out" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Cerrar sesión</button>
        </form>
    </div>
</body>
</html>
