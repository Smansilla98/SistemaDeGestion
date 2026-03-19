<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InteractiveTutorialController extends Controller
{
    /**
     * Definición de tutoriales interactivos por nombre de ruta.
     * Se usa un motor del lado del cliente (overlay + tooltips) y targets con selectores CSS estables.
     */
    private const TUTORIALS_BY_ROUTE = [
        'tables.index' => [
            'tutorialKey' => 'tables.index',
            'steps' => [
                [
                    'title' => 'Buscá una mesa rápido',
                    'text' => 'Usá este buscador para filtrar por número o nombre.',
                    'target' => '[data-tutorial="tables-search"]',
                    'placement' => 'bottom',
                    'next' => 1,
                ],
                [
                    'title' => 'Seleccioná una mesa',
                    'text' => 'Acá vas a ver tus mesas por sector. Tocá una card para ver/gestionar acciones.',
                    'target' => '[data-tutorial="tables-table-card"]',
                    'placement' => 'top',
                    'next' => 2,
                ],
                [
                    'title' => 'Reservar o cambiar estado',
                    'text' => 'Según el estado de la mesa podés reservar o marcar como ocupada.',
                    'target' => '[data-tutorial="tables-action-reservar"],[data-tutorial="tables-action-ocupar"]',
                    'placement' => 'bottom',
                    'next' => null,
                ],
            ],
        ],
        'orders.index' => [
            'tutorialKey' => 'orders.index',
            'steps' => [
                [
                    'title' => 'Filtrá pedidos',
                    'text' => 'Podés buscar por número, cliente o mesa, y además filtrar por estado.',
                    'target' => '[data-tutorial="orders-search"]',
                    'placement' => 'bottom',
                    'next' => 1,
                ],
                [
                    'title' => 'Elegí el estado',
                    'text' => 'Seleccioná un estado para ver solo los pedidos que te interesan.',
                    'target' => '[data-tutorial="orders-status-filter"]',
                    'placement' => 'right',
                    'next' => 2,
                ],
                [
                    'title' => 'Abrí la lista de pedidos',
                    'text' => 'Acá tenés los pedidos según el filtro aplicado. Usá “Ver” para abrir el detalle.',
                    'target' => '[data-tutorial="orders-table"]',
                    'placement' => 'top',
                    'next' => 3,
                ],
                [
                    'title' => 'Acción rápida',
                    'text' => 'Usá el botón “Ver” para entrar al pedido.',
                    'target' => '[data-tutorial="orders-action-view"]',
                    'placement' => 'bottom',
                    'next' => null,
                ],
            ],
        ],
    ];

    public function steps(Request $request)
    {
        $routeName = $request->query('route');

        if (!$routeName || !isset(self::TUTORIALS_BY_ROUTE[$routeName])) {
            return response()->json(['tutorialKey' => null, 'steps' => []]);
        }

        return response()->json(self::TUTORIALS_BY_ROUTE[$routeName]);
    }
}

