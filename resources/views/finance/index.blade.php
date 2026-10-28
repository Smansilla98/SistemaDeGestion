@extends('layouts.app')

@section('title', 'Finanzas de talleres y eventos')

@php $money = fn ($c) => \App\Services\Finance\ActivityFinanceService::money((int) $c); @endphp

@section('content')
@include('community._head', ['icon' => 'bi-pie-chart', 'title' => 'Finanzas de talleres y eventos', 'subtitle' => 'Ingresos, gastos, repartos (ej. 70/30) y saldos pendientes por actividad.'])
<div class="row g-4">
    <div class="col-xl-8"><div class="card"><div class="table-responsive"><table class="table mb-0 align-middle">
        <thead><tr><th>Actividad</th><th>Reparto</th><th class="text-end">Ingresos</th><th class="text-end">Gastos</th><th class="text-end">Resultado</th><th class="text-end">Pendiente de pagar</th></tr></thead>
        <tbody>
        @forelse($rows as $r)
        <tr>
            <td><span class="badge bg-light text-dark">{{ $r['kind'] }}</span> <a href="{{ $r['url'] }}">{{ $r['model']->name }}</a></td>
            <td class="small">{{ $r['s']['rule']?->name ?? '—' }}</td>
            <td class="text-end">{{ $money($r['s']['income_cents']) }}</td>
            <td class="text-end">{{ $money($r['s']['expense_cents']) }}</td>
            <td class="text-end {{ $r['s']['result_cents'] < 0 ? 'text-danger' : '' }}">{{ $money($r['s']['result_cents']) }}</td>
            <td class="text-end">{{ $money(collect($r['s']['parties'])->sum(fn ($p) => max(0, $p['pending_cents']))) }}</td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center text-muted py-5">Todavía no hay talleres ni eventos.</td></tr>
        @endforelse
        </tbody>
    </table></div></div></div>

    <div class="col-xl-4">
        <div class="card mb-4"><div class="card-header"><h6 class="mb-0">Reglas de reparto</h6></div>
            <ul class="list-group list-group-flush">
            @forelse($rules as $rule)<li class="list-group-item"><strong>{{ $rule->name }}</strong><div class="small text-muted">{{ $rule->summary() }}</div></li>
            @empty<li class="list-group-item text-muted">Sin reglas todavía.</li>@endforelse
            </ul>
        </div>
        @if($canCreate)
        <div class="card"><div class="card-header"><h6 class="mb-0">Nueva regla</h6></div><div class="card-body">
            <form method="POST" action="{{ route('finance.rules.store') }}" class="row g-2">@csrf
                <div class="col-12"><input name="name" class="form-control" placeholder="Ej. Talleres 70/30" required value="{{ old('name') }}"></div>
                <div class="col-12"><select name="base" class="form-select">@foreach(\App\Models\DistributionRule::BASES as $k => $l)<option value="{{ $k }}">Sobre {{ mb_strtolower($l) }}</option>@endforeach</select></div>
                @foreach([['Docente', 70], ['Espacio', 30], ['', ''], ['', '']] as $i => [$label, $pct])
                <div class="col-8"><input name="parties[{{ $i }}][label]" class="form-control form-control-sm" placeholder="Parte" value="{{ old("parties.$i.label", $label) }}"></div>
                <div class="col-4"><input name="parties[{{ $i }}][percent]" class="form-control form-control-sm" placeholder="%" value="{{ old("parties.$i.percent", $pct) }}"></div>
                @endforeach
                <div class="col-12 small text-muted">Los porcentajes tienen que sumar 100. «Ingresos menos gastos» reparte lo que queda después de los gastos cargados a la actividad.</div>
                <div class="col-12"><button class="btn btn-primary w-100">Crear regla</button></div>
            </form>
        </div></div>
        @endif
    </div>
</div>
@endsection
