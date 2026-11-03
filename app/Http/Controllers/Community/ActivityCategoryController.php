<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Concerns\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\ActivityCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Categorías de actividades (Música, Teatro, Feria…) para clasificar talleres, eventos y avisos
 * y filtrar la agenda.
 */
class ActivityCategoryController extends Controller
{
    use OrganizationContext;

    public function index(): View
    {
        $this->requirePermission('agenda.create');

        return view('agenda.categories', [
            'categories' => ActivityCategory::query()->where('restaurant_id', $this->org())->orderBy('name')->get()
                ->map(fn ($c) => ['c' => $c, 'uses' => \App\Models\Workshop::query()->where('activity_category_id', $c->id)->count()
                    + \App\Models\Event::query()->where('activity_category_id', $c->id)->count()
                    + \App\Models\AgendaNotice::query()->where('activity_category_id', $c->id)->count()]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requirePermission('agenda.create');
        ActivityCategory::query()->create($this->validated($request) + ['restaurant_id' => $this->org()]);

        return back()->with('success', 'Categoría creada.');
    }

    public function update(Request $request, ActivityCategory $category): RedirectResponse
    {
        $this->ensureOrg($category);
        $this->requirePermission('agenda.create');
        $category->update($this->validated($request, $category));

        return back()->with('success', 'Categoría actualizada.');
    }

    public function destroy(ActivityCategory $category): RedirectResponse
    {
        $this->ensureOrg($category);
        $this->requirePermission('agenda.create');
        $category->delete(); // talleres, eventos y avisos quedan sin categoría

        return back()->with('success', 'Categoría eliminada.');
    }

    private function validated(Request $request, ?ActivityCategory $current = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('activity_categories')->where('restaurant_id', $this->org())->ignore($current?->id)],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
    }
}
