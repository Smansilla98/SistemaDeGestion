<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API — Documentación</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
    <style>body { margin: 0; }</style>
</head>
<body>
<div id="swagger-ui"></div>
<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js" crossorigin></script>
<script>
    window.onload = function () {
        window.ui = SwaggerUIBundle({
            url: @json($specUrl),
            dom_id: '#swagger-ui',
            presets: [SwaggerUIBundle.presets.apis],
            layout: 'BaseLayout',
        });
    };
</script>
</body>
</html>
