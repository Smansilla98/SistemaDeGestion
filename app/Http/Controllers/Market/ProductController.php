<?php

namespace App\Http\Controllers\Market;

use App\Http\Controllers\Concerns\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\Entrepreneur;
use App\Models\MarketProduct;
use App\Services\Finance\ActivityFinanceService;
use App\Services\Market\MarketStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    use OrganizationContext;

    public function index(Request $request): View
    {
        $this->requirePermission('market.view');
        $q = MarketProduct::query()->where('restaurant_id', $this->org())->with('entrepreneur')->orderBy('category')->orderBy('name');
        if (($own = $this->ownEntrepreneurId()) !== null) {
            $q->where('entrepreneur_id', $own);
        }
        if ($request->filled('entrepreneur_id')) {
            $q->where('entrepreneur_id', (int) $request->input('entrepreneur_id'));
        }
        if ($request->filled('q')) {
            $q->where('name', 'like', '%'.$request->string('q')->trim().'%');
        }
        if ($request->boolean('bajo')) {
            $q->whereNotNull('min_stock')->whereColumn('stock_quantity', '<=', 'min_stock');
        }

        return view('market.products.index', [
            'products' => $q->paginate(40)->withQueryString(),
            'entrepreneurs' => Entrepreneur::query()->where('restaurant_id', $this->org())->orderBy('business_name')->get(['id', 'business_name']),
            'canCreate' => $this->can('market.create'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requirePermission('market.create');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'category' => ['nullable', 'string', 'max:60'], 'unit' => ['required', 'string', 'max:20'],
            'price' => ['required', 'string', 'max:20'], 'min_stock' => ['nullable', 'numeric', 'min:0'],
            'entrepreneur_id' => ['nullable', 'integer', Rule::exists('entrepreneurs', 'id')->where('restaurant_id', $this->org())],
        ]);
        $product = MarketProduct::query()->create([
            'restaurant_id' => $this->org(), 'entrepreneur_id' => $data['entrepreneur_id'] ?? null, 'name' => $data['name'],
            'category' => $data['category'] ?? null, 'unit' => $data['unit'], 'price_cents' => ActivityFinanceService::toCents($data['price']),
            'min_stock' => $data['min_stock'] ?? null, 'is_active' => true,
        ]);

        return redirect()->route('market.products.show', $product)->with('success', 'Producto creado. Cargá el stock con un ingreso o una consignación.');
    }

    public function show(MarketProduct $product): View
    {
        $this->ensureOrg($product);
        $this->requirePermission('market.view');
        $own = $this->ownEntrepreneurId();
        abort_if($own !== null && $own !== $product->entrepreneur_id, 403);

        return view('market.products.show', [
            'product' => $product->load('entrepreneur'),
            'movements' => $product->movements()->with('user')->paginate(30),
            'canMove' => $this->can('market.update'),
        ]);
    }

    public function update(Request $request, MarketProduct $product): RedirectResponse
    {
        $this->ensureOrg($product);
        $this->requirePermission('market.update');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'category' => ['nullable', 'string', 'max:60'], 'unit' => ['required', 'string', 'max:20'],
            'price' => ['required', 'string', 'max:20'], 'min_stock' => ['nullable', 'numeric', 'min:0'], 'is_active' => ['nullable', 'boolean'],
        ]);
        $product->update(['name' => $data['name'], 'category' => $data['category'] ?? null, 'unit' => $data['unit'],
            'price_cents' => ActivityFinanceService::toCents($data['price']), 'min_stock' => $data['min_stock'] ?? null, 'is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Producto actualizado.');
    }

    public function move(Request $request, MarketProduct $product, MarketStockService $stock): RedirectResponse
    {
        $this->ensureOrg($product);
        $this->requirePermission('market.update');
        $data = $request->validate([
            'type' => ['required', Rule::in(['INGRESO', 'VENTA', 'AJUSTE_MAS', 'AJUSTE_MENOS'])],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:1000000'], 'note' => ['nullable', 'string', 'max:255'],
        ]);
        $qty = (float) $data['quantity'];
        match ($data['type']) {
            'VENTA' => $stock->sell($product, $qty, $this->user(), $data['note'] ?? null),
            'INGRESO' => $stock->move($product, 'INGRESO', $qty, $this->user(), $data['note'] ?? null),
            'AJUSTE_MAS' => $stock->move($product, 'AJUSTE', $qty, $this->user(), $data['note'] ?? 'Ajuste'),
            default => $stock->move($product, 'AJUSTE', -$qty, $this->user(), $data['note'] ?? 'Ajuste'), // AJUSTE_MENOS
        };

        return back()->with('success', 'Movimiento registrado.');
    }
}
