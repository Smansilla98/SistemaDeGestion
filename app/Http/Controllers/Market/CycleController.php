<?php

namespace App\Http\Controllers\Market;

use App\Http\Controllers\Concerns\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\CycleOrder;
use App\Models\MarketProduct;
use App\Models\OrderCycle;
use App\Services\Finance\ActivityFinanceService;
use App\Services\Market\OrderCycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CycleController extends Controller
{
    use OrganizationContext;

    public function __construct(private readonly OrderCycleService $service) {}

    public function index(): View
    {
        $this->requirePermission('market.view');

        return view('market.cycles.index', [
            'cycles' => OrderCycle::query()->where('restaurant_id', $this->org())->withCount(['orders as orders_count' => fn ($q) => $q->where('status', '!=', 'CANCELADO')])
                ->latest('delivery_on')->paginate(20),
            'canCreate' => $this->can('market.create'),
            'previous' => OrderCycle::query()->where('restaurant_id', $this->org())->has('offer')->latest('delivery_on')->limit(10)->get(['id', 'name', 'delivery_on']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requirePermission('market.create');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'opens_at' => ['required', 'date'], 'closes_at' => ['required', 'date', 'after:opens_at'],
            'delivery_on' => ['required', 'date', 'after_or_equal:closes_at'], 'notes' => ['nullable', 'string', 'max:2000'],
            'copy_from' => ['nullable', 'integer'],
        ]);
        $from = ! empty($data['copy_from']) ? OrderCycle::query()->where('restaurant_id', $this->org())->findOrFail($data['copy_from']) : null;
        unset($data['copy_from']);
        $cycle = OrderCycle::query()->create($data + ['restaurant_id' => $this->org(), 'status' => 'ABIERTO']);
        $copied = $from ? $this->service->copyOffer($from, $cycle) : 0;

        return redirect()->route('market.cycles.show', $cycle)->with('success', $copied
            ? "Ciclo creado con los {$copied} productos de «{$from->name}». Revisá precios y cupos."
            : 'Ciclo creado. Cargá los productos que ofrece antes de tomar pedidos.');
    }

    public function show(OrderCycle $cycle): View
    {
        $this->ensureOrg($cycle);
        $this->requirePermission('market.view');

        return view('market.cycles.show', [
            'cycle' => $cycle,
            'orders' => $cycle->orders()->with(['contact', 'items.product'])->orderByRaw("FIELD(status, 'PENDIENTE', 'ENTREGADO', 'CANCELADO')")->get(),
            'consolidated' => $this->service->consolidated($cycle),
            'offer' => $cycle->offer()->with('product.entrepreneur')->get()->sortBy(fn ($o) => ($o->product?->category ?? '').$o->product?->name)->values(),
            'taken' => $this->service->takenQuantities($cycle),
            'products' => MarketProduct::query()->where('restaurant_id', $this->org())->where('is_active', true)->with('entrepreneur')->orderBy('category')->orderBy('name')->get(),
            'contacts' => Contact::query()->where('restaurant_id', $this->org())->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'canManage' => $this->can('market.update'),
        ]);
    }

    public function updateStatus(Request $request, OrderCycle $cycle): RedirectResponse
    {
        $this->ensureOrg($cycle);
        $this->requirePermission('market.update');
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(OrderCycle::STATUSES))]]);
        $cycle->update($data);

        return back()->with('success', 'Ciclo: '.OrderCycle::STATUSES[$data['status']].'.');
    }

    public function saveOffer(Request $request, OrderCycle $cycle): RedirectResponse
    {
        $this->ensureOrg($cycle);
        $this->requirePermission('market.update');
        abort_if($cycle->status === 'ENTREGADO', 422, 'El ciclo ya se entregó.');
        $data = $request->validate([
            'offer' => ['nullable', 'array'], 'offer.*' => ['nullable', 'boolean'],
            'price' => ['nullable', 'array'], 'price.*' => ['nullable', 'string', 'max:20'],
            'max' => ['nullable', 'array'], 'max.*' => ['nullable', 'numeric', 'min:0'],
        ]);
        $items = [];
        foreach (array_keys(array_filter($data['offer'] ?? [])) as $id) {
            $price = $data['price'][$id] ?? null;
            $items[(int) $id] = ['price' => filled($price) ? ActivityFinanceService::toCents((string) $price) : null,
                'max' => isset($data['max'][$id]) && $data['max'][$id] !== '' ? (float) $data['max'][$id] : null];
        }
        $this->service->saveOffer($cycle, $items);

        return back()->with('success', count($items).' productos en la oferta del ciclo.');
    }

    public function storeOrder(Request $request, OrderCycle $cycle): RedirectResponse
    {
        $this->ensureOrg($cycle);
        $this->requirePermission('market.update');
        $data = $request->validate([
            'contact_id' => ['nullable', 'integer'], 'new_name' => ['nullable', 'string', 'max:255'], 'new_phone' => ['nullable', 'string', 'max:40'],
            'qty' => ['required', 'array'], 'qty.*' => ['nullable', 'numeric', 'min:0'], 'note' => ['nullable', 'string', 'max:255'],
        ]);
        if (! empty($data['contact_id'])) {
            $contact = Contact::query()->where('restaurant_id', $this->org())->findOrFail($data['contact_id']);
        } else {
            $request->validate(['new_name' => ['required']], ['new_name.required' => 'Elegí un contacto o escribí el nombre.']);
            $contact = Contact::query()->create(['restaurant_id' => $this->org(), 'kind' => 'CLIENTE', 'name' => $data['new_name'], 'phone' => $data['new_phone'] ?? null]);
        }
        $order = $this->service->placeOrder($cycle, $contact, $data['qty'], true, $data['note'] ?? null);

        return back()->with('success', "Pedido de {$contact->name}: ".ActivityFinanceService::money($order->total_cents).'.');
    }

    public function deliver(Request $request, OrderCycle $cycle, CycleOrder $order): RedirectResponse
    {
        $this->ensureOrg($cycle);
        $this->requirePermission('market.update');
        abort_unless($order->order_cycle_id === $cycle->id, 404);
        $data = $request->validate(['paid' => ['nullable', 'string', 'max:20']]);
        $this->service->deliver($order, $this->user(), filled($data['paid'] ?? null) ? ActivityFinanceService::toCents($data['paid']) : null);

        return back()->with('success', 'Pedido entregado y stock descontado.');
    }

    public function cancelOrder(OrderCycle $cycle, CycleOrder $order): RedirectResponse
    {
        $this->ensureOrg($cycle);
        $this->requirePermission('market.update');
        abort_unless($order->order_cycle_id === $cycle->id, 404);
        $this->service->cancel($order);

        return back()->with('success', 'Pedido cancelado.');
    }
}
