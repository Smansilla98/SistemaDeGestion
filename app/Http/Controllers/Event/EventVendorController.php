<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Concerns\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\Entrepreneur;
use App\Models\Event;
use App\Models\EventVendor;
use App\Services\Finance\ActivityFinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Quién vende qué en un evento: lo que vende la casa (con su stock) y los puestos de emprendedores
 * que venden lo suyo y le dejan un porcentaje al espacio. De los puestos no se carga stock: al final
 * se anota cuánto vendieron y se cobra la comisión, que entra como ingreso del evento.
 */
class EventVendorController extends Controller
{
    use OrganizationContext;

    public function houseSells(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeEvent($event);
        $data = $request->validate(['house_sells' => ['nullable', 'array'], 'house_sells.*' => [Rule::in(array_keys(EventVendor::CATEGORIES))]]);
        $event->update(['house_sells' => array_values(array_unique($data['house_sells'] ?? []))]);

        return back()->with('success', 'Guardado qué vende la casa.');
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeEvent($event);
        $data = $this->validated($request);
        $event->vendors()->create($data + ['restaurant_id' => $event->restaurant_id]);

        return back()->with('success', 'Puesto agregado.');
    }

    public function update(Request $request, Event $event, EventVendor $vendor): RedirectResponse
    {
        $this->authorizeVendor($event, $vendor);
        $data = $this->validated($request, $vendor);
        if (array_key_exists('sales_cents', $data) && $data['sales_cents'] !== null
            && (int) round($data['sales_cents'] * $data['percent'] / 100) < $vendor->collected_cents) {
            throw ValidationException::withMessages(['sales' => 'Ya se cobró más que la comisión de esa venta: anulá el cobro en «Pagos y reparto» primero.']);
        }
        $vendor->update($data);

        return back()->with('success', 'Puesto actualizado.');
    }

    /** Cobra la comisión pendiente del puesto y la registra como ingreso del evento. */
    public function collect(Request $request, Event $event, EventVendor $vendor, ActivityFinanceService $finance): RedirectResponse
    {
        $this->authorizeVendor($event, $vendor);
        $data = $request->validate(['method' => ['nullable', 'string', 'max:30']]);

        $amount = DB::transaction(function () use ($event, $vendor, $finance, $data) {
            $locked = EventVendor::query()->whereKey($vendor->id)->lockForUpdate()->firstOrFail();
            $amount = $locked->pendingCents();
            if ($locked->sales_cents === null) {
                throw ValidationException::withMessages(['sales' => 'Primero anotá cuánto vendió el puesto.']);
            }
            if ($amount <= 0) {
                throw ValidationException::withMessages(['sales' => 'No hay comisión pendiente de cobrar.']);
            }
            $finance->record($event, 'INGRESO', sprintf('Comisión %s (%s%% de %s)', $locked->name,
                rtrim(rtrim(number_format($locked->percent, 2, ',', ''), '0'), ','), ActivityFinanceService::money((int) $locked->sales_cents)),
                $amount, today()->toDateString(), $this->user(), ['method' => $data['method'] ?? null]);
            $locked->update(['collected_cents' => $locked->collected_cents + $amount]);

            return $amount;
        });

        return back()->with('success', 'Cobrada la comisión: '.ActivityFinanceService::money($amount).'. Quedó como ingreso del evento.');
    }

    public function destroy(Event $event, EventVendor $vendor): RedirectResponse
    {
        $this->authorizeVendor($event, $vendor);
        if ($vendor->collected_cents > 0) {
            return back()->with('error', 'Ese puesto ya tiene una comisión cobrada: no se puede quitar.');
        }
        $vendor->delete();

        return back()->with('success', 'Puesto quitado.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?EventVendor $vendor = null): array
    {
        $data = $request->validate([
            'entrepreneur_id' => ['nullable', 'integer', Rule::exists('entrepreneurs', 'id')->where('restaurant_id', $this->org())],
            'name' => ['nullable', 'string', 'max:255', 'required_without:entrepreneur_id'],
            'category' => ['required', Rule::in(array_keys(EventVendor::CATEGORIES))],
            'percent' => ['required', 'numeric', 'between:0,100'],
            'sales' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], ['name.required_without' => 'Elegí un emprendedor o escribí el nombre del puesto.']);

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '' && ! empty($data['entrepreneur_id'])) {
            $name = (string) Entrepreneur::query()->whereKey($data['entrepreneur_id'])->value('business_name');
        }
        $out = ['entrepreneur_id' => $data['entrepreneur_id'] ?? null, 'name' => $name, 'category' => $data['category'],
            'percent' => (float) $data['percent'], 'notes' => $data['notes'] ?? null];
        if ($request->has('sales') || $vendor === null) {
            $out['sales_cents'] = filled($data['sales'] ?? null) ? ActivityFinanceService::toCents($data['sales']) : null;
        }

        return $out;
    }

    private function authorizeEvent(Event $event): void
    {
        $this->ensureOrg($event);
        $this->requirePermission('events.update');
    }

    private function authorizeVendor(Event $event, EventVendor $vendor): void
    {
        $this->authorizeEvent($event);
        abort_unless($vendor->event_id === $event->id, 404);
    }
}
