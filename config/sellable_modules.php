<?php

/**
 * Módulos vendibles por restaurante.
 * Si no hay licencia guardada, quedan todos habilitados.
 * Dashboard, usuarios y el monitor de superadmin no se apagan.
 */
return [
    'tables' => [
        'label' => 'Mesas',
        'description' => 'Salón, sectores y ocupación de mesas.',
        'offered' => true,
        'web' => ['tables.', 'sectors.'],
        'api' => ['tables', 'catalog/sectors'],
    ],
    'orders' => [
        'label' => 'Pedidos',
        'description' => 'Toma de pedidos, comanda y estados.',
        'offered' => true,
        'web' => ['orders.'],
        'api' => ['orders'],
    ],
    'kitchen' => [
        'label' => 'Cocina',
        'description' => 'Tablero de cocina y avisos de pedido listo.',
        'offered' => true,
        'web' => ['kitchen.', 'api.ready-orders'],
        'api' => ['kitchen', 'notifications/ready-orders'],
    ],
    'cash' => [
        'label' => 'Caja',
        'description' => 'Sesiones de caja, cobros y medios de pago.',
        'offered' => true,
        'web' => ['cash-register.'],
        'api' => ['cash', 'payment-method-configurations'],
    ],
    'catalog' => [
        'label' => 'Catálogo',
        'description' => 'Productos, categorías, descuentos y clientes.',
        'offered' => true,
        'web' => ['products.', 'categories.', 'discount-types.'],
        'api' => ['products', 'categories', 'catalog/categories', 'catalog/discounts', 'clients'],
    ],
    'stock' => [
        'label' => 'Stock',
        'description' => 'Inventario, movimientos e insumos.',
        'offered' => true,
        'web' => ['stock.'],
        'api' => ['stock'],
    ],
    'reports' => [
        'label' => 'Reportes',
        'description' => 'Ventas del período.',
        'offered' => true,
        'web' => ['reports.'],
        'api' => ['reports'],
    ],
    'events' => [
        'label' => 'Eventos',
        'description' => 'Agenda y actividades recurrentes. No se ofrece al local: no es la venta de comida.',
        'offered' => false,
        'web' => ['events.', 'recurring-activities.'],
        'api' => ['events', 'recurring-activities'],
    ],
    'fixed-expenses' => [
        'label' => 'Gastos fijos',
        'description' => 'Alquiler y servicios. No se ofrece al local.',
        'offered' => false,
        'web' => ['fixed-expenses.'],
        'api' => ['fixed-expenses'],
    ],
];
