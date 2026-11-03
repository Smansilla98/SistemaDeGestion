@extends('layouts.app')

@section('title', 'Finanzas: '.$activity->name)

@php $money = fn ($c) => \App\Services\Finance\ActivityFinanceService::money((int) $c); $s = $settlement; @endphp

@section('content')
@include('community._head', ['icon' => 'bi-pie-chart', 'title' => $activity->name, 'back' => route('finance.index'),
    'subtitle' => ($type === 'workshop' ? 'Taller' : 'Evento del '.$activity->date->format('d/m/Y')).' · '.($s['rule']?->summary() ?? 'sin regla de reparto'),
    'actions' => '<a href="'.($type === 'workshop' ? route('workshops.show', $activity) : route('events.show', $activity)).'" class="btn btn-outline-light">Ver '.($type === 'workshop' ? 'taller' : 'evento').'</a>'])

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Ingresos</div><h4 class="text-success mb-0">{{ $money($s['income_cents']) }}</h4></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Gastos</div><h4 class="text-danger mb-0">{{ $money($s['expense_cents']) }}</h4></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Resultado</div><h4 class="mb-0">{{ $money($s['result_cents']) }}</h4></div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body"><div class="small text-muted">Base del reparto</div><h4 class="mb-0">{{ $money($s['base_cents']) }}</h4></div></div></div>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        @if($stages)
        <div class="card mb-4"><div class="card-header"><h5 class="mb-0">Etapas: presupuesto y pagos</h5></div>
            <div class="table-responsive"><table class="table mb-0 small">
                <thead><tr><th>Etapa</th><th class="text-end">Presupuesto</th><th class="text-end">Pagado</th><th class="text-end">Saldo</th></tr></thead>
                <tbody>
                @forelse($stages['stages'] as $st)<tr><td>{{ $st['name'] }}</td><td class="text-end">{{ $money($st['budget_cents']) }}</td><td class="text-end">{{ $money($st['paid_cents']) }}</td><td class="text-end fw-bold">{{ $money($st['pending_cents']) }}</td></tr>
                @empty<tr><td colspan="4" class="text-muted">Sin etapas. Cargalas desde el evento.</td></tr>@endforelse
                </tbody>
                <tfoot><tr class="fw-bold"><td>Total</td><td class="text-end">{{ $money($stages['budget_cents']) }}</td><td class="text-end">{{ $money($stages['paid_cents']) }}</td><td class="text-end">{{ $money($stages['pending_cents']) }}</td></tr></tfoot>
            </table></div>
        </div>
        @endif

        <div class="card">
            <div class="card-header"><h5 class="mb-0">Movimientos</h5></div>
            @if($canCreate)
            <div class="card-body border-bottom">
                <form method="POST" action="{{ route('finance.activity.entries.store', [$type, $activity->id]) }}" class="row g-2">@csrf
                    <div class="col-md-3"><select name="type" class="form-select"><option value="INGRESO">Ingreso</option><option value="GASTO">Gasto</option></select></div>
                    <div class="col-md-5"><input name="concept" class="form-control" placeholder="Concepto (cuota, entrada, materiales…)" required></div>
                    <div class="col-md-2"><input name="amount" class="form-control" placeholder="$" required></div>
                    <div class="col-md-2"><input type="date" name="date" class="form-control" value="{{ today()->toDateString() }}" required></div>
                    @if($type === 'workshop')
                    <div class="col-md-6"><select name="workshop_enrollment_id" class="form-select"><option value="">Pago de un alumno (opcional)</option>
                        @foreach($enrollments as $e)<option value="{{ $e->id }}">{{ $e->contact->name }}@isset($balances[$e->id]) — debe {{ $money($balances[$e->id]['pending_cents']) }}@endisset</option>@endforeach</select></div>
                    @else
                    <div class="col-md-6"><select name="event_stage_id" class="form-select"><option value="">Etapa (para pagos/gastos)</option>
                        @foreach($activity->stages as $st)<option value="{{ $st->id }}">{{ $st->name }}</option>@endforeach</select></div>
                    @endif
                    <div class="col-md-4"><select name="contact_id" class="form-select"><option value="">Persona (opcional)</option>@foreach($contacts as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">Cargar</button></div>
                </form>
            </div>
            @endif
            <div class="table-responsive"><table class="table mb-0 small align-middle">
                <tbody>
                @forelse($entries as $en)
                <tr class="{{ $en->voided_at ? 'text-muted text-decoration-line-through' : '' }}">
                    <td>{{ $en->date->format('d/m/Y') }}</td>
                    <td>{{ $en->concept }} <span class="text-muted">{{ $en->contact?->name }} {{ $en->stage?->name }}</span>@if($en->voided_at)<div class="text-decoration-none">Anulado: {{ $en->void_reason }}</div>@endif</td>
                    <td class="text-end {{ $en->type === 'INGRESO' ? 'text-success' : 'text-danger' }}">{{ $en->type === 'INGRESO' ? '+' : '−' }}{{ $money($en->amount_cents) }}</td>
                    <td class="text-end">@if($canSettle && ! $en->voided_at)
                        <form method="POST" action="{{ route('finance.entries.void', $en) }}" class="d-flex gap-1" onsubmit="return confirm('¿Anular este movimiento?')">@csrf
                            <input name="reason" class="form-control form-control-sm" placeholder="Motivo" required style="width: 120px"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i></button></form>@endif</td>
                </tr>
                @empty
                <tr><td class="text-center text-muted py-4">Sin movimientos.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card mb-4"><div class="card-header"><h5 class="mb-0">Reparto</h5></div>
            @if($s['rule'])
            <div class="table-responsive"><table class="table mb-0 small">
                <thead><tr><th>Parte</th><th class="text-end">Le toca</th><th class="text-end">Pagado</th><th class="text-end">Pendiente</th></tr></thead>
                <tbody>@foreach($s['parties'] as $p)<tr><td>{{ $p['label'] }} ({{ rtrim(rtrim(number_format($p['percent'], 2, ',', ''), '0'), ',') }}%)</td>
                    <td class="text-end">{{ $money($p['due_cents']) }}</td><td class="text-end">{{ $money($p['paid_cents']) }}</td>
                    <td class="text-end fw-bold {{ $p['pending_cents'] < 0 ? 'text-danger' : '' }}">{{ $money($p['pending_cents']) }}</td></tr>@endforeach</tbody>
            </table></div>
            @if(collect($s['parties'])->contains(fn ($p) => $p['pending_cents'] < 0))
            <div class="card-body small text-danger border-top">Hay una parte con pagos por encima de lo que le toca (se anuló un ingreso o se cargó un gasto después de pagar).</div>
            @endif
            @if($canSettle)
            <div class="card-body border-top">
                <form method="POST" action="{{ route('finance.activity.payouts.store', [$type, $activity->id]) }}" class="row g-2">@csrf
                    <div class="col-5"><select name="party" class="form-select">@foreach($s['parties'] as $p)<option>{{ $p['label'] }}</option>@endforeach</select></div>
                    <div class="col-3"><input name="amount" class="form-control" placeholder="$" required></div>
                    <div class="col-4"><input type="date" name="paid_on" class="form-control" value="{{ today()->toDateString() }}"></div>
                    <div class="col-8"><input name="note" class="form-control" placeholder="Nota"></div>
                    <div class="col-4"><button class="btn btn-primary w-100">Pagar</button></div>
                </form>
            </div>
            @endif
            @if($payouts->isNotEmpty())
            <ul class="list-group list-group-flush small">@foreach($payouts as $po)<li class="list-group-item d-flex justify-content-between"><span>{{ $po->paid_on->format('d/m/Y') }} · {{ $po->party }} {{ $po->note }}</span><span>{{ $money($po->amount_cents) }}</span></li>@endforeach</ul>
            @endif
            @else
            <div class="card-body text-muted small">Asigná una regla para calcular cuánto le toca a cada parte.</div>
            @endif
            @if($canCreate)
            <div class="card-footer">
                <form method="POST" action="{{ route('finance.activity.rule', [$type, $activity->id]) }}" class="d-flex gap-2">@csrf
                    <select name="distribution_rule_id" class="form-select form-select-sm"><option value="">Sin regla</option>@foreach($rules as $r)<option value="{{ $r->id }}" @selected($s['rule']?->id === $r->id)>{{ $r->name }}</option>@endforeach</select>
                    <button class="btn btn-sm btn-outline-primary">Asignar</button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
