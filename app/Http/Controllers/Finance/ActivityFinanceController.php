<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Concerns\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\ActivityEntry;
use App\Models\Contact;
use App\Models\DistributionRule;
use App\Models\Event;
use App\Models\Workshop;
use App\Services\Finance\ActivityFinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Finanzas de talleres y eventos: reglas de reparto, libro de ingresos/gastos y pagos de repartos.
 */
class ActivityFinanceController extends Controller
{
    use OrganizationContext;

    public function __construct(private readonly ActivityFinanceService $service) {}

    public function index(): View
    {
        $this->requirePermission('finance.view');
        $rows = Workshop::query()->where('restaurant_id', $this->org())->with('distributionRule')->orderBy('name')->get()
            ->map(fn ($w) => ['kind' => 'Taller', 'model' => $w, 'url' => route('finance.activity', ['workshop', $w->id]), 's' => $this->service->settlement($w)])
            ->concat(Event::query()->where('restaurant_id', $this->org())->with('distributionRule')->latest('date')->limit(50)->get()
                ->map(fn ($e) => ['kind' => 'Evento', 'model' => $e, 'url' => route('finance.activity', ['event', $e->id]), 's' => $this->service->settlement($e)]));

        return view('finance.index', [
            'rows' => $rows,
            'rules' => DistributionRule::query()->where('restaurant_id', $this->org())->orderBy('name')->get(),
            'canCreate' => $this->can('finance.create'),
        ]);
    }

    public function storeRule(Request $request): RedirectResponse
    {
        $this->requirePermission('finance.create');
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'base' => ['required', Rule::in(array_keys(DistributionRule::BASES))],
            'parties' => ['required', 'array', 'max:6']]);
        DistributionRule::query()->create(['restaurant_id' => $this->org(), 'name' => $data['name'], 'base' => $data['base'],
            'parties' => ActivityFinanceService::normalizeParties($data['parties'])]);

        return back()->with('success', 'Regla de reparto creada.');
    }

    public function show(string $type, int $id): View
    {
        $this->requirePermission('finance.view');
        $activity = $this->activity($type, $id);

        return view('finance.activity', [
            'type' => $type, 'activity' => $activity,
            'settlement' => $this->service->settlement($activity),
            'entries' => $activity->entries()->with(['contact', 'stage'])->latest('date')->latest('id')->get(),
            'payouts' => $activity->payouts()->latest('paid_on')->get(),
            'enrollments' => $activity instanceof Workshop ? $activity->enrollments()->with('contact')->whereIn('status', ['ACTIVA', 'FINALIZADA'])->get() : collect(),
            'balances' => $activity instanceof Workshop ? $this->service->enrollmentBalances($activity) : [],
            'stages' => $activity instanceof Event ? $this->service->stagesSummary($activity) : null,
            'rules' => DistributionRule::query()->where('restaurant_id', $this->org())->orderBy('name')->get(),
            'contacts' => Contact::query()->where('restaurant_id', $this->org())->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'canCreate' => $this->can('finance.create'), 'canSettle' => $this->can('finance.settle'),
        ]);
    }

    public function setRule(Request $request, string $type, int $id): RedirectResponse
    {
        $this->requirePermission('finance.create');
        $activity = $this->activity($type, $id);
        $data = $request->validate(['distribution_rule_id' => ['nullable', 'integer', Rule::exists('distribution_rules', 'id')->where('restaurant_id', $this->org())]]);
        $activity->update(['distribution_rule_id' => $data['distribution_rule_id'] ?? null]);

        return back()->with('success', 'Regla de reparto asignada.');
    }

    public function storeEntry(Request $request, string $type, int $id): RedirectResponse
    {
        $this->requirePermission('finance.create');
        $activity = $this->activity($type, $id);
        $data = $request->validate([
            'type' => ['required', Rule::in(['INGRESO', 'GASTO'])], 'concept' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'string', 'max:20'], 'date' => ['required', 'date'], 'method' => ['nullable', 'string', 'max:30'],
            'contact_id' => ['nullable', 'integer', Rule::exists('contacts', 'id')->where('restaurant_id', $this->org())],
            'workshop_enrollment_id' => ['nullable', 'integer'], 'event_stage_id' => ['nullable', 'integer'],
        ]);
        $this->service->record($activity, $data['type'], $data['concept'], ActivityFinanceService::toCents($data['amount']), $data['date'], $this->user(),
            array_filter(['contact_id' => $data['contact_id'] ?? null, 'workshop_enrollment_id' => $data['workshop_enrollment_id'] ?? null,
                'event_stage_id' => $data['event_stage_id'] ?? null, 'method' => $data['method'] ?? null]));

        return back()->with('success', ($data['type'] === 'INGRESO' ? 'Ingreso' : 'Gasto').' registrado.');
    }

    public function voidEntry(Request $request, ActivityEntry $entry): RedirectResponse
    {
        $this->ensureOrg($entry);
        $this->requirePermission('finance.settle');
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $this->service->void($entry, $data['reason']);

        return back()->with('success', 'Movimiento anulado (queda en el historial).');
    }

    public function storePayout(Request $request, string $type, int $id): RedirectResponse
    {
        $this->requirePermission('finance.settle');
        $activity = $this->activity($type, $id);
        $data = $request->validate(['party' => ['required', 'string', 'max:60'], 'amount' => ['required', 'string', 'max:20'],
            'paid_on' => ['required', 'date'], 'note' => ['nullable', 'string', 'max:255']]);
        $this->service->payout($activity, $data['party'], ActivityFinanceService::toCents($data['amount']), $data['paid_on'], $this->user(), $data['note'] ?? null);

        return back()->with('success', "Pago a «{$data['party']}» registrado.");
    }

    private function activity(string $type, int $id): Workshop|Event
    {
        $model = match ($type) {
            'workshop' => Workshop::query()->where('restaurant_id', $this->org())->findOrFail($id),
            'event' => Event::query()->where('restaurant_id', $this->org())->findOrFail($id),
            default => abort(404),
        };

        return $model;
    }
}
