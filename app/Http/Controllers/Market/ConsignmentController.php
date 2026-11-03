<?php

namespace App\Http\Controllers\Market;

use App\Http\Controllers\Concerns\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\Consignment;
use App\Models\ConsignmentItem;
use App\Models\Entrepreneur;
use App\Services\Finance\ActivityFinanceService;
use App\Services\Market\ConsignmentService;
use App\Services\Market\MarketStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConsignmentController extends Controller
{
    use OrganizationContext;

    public function __construct(private readonly ConsignmentService $service) {}

    public function index(Request $request): View
    {
        $this->requirePermission('market.view');
        $q = Consignment::query()->where('restaurant_id', $this->org())->with('entrepreneur')->latest('received_on');
        if (($own = $this->ownEntrepreneurId()) !== null) {
            $q->where('entrepreneur_id', $own);
        }
        $q->where('status', $request->input('estado', 'ABIERTA') === 'CERRADA' ? 'CERRADA' : 'ABIERTA');
        $rows = $q->get()->map(fn ($c) => ['c' => $c, 'b' => $this->service->balance($c)]);

        return view('market.consignments.index', [
            'rows' => $rows, 'status' => $request->input('estado', 'ABIERTA'),
            'totalOwed' => $rows->sum(fn ($r) => $r['b']['owed_cents']),
            'canCreate' => $this->can('market.create'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->requirePermission('market.create');
        $entrepreneurs = Entrepreneur::query()->where('restaurant_id', $this->org())->where('is_active', true)
            ->with(['products' => fn ($p) => $p->where('is_active', true)->orderBy('name')])->orderBy('business_name')->get();

        return view('market.consignments.create', ['entrepreneurs' => $entrepreneurs, 'selected' => (int) $request->input('emprendedor')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requirePermission('market.create');
        $data = $request->validate([
            'entrepreneur_id' => ['required', 'integer', Rule::exists('entrepreneurs', 'id')->where('restaurant_id', $this->org())],
            'received_on' => ['required', 'date'], 'commission_percent' => ['required', 'numeric', 'between:0,100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array'], 'items.*.market_product_id' => ['required', 'integer'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'], 'items.*.price' => ['nullable', 'string', 'max:20'],
        ]);
        $items = collect($data['items'])->map(fn ($i) => [
            'market_product_id' => (int) $i['market_product_id'], 'quantity' => (float) ($i['quantity'] ?? 0),
            'unit_price_cents' => filled($i['price'] ?? null) ? ActivityFinanceService::toCents($i['price']) : null,
        ])->all();
        $consignment = $this->service->receive(Entrepreneur::query()->findOrFail($data['entrepreneur_id']), $data['received_on'],
            (float) $data['commission_percent'], $items, $this->user(), $data['notes'] ?? null);

        return redirect()->route('market.consignments.show', $consignment)->with('success', 'Consignación registrada y stock actualizado.');
    }

    public function show(Consignment $consignment): View
    {
        $this->ensureOrg($consignment);
        $this->requirePermission('market.view');
        $own = $this->ownEntrepreneurId();
        abort_if($own !== null && $own !== $consignment->entrepreneur_id, 403);

        return view('market.consignments.show', [
            'consignment' => $consignment->load(['entrepreneur', 'items.product', 'settlements']),
            'balance' => $this->service->balance($consignment),
            'canMove' => $this->can('market.update'),
            'canSettle' => $this->can('market.settle'),
        ]);
    }

    public function returnItem(Request $request, Consignment $consignment, ConsignmentItem $item, MarketStockService $stock): RedirectResponse
    {
        $this->ensureOrg($consignment);
        $this->requirePermission('market.update');
        abort_unless($item->consignment_id === $consignment->id, 404);
        $data = $request->validate(['quantity' => ['required', 'numeric', 'gt:0'], 'note' => ['nullable', 'string', 'max:255']]);
        $stock->returnToEntrepreneur($item, (float) $data['quantity'], $this->user(), $data['note'] ?? 'Devolución');
        $this->service->closeIfDone($consignment->fresh());

        return back()->with('success', 'Devolución registrada.');
    }

    public function settle(Request $request, Consignment $consignment): RedirectResponse
    {
        $this->ensureOrg($consignment);
        $this->requirePermission('market.settle');
        $data = $request->validate(['paid_on' => ['required', 'date'], 'method' => ['nullable', 'string', 'max:30'], 'note' => ['nullable', 'string', 'max:255']]);
        $s = $this->service->settle($consignment, $data['paid_on'], $data['method'] ?? null, $data['note'] ?? null, $this->user());

        return back()->with('success', 'Liquidación registrada: '.ActivityFinanceService::money($s->paid_cents).'.');
    }
}
