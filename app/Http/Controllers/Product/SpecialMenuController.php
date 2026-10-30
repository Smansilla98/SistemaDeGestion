<?php

declare(strict_types=1);

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Concerns\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\AgendaNotice;
use App\Models\Product;
use App\Models\SpecialMenu;
use App\Services\SpecialMenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Menús especiales de la barra (fecha patria, show, feria): qué se vende, a qué precio
 * y cuánto se prevé vender, con el stock que hace falta.
 */
class SpecialMenuController extends Controller
{
    use OrganizationContext;

    public function __construct(private readonly SpecialMenuService $service) {}

    public function index(): View
    {
        $this->requirePermission('products.view');

        return view('special-menus.index', [
            'menus' => SpecialMenu::query()->where('restaurant_id', $this->org())->withCount('items')->orderByDesc('starts_on')->paginate(20),
            'active' => $this->service->activeFor($this->org()),
            'canEdit' => $this->can('products.update'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requirePermission('products.update');
        $data = $this->validated($request);
        $copyFrom = $request->integer('copy_from') ?: null;

        $menu = DB::transaction(function () use ($data, $request, $copyFrom) {
            $menu = SpecialMenu::query()->create($data + ['restaurant_id' => $this->org(), 'created_by' => $this->user()->id]);
            if ($copyFrom && ($from = SpecialMenu::query()->where('restaurant_id', $this->org())->find($copyFrom))) {
                foreach ($from->items as $item) {
                    $menu->items()->create($item->only(['product_id', 'price', 'planned_quantity']));
                }
            }
            $this->syncNotice($menu, $request->boolean('in_agenda'));

            return $menu;
        });

        return redirect()->route('special-menus.show', $menu)->with('success', 'Menú creado. Elegí los productos, sus precios y cuánto pensás vender.');
    }

    public function show(SpecialMenu $specialMenu): View
    {
        $this->requirePermission('products.view');
        $this->ensureOrg($specialMenu);

        return view('special-menus.show', [
            'menu' => $specialMenu->load('items.product.category', 'notice'),
            'products' => Product::query()->where('restaurant_id', $this->org())->where('type', 'PRODUCT')->where('is_active', true)
                ->with('category')->orderBy('name')->get()->groupBy(fn (Product $p) => $p->category?->name ?? 'Sin categoría'),
            'plan' => $this->service->stockPlan($specialMenu),
            'canEdit' => $this->can('products.update'),
        ]);
    }

    public function update(Request $request, SpecialMenu $specialMenu): RedirectResponse
    {
        $this->requirePermission('products.update');
        $this->ensureOrg($specialMenu);
        $specialMenu->update($this->validated($request, $specialMenu));
        $this->syncNotice($specialMenu, $request->boolean('in_agenda'));

        return back()->with('success', 'Menú guardado.');
    }

    public function saveItems(Request $request, SpecialMenu $specialMenu): RedirectResponse
    {
        $this->requirePermission('products.update');
        $this->ensureOrg($specialMenu);
        $data = $request->validate([
            'include' => ['nullable', 'array'], 'include.*' => ['nullable', 'boolean'],
            'price' => ['nullable', 'array'], 'price.*' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'planned' => ['nullable', 'array'], 'planned.*' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ]);
        $ids = array_map('intval', array_keys(array_filter($data['include'] ?? [])));
        $valid = Product::query()->where('restaurant_id', $this->org())->whereIn('id', $ids)->pluck('id')->all();

        DB::transaction(function () use ($specialMenu, $valid, $data) {
            $specialMenu->items()->whereNotIn('product_id', $valid)->delete();
            foreach ($valid as $id) {
                $price = $data['price'][$id] ?? null;
                $planned = $data['planned'][$id] ?? null;
                $specialMenu->items()->updateOrCreate(['product_id' => $id], [
                    'price' => $price === null || $price === '' ? null : $price,
                    'planned_quantity' => $planned === null || $planned === '' ? null : (int) $planned,
                ]);
            }
        });

        return back()->with('success', count($valid).' productos en el menú.');
    }

    public function destroy(SpecialMenu $specialMenu): RedirectResponse
    {
        $this->requirePermission('products.update');
        $this->ensureOrg($specialMenu);
        $specialMenu->notice?->delete();
        $specialMenu->delete();

        return redirect()->route('special-menus.index')->with('success', 'Menú eliminado.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?SpecialMenu $current = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'exclusive' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [], ['name' => 'nombre', 'starts_on' => 'desde', 'ends_on' => 'hasta']);
        $data['exclusive'] = (bool) ($data['exclusive'] ?? false);

        // Dos menús a la vez confunden a la barra: no se superponen.
        $overlap = SpecialMenu::query()->where('restaurant_id', $this->org())
            ->when($current, fn ($q) => $q->whereKeyNot($current->id))
            ->whereDate('starts_on', '<=', $data['ends_on'])->whereDate('ends_on', '>=', $data['starts_on'])->first();
        if ($overlap) {
            throw ValidationException::withMessages(['starts_on' => "Se superpone con «{$overlap->name}» ({$overlap->starts_on->format('d/m')} al {$overlap->ends_on->format('d/m')})."]);
        }

        return $data;
    }

    /** Aviso de fecha especial en la agenda, para que todo el equipo lo vea. */
    private function syncNotice(SpecialMenu $menu, bool $inAgenda): void
    {
        if (! $inAgenda) {
            $menu->notice?->delete();
            $menu->forceFill(['agenda_notice_id' => null])->saveQuietly();

            return;
        }
        $attrs = ['restaurant_id' => $menu->restaurant_id, 'kind' => 'FECHA_ESPECIAL', 'title' => 'Menú especial: '.$menu->name,
            'body' => $menu->notes, 'date' => $menu->starts_on, 'end_date' => $menu->ends_on->ne($menu->starts_on) ? $menu->ends_on : null];
        if ($menu->notice) {
            $menu->notice->update($attrs);
        } else {
            $notice = AgendaNotice::query()->create($attrs + ['created_by' => $this->user()->id]);
            $menu->forceFill(['agenda_notice_id' => $notice->id])->saveQuietly();
        }
    }
}
