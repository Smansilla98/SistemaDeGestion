<?php

declare(strict_types=1);

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ResolveActiveRestaurant;
use App\Models\Restaurant;
use App\Services\BranchService;
use App\Support\BranchAccess;
use App\Support\HomeRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(private BranchAccess $access) {}

    /**
     * Paso posterior al login: elegir en qué sucursal se va a operar.
     */
    public function select(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $branches = $this->access->branchesFor($user);

        if ($branches->count() <= 1) {
            return redirect()->route(HomeRoute::for($user));
        }

        return view('branches.select', [
            'branches' => $branches,
            'currentId' => (int) $request->session()->get(ResolveActiveRestaurant::SESSION_KEY, 0),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'restaurant_id' => ['required', 'integer'],
        ]);

        $user = $request->user();
        $restaurantId = (int) $validated['restaurant_id'];

        if (! $this->access->canAccess($user, $restaurantId)) {
            return back()->withErrors(['restaurant_id' => 'No tenés acceso a esa sucursal.']);
        }

        $request->session()->put(ResolveActiveRestaurant::SESSION_KEY, $restaurantId);
        $user->useRestaurant($restaurantId);

        return redirect()->route(HomeRoute::for($user));
    }

    /**
     * Administración de sucursales del grupo.
     */
    public function index(Request $request): View
    {
        $current = Restaurant::findOrFail($request->user()->restaurant_id);
        $rootId = $current->groupRootId();

        $branches = Restaurant::query()
            ->where(fn ($q) => $q->where('id', $rootId)->orWhere('parent_id', $rootId))
            ->withCount('users')
            ->orderByRaw('parent_id IS NOT NULL')
            ->orderBy('name')
            ->get();

        return view('branches.index', compact('branches', 'current'));
    }

    public function create(Request $request, BranchService $service): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'copy_catalog' => ['sometimes', 'boolean'],
        ]);

        $source = Restaurant::findOrFail($request->user()->restaurant_id);
        $branch = $service->createBranch($source, $validated, $request->boolean('copy_catalog'));

        return redirect()->route('branches.index')
            ->with('success', "Sucursal «{$branch->name}» creada. Cargá su stock con una puesta a punto.");
    }

    public function update(Request $request, Restaurant $branch): RedirectResponse
    {
        if (! $this->access->canAccess($request->user(), (int) $branch->id)
            && ! $this->belongsToCurrentGroup($request, $branch)) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $isActive = $request->boolean('is_active');
        if (! $isActive && (int) $branch->id === (int) $request->user()->restaurant_id) {
            return back()->with('error', 'No podés desactivar la sucursal en la que estás operando.');
        }

        $branch->update([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'is_active' => $isActive,
        ]);

        return redirect()->route('branches.index')->with('success', 'Sucursal actualizada.');
    }

    /**
     * Las sucursales inactivas no entran en BranchAccess, pero el admin del grupo
     * tiene que poder reactivarlas.
     */
    private function belongsToCurrentGroup(Request $request, Restaurant $branch): bool
    {
        $current = Restaurant::find($request->user()->restaurant_id);

        return $current !== null && $branch->groupRootId() === $current->groupRootId();
    }
}
