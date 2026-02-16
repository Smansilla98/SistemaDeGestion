<?php

namespace App\Http\Controllers\DiscountType;

use App\Http\Controllers\Controller;
use App\Models\DiscountType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DiscountTypeController extends Controller
{
    /**
     * Mostrar lista de tipos de descuentos
     */
    public function index(Request $request)
    {
        // Solo ADMIN puede gestionar descuentos
        if (auth()->user()->role !== 'ADMIN') {
            abort(403, 'No tienes permiso para acceder a esta sección');
        }

        $restaurantId = auth()->user()->restaurant_id;

        $query = DiscountType::where('restaurant_id', $restaurantId);

        // Búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filtro por estado
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $allowedSorts = ['name', 'percentage', 'is_active', 'created_at'];
        
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name');
        }

        $discountTypes = $query->paginate(20)->withQueryString();

        return view('discount-types.index', compact('discountTypes'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        if (auth()->user()->role !== 'ADMIN') {
            abort(403, 'No tienes permiso para acceder a esta sección');
        }

        return view('discount-types.create');
    }

    /**
     * Crear nuevo tipo de descuento
     */
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'ADMIN') {
            abort(403, 'No tienes permiso para acceder a esta sección');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $validated['restaurant_id'] = auth()->user()->restaurant_id;
        $validated['is_active'] = $request->has('is_active') ? true : false;

        DiscountType::create($validated);

        return redirect()->route('discount-types.index')
            ->with('success', 'Tipo de descuento creado exitosamente');
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(DiscountType $discountType)
    {
        if (auth()->user()->role !== 'ADMIN') {
            abort(403, 'No tienes permiso para acceder a esta sección');
        }

        // Verificar que el descuento pertenece al restaurante del usuario
        if ($discountType->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403, 'No tienes permiso para editar este descuento');
        }

        return view('discount-types.edit', compact('discountType'));
    }

    /**
     * Actualizar tipo de descuento
     */
    public function update(Request $request, DiscountType $discountType)
    {
        if (auth()->user()->role !== 'ADMIN') {
            abort(403, 'No tienes permiso para acceder a esta sección');
        }

        // Verificar que el descuento pertenece al restaurante del usuario
        if ($discountType->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403, 'No tienes permiso para editar este descuento');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'percentage' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? true : false;

        $discountType->update($validated);

        return redirect()->route('discount-types.index')
            ->with('success', 'Tipo de descuento actualizado exitosamente');
    }

    /**
     * Eliminar tipo de descuento
     */
    public function destroy(DiscountType $discountType)
    {
        if (auth()->user()->role !== 'ADMIN') {
            abort(403, 'No tienes permiso para acceder a esta sección');
        }

        // Verificar que el descuento pertenece al restaurante del usuario
        if ($discountType->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403, 'No tienes permiso para eliminar este descuento');
        }

        $discountType->delete();

        return redirect()->route('discount-types.index')
            ->with('success', 'Tipo de descuento eliminado exitosamente');
    }
}

