<?php

namespace App\Http\Controllers\Sector;

use App\Http\Controllers\Controller;
use App\Models\Sector;
use App\Models\SubsectorItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use App\Traits\Auditable;

class SectorController extends Controller
{
    use Auditable;

    public function __construct()
    {
        $this->middleware('role:ADMIN');
    }

    /**
     * Mostrar lista de sectores
     * Mejora: Métricas, búsqueda, ordenamiento, paginación
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Sector::class);

        $restaurantId = auth()->user()->restaurant_id;

        $query = Sector::where('restaurant_id', $restaurantId)
            ->whereNull('parent_id')
            ->where('type', Sector::TYPE_SECTOR)
            ->withCount(['tables', 'subsectors', 'categories' => function($q) {
                $q->where('is_active', true);
            }])
            ->with(['subsectors']);

        // Búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $allowedSorts = ['name', 'created_at'];
        
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name');
        }

        // Paginación backend
        $sectors = $query->paginate(20)->withQueryString();

        return view('sectors.index', compact('sectors'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create(Request $request)
    {
        Gate::authorize('create', Sector::class);

        $parentId = $request->get('parent_id');
        $parentSector = null;
        if ($parentId) {
            $parentSector = Sector::findOrFail($parentId);
        }

        $sectors = Sector::where('restaurant_id', auth()->user()->restaurant_id)
            ->whereNull('parent_id')
            ->where('type', Sector::TYPE_SECTOR)
            ->orderBy('name')
            ->get();

        return view('sectors.create', compact('parentSector', 'sectors'));
    }

    /**
     * Crear nuevo sector
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Sector::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:sectors,id',
            'type' => 'required|in:SECTOR,SUBSECTOR',
            'capacity' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $validated['restaurant_id'] = auth()->user()->restaurant_id;
        $validated['is_active'] = $request->has('is_active');
        
        // Si es subsector, asegurar que tiene parent_id
        if ($validated['type'] === Sector::TYPE_SUBSECTOR && !$validated['parent_id']) {
            return back()->with('error', 'Un subsector debe tener un sector padre')->withInput();
        }
        
        // Si es sector principal, asegurar que no tiene parent_id
        if ($validated['type'] === Sector::TYPE_SECTOR) {
            $validated['parent_id'] = null;
        }

        $sector = Sector::create($validated);
        
        // Auditoría
        $this->auditCreate($sector, $validated);
        
        // Si es subsector y tiene capacidad, crear los items automáticamente
        if ($sector->isSubsector() && $sector->capacity) {
            for ($i = 1; $i <= $sector->capacity; $i++) {
                SubsectorItem::create([
                    'subsector_id' => $sector->id,
                    'name' => "Lugar {$i}",
                    'position' => $i,
                    'status' => SubsectorItem::STATUS_LIBRE,
                ]);
            }
        }

        if ($sector->isSubsector()) {
            return redirect()->route('sectors.show', $sector->parent_id)
                ->with('success', 'Subsector creado exitosamente');
        }

        return redirect()->route('sectors.index')
            ->with('success', 'Sector creado exitosamente');
    }

    /**
     * Mostrar sector
     */
    public function show(Sector $sector)
    {
        Gate::authorize('view', $sector);

        $sector->load([
            'tables' => function($query) {
                $query->orderBy('number');
            },
            'subsectors.items' => function($query) {
                $query->orderBy('position');
            }
        ]);

        return view('sectors.show', compact('sector'));
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Sector $sector)
    {
        Gate::authorize('update', $sector);

        return view('sectors.edit', compact('sector'));
    }

    /**
     * Actualizar sector
     */
    public function update(Request $request, Sector $sector)
    {
        Gate::authorize('update', $sector);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        
        // Si es subsector y cambió la capacidad, ajustar items
        if ($sector->isSubsector() && isset($validated['capacity'])) {
            $currentItemsCount = $sector->items()->count();
            $newCapacity = $validated['capacity'];
            
            if ($newCapacity > $currentItemsCount) {
                // Agregar items faltantes
                for ($i = $currentItemsCount + 1; $i <= $newCapacity; $i++) {
                    SubsectorItem::create([
                        'subsector_id' => $sector->id,
                        'name' => "Lugar {$i}",
                        'position' => $i,
                        'status' => SubsectorItem::STATUS_LIBRE,
                    ]);
                }
            } elseif ($newCapacity < $currentItemsCount) {
                // Eliminar items sobrantes (solo los que estén libres)
                $itemsToDelete = $sector->items()
                    ->where('position', '>', $newCapacity)
                    ->where('status', SubsectorItem::STATUS_LIBRE)
                    ->get();
                
                foreach ($itemsToDelete as $item) {
                    $item->delete();
                }
            }
        }

        $sector->update($validated);

        return redirect()->route('sectors.index')
            ->with('success', 'Sector actualizado exitosamente');
    }

    /**
     * Eliminar sector
     */
    public function destroy(Sector $sector)
    {
        Gate::authorize('delete', $sector);

        // Verificar que no tenga mesas
        if ($sector->tables()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un sector que tiene mesas asignadas');
        }

        // Verificar que no tenga subsectores
        if ($sector->subsectors()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un sector que tiene subsectores');
        }

        // Auditoría antes de eliminar
        $this->auditDelete($sector);
        
        $parentId = $sector->parent_id;
        $sector->delete();

        if ($sector->isSubsector() && $parentId) {
            return redirect()->route('sectors.show', $parentId)
                ->with('success', 'Subsector eliminado exitosamente');
        }

        return redirect()->route('sectors.index')
            ->with('success', 'Sector eliminado exitosamente');
    }

    /**
     * Crear item en un subsector
     */
    public function storeItem(Request $request, Sector $sector)
    {
        Gate::authorize('update', $sector);

        if (!$sector->isSubsector()) {
            return back()->with('error', 'Solo se pueden agregar items a subsectores');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'nullable|integer|min:1',
        ]);

        $position = $validated['position'] ?? ($sector->items()->max('position') + 1);

        SubsectorItem::create([
            'subsector_id' => $sector->id,
            'name' => $validated['name'],
            'position' => $position,
            'status' => SubsectorItem::STATUS_LIBRE,
        ]);

        return back()->with('success', 'Item agregado exitosamente');
    }

    /**
     * Eliminar item de un subsector
     */
    public function destroyItem(Sector $sector, SubsectorItem $item)
    {
        Gate::authorize('update', $sector);

        if ($item->subsector_id !== $sector->id) {
            return back()->with('error', 'El item no pertenece a este subsector');
        }

        if ($item->status !== SubsectorItem::STATUS_LIBRE) {
            return back()->with('error', 'No se puede eliminar un item que está ocupado');
        }

        $item->delete();

        return back()->with('success', 'Item eliminado exitosamente');
    }
}

