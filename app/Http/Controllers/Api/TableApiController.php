<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Models\Sector;
use Illuminate\Http\Request;

class TableApiController extends Controller
{
    /**
     * Listar todas las mesas
     */
    public function index(Request $request)
    {
        $restaurantId = auth()->user()->restaurant_id;

        $tables = Table::where('restaurant_id', $restaurantId)
            ->with(['sector'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tables
        ]);
    }

    /**
     * Mostrar una mesa específica
     */
    public function show(Table $table)
    {
        if ($table->restaurant_id !== auth()->user()->restaurant_id) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $table->load(['sector', 'currentOrder']);

        return response()->json([
            'success' => true,
            'data' => $table
        ]);
    }

    /**
     * Obtener mesas por sector
     */
    public function bySector($sectorId)
    {
        $restaurantId = auth()->user()->restaurant_id;

        $sector = Sector::where('id', $sectorId)
            ->where('restaurant_id', $restaurantId)
            ->firstOrFail();

        $tables = Table::where('sector_id', $sectorId)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tables,
            'sector' => $sector
        ]);
    }
}

