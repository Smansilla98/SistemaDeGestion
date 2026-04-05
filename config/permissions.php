<?php

return [
    /*
    | Módulos y permisos (Ver, Crear, Actualizar, Eliminar).
    | permission_key = módulo.acción (ej: users.view, users.create).
    */
    'modules' => [
        ['key' => 'dashboard', 'label' => 'Dashboard (Página Principal)', 'actions' => ['view']],
        ['key' => 'tables', 'label' => 'Mesas', 'actions' => ['view', 'create', 'update', 'delete']],
        ['key' => 'orders', 'label' => 'Pedidos', 'actions' => ['view', 'create', 'update', 'delete']],
        ['key' => 'kitchen', 'label' => 'Cocina', 'actions' => ['view', 'update']],
        ['key' => 'cash-register', 'label' => 'Caja', 'actions' => ['view', 'create', 'update', 'delete']],
        ['key' => 'discount-types', 'label' => 'Descuentos', 'actions' => ['view', 'create', 'update', 'delete']],
        ['key' => 'sectors', 'label' => 'Sectores', 'actions' => ['view', 'create', 'update', 'delete']],
        ['key' => 'categories', 'label' => 'Categorías', 'actions' => ['view', 'create', 'update', 'delete']],
        ['key' => 'products', 'label' => 'Productos', 'actions' => ['view', 'create', 'update', 'delete']],
        ['key' => 'stock', 'label' => 'Stock', 'actions' => ['view', 'create', 'update', 'delete']],
        ['key' => 'stock_mozo', 'label' => 'Ingreso de insumos (mozos)', 'actions' => ['create']],
        ['key' => 'users', 'label' => 'Usuarios', 'actions' => ['view', 'create', 'update', 'delete']],
        ['key' => 'printers', 'label' => 'Impresoras', 'actions' => ['view', 'create', 'update', 'delete']],
        ['key' => 'events', 'label' => 'Eventos', 'actions' => ['view', 'create', 'update', 'delete']],
        ['key' => 'recurring-activities', 'label' => 'Actividades Recurrentes', 'actions' => ['view', 'create', 'update', 'delete']],
        ['key' => 'fixed-expenses', 'label' => 'Gastos Fijos', 'actions' => ['view', 'create', 'update', 'delete']],
        ['key' => 'reports', 'label' => 'Reportes', 'actions' => ['view', 'export']],
        ['key' => 'configuration', 'label' => 'Configuración', 'actions' => ['view', 'update']],
        ['key' => 'tutorials', 'label' => 'Tutoriales', 'actions' => ['view', 'create', 'update', 'delete']],
    ],

    'action_labels' => [
        'view' => 'Ver',
        'create' => 'Crear',
        'update' => 'Actualizar',
        'delete' => 'Eliminar',
        'export' => 'Exportar',
    ],

    /*
    | Valores por defecto por rol (cuando no hay fila en role_permissions).
    | true = permitido, false = no permitido.
    */
    'role_defaults' => [
        'SUPERADMIN' => true,
        'ADMIN' => true, // todos los permisos (se resuelve por módulo)
        'GERENTE' => [
            'stock_mozo.create' => false,
            'dashboard.view' => true,
            'tables.view' => true, 'tables.create' => true, 'tables.update' => true, 'tables.delete' => true,
            'orders.view' => true, 'orders.create' => true, 'orders.update' => true, 'orders.delete' => true,
            'kitchen.view' => false, 'kitchen.update' => false,
            'cash-register.view' => true, 'cash-register.create' => false, 'cash-register.update' => true, 'cash-register.delete' => false,
            'discount-types.view' => false, 'discount-types.create' => false, 'discount-types.update' => false, 'discount-types.delete' => false,
            'sectors.view' => false, 'sectors.create' => false, 'sectors.update' => false, 'sectors.delete' => false,
            'categories.view' => false, 'categories.create' => false, 'categories.update' => false, 'categories.delete' => false,
            'products.view' => true, 'products.create' => true, 'products.update' => true, 'products.delete' => true,
            'stock.view' => true, 'stock.create' => true, 'stock.update' => true, 'stock.delete' => true,
            'users.view' => true, 'users.create' => true, 'users.update' => true, 'users.delete' => true,
            'printers.view' => false, 'printers.create' => false, 'printers.update' => false, 'printers.delete' => false,
            'events.view' => true, 'events.create' => true, 'events.update' => true, 'events.delete' => true,
            'recurring-activities.view' => true, 'recurring-activities.create' => true, 'recurring-activities.update' => true, 'recurring-activities.delete' => true,
            'fixed-expenses.view' => false, 'fixed-expenses.create' => false, 'fixed-expenses.update' => false, 'fixed-expenses.delete' => false,
            'reports.view' => false, 'reports.export' => false,
            'configuration.view' => true, 'configuration.update' => true,
            'tutorials.view' => true, 'tutorials.create' => true, 'tutorials.update' => true, 'tutorials.delete' => true,
        ],
        'CAJERO' => [
            'stock_mozo.create' => false,
            'dashboard.view' => true,
            'tables.view' => false, 'orders.view' => true, 'orders.create' => true, 'orders.update' => true, 'orders.delete' => false,
            'kitchen.view' => false, 'cash-register.view' => true, 'cash-register.create' => false, 'cash-register.update' => true, 'cash-register.delete' => false,
            'discount-types.view' => false, 'sectors.view' => false, 'categories.view' => false,
            'products.view' => true, 'stock.view' => true, 'stock.create' => true, 'stock.update' => true, 'stock.delete' => false,
            'users.view' => false, 'printers.view' => false,
            'events.view' => false, 'recurring-activities.view' => true,
            'fixed-expenses.view' => true, 'reports.view' => true, 'reports.export' => true,
            'configuration.view' => false, 'tutorials.view' => true,
        ],
        'MOZO' => [
            'dashboard.view' => true,
            'tables.view' => true, 'tables.create' => false, 'tables.update' => true, 'tables.delete' => false,
            'orders.view' => true, 'orders.create' => true, 'orders.update' => true, 'orders.delete' => false,
            'kitchen.view' => false, 'cash-register.view' => false, 'discount-types.view' => false,
            'sectors.view' => false, 'categories.view' => false,
            'products.view' => true, 'stock.view' => false, 'stock_mozo.create' => true, 'users.view' => false, 'printers.view' => false,
            'events.view' => true, 'recurring-activities.view' => true,
            'fixed-expenses.view' => false, 'reports.view' => false, 'configuration.view' => false, 'tutorials.view' => true,
        ],
        'COCINA' => [
            'dashboard.view' => true,
            'tables.view' => false, 'orders.view' => true, 'kitchen.view' => true, 'kitchen.update' => true,
            'cash-register.view' => false, 'discount-types.view' => false, 'sectors.view' => false, 'categories.view' => false,
            'products.view' => true, 'stock.view' => false, 'users.view' => false, 'printers.view' => false,
            'events.view' => false, 'recurring-activities.view' => false,
            'fixed-expenses.view' => false, 'reports.view' => false, 'configuration.view' => false, 'tutorials.view' => true,
        ],
        'SUPERVISOR' => [
            'dashboard.view' => true,
            'tables.view' => true, 'orders.view' => true, 'kitchen.view' => false, 'cash-register.view' => true,
            'discount-types.view' => false, 'sectors.view' => false, 'categories.view' => false,
            'products.view' => true, 'stock.view' => true, 'users.view' => false, 'printers.view' => false,
            'events.view' => true, 'recurring-activities.view' => true,
            'fixed-expenses.view' => false, 'reports.view' => true, 'configuration.view' => false, 'tutorials.view' => true,
        ],
        'ENCARGADO' => [
            'dashboard.view' => true,
            'tables.view' => true, 'orders.view' => true, 'kitchen.view' => false, 'cash-register.view' => true,
            'discount-types.view' => false, 'sectors.view' => false, 'categories.view' => false,
            'products.view' => true, 'stock.view' => true, 'users.view' => false, 'printers.view' => false,
            'events.view' => true, 'recurring-activities.view' => true,
            'fixed-expenses.view' => false, 'reports.view' => true, 'configuration.view' => false, 'tutorials.view' => true,
        ],
    ],
];
