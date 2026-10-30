<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Diseño · {{ \App\Support\Branding::name() }}</title>
    @include('partials.favicon')
    {{-- Editor OpenDesign (MIT) — ver resources/opendesign/NOTICE.md --}}
    <script>
        window.__OPENDESIGN__ = @json($config);
    </script>
    @vite('src/main.tsx', 'opendesign')
</head>
<body>
    <noscript>El editor de Diseño necesita JavaScript activado.</noscript>
    <div id="app"></div>
</body>
</html>
