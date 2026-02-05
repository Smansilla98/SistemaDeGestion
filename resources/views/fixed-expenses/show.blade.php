@extends('layouts.app')

@section('title', 'Detalle de Gasto/Ingreso Fijo')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('fixed-expenses.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
            <i class="bi bi-cash-stack"></i> {{ $fixedExpense->name }}
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Información General</h5>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">Tipo:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-{{ $fixedExpense->type === 'GASTO' ? 'danger' : 'success' }}">
                            {{ $fixedExpense->type === 'GASTO' ? 'Gasto' : 'Ingreso' }}
                        </span>
                    </dd>

                    <dt class="col-sm-4">Categoría:</dt>
                    <dd class="col-sm-8">{{ $fixedExpense->getCategoryLabel() }}</dd>

                    <dt class="col-sm-4">Monto:</dt>
                    <dd class="col-sm-8"><strong>${{ number_format($fixedExpense->amount, 2) }}</strong></dd>

                    <dt class="col-sm-4">Frecuencia:</dt>
                    <dd class="col-sm-8">
                        @php
                            $freqLabels = [
                                'MENSUAL' => 'Mensual',
                                'QUINCENAL' => 'Quincenal',
                                'SEMANAL' => 'Semanal',
                                'DIARIO' => 'Diario',
                                'ANUAL' => 'Anual',
                            ];
                        @endphp
                        {{ $freqLabels[$fixedExpense->frequency] ?? $fixedExpense->frequency }}
                    </dd>

                    <dt class="col-sm-4">Fecha de Inicio:</dt>
                    <dd class="col-sm-8">{{ $fixedExpense->start_date->format('d/m/Y') }}</dd>

                    <dt class="col-sm-4">Fecha de Fin:</dt>
                    <dd class="col-sm-8">
                        {{ $fixedExpense->end_date ? $fixedExpense->end_date->format('d/m/Y') : 'Indefinido' }}
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
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Proyección (Próximos 12 Meses)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm">
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
                            <tr class="table-info">
                                <th>Total:</th>
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

