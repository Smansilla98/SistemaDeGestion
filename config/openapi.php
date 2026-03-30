<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Orígenes CORS para GET /docs/openapi.yaml
    |--------------------------------------------------------------------------
    |
    | Si la UI de Swagger se sirve desde otro dominio (p. ej. otro servicio en
    | Railway) y el YAML sigue en esta app, lista aquí ese origen exacto
    | (scheme + host, sin barra final). Varios: separados por coma.
    |
    */
    'cors_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('OPENAPI_CORS_ORIGINS', ''))
    ))),

];
