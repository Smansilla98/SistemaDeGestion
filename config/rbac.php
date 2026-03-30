<?php

declare(strict_types=1);

return [
    'permissions' => [
        'auth.me',
        'products.read',
        'products.write',
        'orders.read',
        'orders.write',
        'clients.read',
        'clients.write',
        'users.read',
        'users.write',
    ],
    'role_permissions' => [
        'ADMIN' => ['*'],
        'GERENTE' => ['*'],
        'CAJERO' => [
            'auth.me', 'products.read', 'orders.read', 'orders.write', 'clients.read', 'clients.write',
        ],
        'MOZO' => [
            'auth.me', 'products.read', 'orders.read', 'orders.write', 'clients.read', 'clients.write',
        ],
        'COCINA' => [
            'auth.me', 'products.read', 'orders.read', 'orders.write',
        ],
        'SUPERVISOR' => [
            'auth.me', 'products.read', 'orders.read', 'clients.read',
        ],
        'ENCARGADO' => [
            'auth.me', 'products.read', 'orders.read', 'orders.write', 'clients.read',
        ],
    ],
];
