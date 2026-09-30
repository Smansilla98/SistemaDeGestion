<?php

declare(strict_types=1);

/**
 * Usuario de demostración / Play Store: no autentica, solo responde un mensaje amable.
 */
return [
    'enabled' => (bool) env('DEMO_LOGIN_ENABLED', true),
    'username' => (string) env('DEMO_LOGIN_USERNAME', 'demo'),
    'password' => (string) env('DEMO_LOGIN_PASSWORD', 'demo1234'),
    'message' => (string) env(
        'DEMO_LOGIN_MESSAGE',
        'hola, gracias por probar la app :)'
    ),
];
