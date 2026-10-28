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
        'license_usd' => 1200,
        'setup_usd' => 500,
        'web' => ['tables.', 'sectors.'],
        'api' => ['tables', 'catalog/sectors'],
    ],
    'orders' => [
        'label' => 'Pedidos',
        'description' => 'Toma de pedidos, comanda y estados.',
        'offered' => true,
        'license_usd' => 1800,
        'setup_usd' => 700,
        'web' => ['orders.'],
        'api' => ['orders'],
    ],
    'kitchen' => [
        'label' => 'Cocina',
        'description' => 'Tablero de cocina y avisos de pedido listo.',
        'offered' => true,
        'license_usd' => 900,
        'setup_usd' => 400,
        'web' => ['kitchen.', 'api.ready-orders'],
        'api' => ['kitchen', 'notifications/ready-orders'],
    ],
    'cash' => [
        'label' => 'Caja',
        'description' => 'Sesiones de caja, cobros y medios de pago.',
        'offered' => true,
        'license_usd' => 1400,
        'setup_usd' => 500,
        'web' => ['cash-register.'],
        'api' => ['cash', 'payment-method-configurations'],
    ],
    'catalog' => [
        'label' => 'Catálogo',
        'description' => 'Productos, categorías, descuentos y clientes.',
        'offered' => true,
        'license_usd' => 800,
        'setup_usd' => 350,
        'web' => ['products.', 'categories.', 'discount-types.'],
        'api' => ['products', 'categories', 'catalog/categories', 'catalog/discounts', 'clients'],
    ],
    'stock' => [
        'label' => 'Stock',
        'description' => 'Inventario, movimientos e insumos.',
        'offered' => true,
        'license_usd' => 900,
        'setup_usd' => 450,
        'web' => ['stock.'],
        'api' => ['stock'],
    ],
    'reports' => [
        'label' => 'Reportes',
        'description' => 'Ventas del período.',
        'offered' => true,
        'license_usd' => 700,
        'setup_usd' => 300,
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

    // Módulos para organizaciones culturales, educativas y comerciales. default=false: un local
    // existente no los ve hasta que el superadmin los habilita en Monitor de módulos.
    'workshops' => [
        'label' => 'Talleres y agenda',
        'description' => 'Talleres, inscripciones con cupo y lista de espera, clases, asistencia, evaluaciones y agenda.',
        'offered' => false,
        'default' => false,
        'web' => ['workshops.', 'agenda.'],
        'api' => [],
    ],
    'contacts' => [
        'label' => 'Contactos y difusión',
        'description' => 'Contactos con etiquetas y grupos, importación/exportación y comunicaciones.',
        'offered' => false,
        'default' => false,
        'web' => ['contacts.', 'contact-groups.', 'campaigns.'],
        'api' => [],
    ],
    'market' => [
        'label' => 'Emprendedores y mercado',
        'description' => 'Emprendedores, productos, stock, consignaciones, liquidaciones y ciclos de pedidos.',
        'offered' => false,
        'default' => false,
        'web' => ['market.'],
        'api' => [],
    ],
    'documents' => [
        'label' => 'Documentos',
        'description' => 'Documentos con versiones y permisos, y manuales de marca en PDF.',
        'offered' => false,
        'default' => false,
        'web' => ['documents.'],
        'api' => [],
    ],
    'finance' => [
        'label' => 'Finanzas de actividades',
        'description' => 'Ingresos y gastos por taller o evento, repartos configurables (ej. 70/30) y liquidaciones.',
        'offered' => false,
        'default' => false,
        'web' => ['finance.'],
        'api' => [],
    ],
];
