<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Concerns\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventStage;
use App\Services\Finance\ActivityFinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Etapas de un evento: responsable, fecha, presupuesto y estado.
 */
class EventStageController extends Controller
{
    use OrganizationContext;

    public function store(Request $request, Event $event): RedirectResponse
    {
        $this->ensureOrg($event);
        $this->requirePermission('events.update');
        $data = $this->validated($request);
        $event->stages()->create($data + ['position' => (int) $event->stages()->max('position') + 1]);

        return back()->with('success', 'Etapa agregada.');
    }

    public function update(Request $request, Event $event, EventStage $stage): RedirectResponse
    {
        $this->ensureOrg($event);
        $this->requirePermission('events.update');
        abort_unless($stage->event_id === $event->id, 404);
        $stage->update($this->validated($request));

        return back()->with('success', 'Etapa actualizada.');
    }

    public function destroy(Event $event, EventStage $stage): RedirectResponse
    {
        $this->ensureOrg($event);
        $this->requirePermission('events.update');
        abort_unless($stage->event_id === $event->id, 404);
        if ($stage->entries()->whereNull('voided_at')->exists()) {
            return back()->with('error', 'La etapa tiene pagos registrados: anulalos primero.');
        }
        $stage->delete();

        return back()->with('success', 'Etapa eliminada.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'starts_at' => ['nullable', 'date'],
            'responsible_contact_id' => ['nullable', 'integer', Rule::exists('contacts', 'id')->where('restaurant_id', $this->org())],
            'budget' => ['nullable', 'string', 'max:20'], 'status' => ['required', Rule::in(array_keys(EventStage::STATUSES))],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);
        $data['budget_cents'] = ActivityFinanceService::toCents($data['budget'] ?? 0);
        unset($data['budget']);

        return $data;
    }
}
