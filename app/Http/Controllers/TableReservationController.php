<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class TableReservationController extends Controller
{
    /**
     * Mostrar formulario de reserva
     */
    public function create(Request $request, Table $table)
    {
        Gate::authorize('view', $table);

        return view('tables.reserve', compact('table'));
    }

    /**
     * Crear reserva (implementación básica)
     * 
     * Nota: Esta es una implementación básica. Para producción,
     * se debería crear una tabla 'reservations' con campos como:
     * - customer_name
     * - customer_phone
     * - reservation_date
     * - reservation_time
     * - number_of_guests
     * - status (pending/confirmed/cancelled)
     */
    public function store(Request $request, Table $table)
    {
        Gate::authorize('update', $table);

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'reservation_date' => 'required|date|after_or_equal:today',
            'reservation_time' => 'required',
            'number_of_guests' => 'required|integer|min:1|max:' . $table->capacity,
        ]);

        // Verificar que la mesa esté disponible
        if ($table->status !== 'LIBRE') {
            return back()->with('error', 'La mesa no está disponible para reservar');
        }

        // Cambiar estado a RESERVADA
        $table->update(['status' => 'RESERVADA']);

        // En producción, aquí se guardaría la reserva en la tabla reservations
        // Reservation::create([...]);

        return redirect()->route('tables.index')
            ->with('success', "Reserva creada para {$validated['customer_name']} el {$validated['reservation_date']}");
    }
}

