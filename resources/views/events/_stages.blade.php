{{-- Etapas del evento: responsable, fecha, presupuesto, pagado y saldo. --}}
@php
    $perm = app(\App\Services\PermissionService::class);
    $canStages = $perm->allowed(auth()->user(), 'events.update');
    $stageSummary = collect(app(\App\Services\Finance\ActivityFinanceService::class)->stagesSummary($event)['stages'])->keyBy('id');
    $stageList = $event->stages()->with('responsible')->get();
    $stageContacts = $canStages ? \App\Models\Contact::query()->where('restaurant_id', $event->restaurant_id)->where('is_active', true)->orderBy('name')->get(['id', 'name']) : collect();
    $money = fn ($c) => \App\Services\Finance\ActivityFinanceService::money((int) $c);
    $financeOn = app(\App\Services\ModuleLicenseService::class)->enabledForUser(auth()->user(), 'finance') && $perm->allowed(auth()->user(), 'finance.view');
@endphp
@if($canStages || $stageList->isNotEmpty())
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Etapas, responsables y presupuesto</h5>
        @if($financeOn)<a href="{{ route('finance.activity', ['event', $event->id]) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pie-chart"></i> Pagos y reparto</a>@endif
    </div>
    <div class="table-responsive"><table class="table mb-0 align-middle small">
        <thead><tr><th>Etapa</th><th>Cuándo</th><th>Responsable</th><th>Estado</th><th class="text-end">Presupuesto</th><th class="text-end">Pagado</th><th class="text-end">Saldo</th>@if($canStages)<th></th>@endif</tr></thead>
        <tbody>
        @forelse($stageList as $st)
        <tr>
            <td>{{ $st->name }}@if($st->notes)<div class="text-muted">{{ $st->notes }}</div>@endif</td>
            <td>{{ $st->starts_at?->format('d/m H:i') }}</td>
            <td>{{ $st->responsible?->name }}</td>
            <td>{{ \App\Models\EventStage::STATUSES[$st->status] ?? $st->status }}</td>
            <td class="text-end">{{ $money($st->budget_cents) }}</td>
            <td class="text-end">{{ $money($stageSummary[$st->id]['paid_cents'] ?? 0) }}</td>
            <td class="text-end fw-bold">{{ $money($stageSummary[$st->id]['pending_cents'] ?? 0) }}</td>
            @if($canStages)<td class="text-end">
                <form method="POST" action="{{ route('events.stages.destroy', [$event, $st]) }}" onsubmit="return confirm('¿Eliminar la etapa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button></form>
            </td>@endif
        </tr>
        @empty
        <tr><td colspan="8" class="text-muted text-center py-3">Sin etapas. Dividí el evento en partes (armado, prensa, show, desarme…) con su responsable y presupuesto.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if($canStages)
    <div class="card-body border-top">
        <form method="POST" action="{{ route('events.stages.store', $event) }}" class="row g-2">@csrf
            <div class="col-md-3"><input name="name" class="form-control" placeholder="Etapa" required></div>
            <div class="col-md-2"><input type="datetime-local" name="starts_at" class="form-control"></div>
            <div class="col-md-3"><select name="responsible_contact_id" class="form-select"><option value="">Responsable</option>@foreach($stageContacts as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><input name="budget" class="form-control" placeholder="Presupuesto $"></div>
            <input type="hidden" name="status" value="PENDIENTE">
            <div class="col-md-2"><button class="btn btn-primary w-100">Agregar</button></div>
        </form>
        <div class="small text-muted mt-1">Los pagos de cada etapa se cargan como gastos en «Pagos y reparto»; el saldo es presupuesto menos pagado.</div>
    </div>
    @endif
</div>
@endif
