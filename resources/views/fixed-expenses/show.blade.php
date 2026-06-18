@extends('layouts.app')

@section('title', 'Detalle de Gasto/Ingreso Fijo')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('fixed-expenses.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1 class="text-white mb-2 page-hero-title">
            <i class="bi bi-cash-stack"></i> {{ $fixedExpense->name }}
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Información y trazabilidad</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Tipo:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-{{ $fixedExpense->type === 'GASTO' ? 'danger' : 'success' }}">
                            {{ $fixedExpense->type === 'GASTO' ? 'Gasto fijo' : 'Ingreso fijo' }}
                        </span>
                    </dd>

                    <dt class="col-sm-4">Categoría:</dt>
                    <dd class="col-sm-8">{{ $fixedExpense->getCategoryLabel() }}</dd>

                    <dt class="col-sm-4">Monto por ocurrencia:</dt>
                    <dd class="col-sm-8"><strong>${{ number_format($fixedExpense->amount, 2) }}</strong></dd>

                    <dt class="col-sm-4">Frecuencia:</dt>
                    <dd class="col-sm-8">{{ $fixedExpense->getFrequencyLabel() }}</dd>

                    <dt class="col-sm-4">Equivalente mensual:</dt>
                    <dd class="col-sm-8">
                        <strong class="text-{{ $fixedExpense->type === 'GASTO' ? 'danger' : 'success' }}">
                            ${{ number_format($fixedExpense->getMonthlyEquivalent(), 2) }}
                        </strong>
                        <small class="text-muted"> (referencia para planificación)</small>
                    </dd>

                    <dt class="col-sm-4">Día de cobro/pago:</dt>
                    <dd class="col-sm-8">
                        {{ $fixedExpense->getDueDayLabel() ?? 'Sin definir' }}
                    </dd>

                    <dt class="col-sm-4">Vigencia:</dt>
                    <dd class="col-sm-8">
                        Desde {{ $fixedExpense->start_date->format('d/m/Y') }}
                        @if($fixedExpense->end_date)
                            — Hasta {{ $fixedExpense->end_date->format('d/m/Y') }}
                        @else
                            — <span class="text-muted">Indefinida</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4">Estado:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-{{ $fixedExpense->is_active ? 'success' : 'secondary' }}">
                            {{ $fixedExpense->is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </dd>

                    @if($fixedExpense->description)
                    <dt class="col-sm-4">Descripción:</dt>
                    <dd class="col-sm-8">{{ $fixedExpense->description }}</dd>
                    @endif
                </dl>

                <div class="mt-3">
                    @can('update', $fixedExpense)
                    <a href="{{ route('fixed-expenses.edit', $fixedExpense) }}" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Este mes</h5>
            </div>
            <div class="card-body text-center">
                @php
                    $currentMonthAmount = $fixedExpense->getProjectedAmountForPeriod(
                        now()->startOfMonth(),
                        now()->endOfMonth()
                    );
                    $dueThisMonth = $fixedExpense->getDueDateForMonth(now()->startOfMonth());
                @endphp
                <h3 class="text-{{ $fixedExpense->type === 'GASTO' ? 'danger' : 'success' }} mb-1">
                    ${{ number_format($currentMonthAmount, 2) }}
                </h3>
                <p class="text-muted mb-0">
                    @if($dueThisMonth)
                        Cobro/pago estimado: {{ $dueThisMonth->format('d/m/Y') }}
                    @else
                        Sin día de cobro definido
                    @endif
                </p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Proyección (12 meses)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Mes</th>
                                <th class="text-end">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($projections as $projection)
                            <tr>
                                <td>{{ $projection['month_name'] }}</td>
                                <td class="text-end">
                                    <strong class="text-{{ $fixedExpense->type === 'GASTO' ? 'danger' : 'success' }}">
                                        ${{ number_format($projection['amount'], 2) }}
                                    </strong>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <th>Total anual</th>
                                <th class="text-end">
                                    ${{ number_format(collect($projections)->sum('amount'), 2) }}
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
