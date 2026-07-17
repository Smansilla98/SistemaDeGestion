@extends('layouts.app')

@section('title', 'Gastos Fijos')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="text-white mb-2 page-hero-title">
                <i class="bi bi-cash-stack"></i> Gastos e Ingresos Fijos
            </h1>
            <p class="text-white-50 mb-0">Proyección mensual y trazabilidad de ingresos/gastos recurrentes</p>
        </div>
        @can('create', App\Models\FixedExpense::class)
        <a href="{{ route('fixed-expenses.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo registro
        </a>
        @endcan
    </div>
</div>

<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('fixed-expenses.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label mb-1">Mes de referencia</label>
                <input type="month" name="month" class="form-control" value="{{ $month->format('Y-m') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">Tipo</label>
                <select name="type" class="form-select">
                    <option value="">Todos</option>
                    <option value="GASTO" {{ request('type') == 'GASTO' ? 'selected' : '' }}>Gastos</option>
                    <option value="INGRESO" {{ request('type') == 'INGRESO' ? 'selected' : '' }}>Ingresos</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1">Estado</label>
                <select name="is_active" class="form-select">
                    <option value="">Todos</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Activos</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactivos</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel"></i> Aplicar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card border-success h-100">
            <div class="card-body">
                <h6 class="text-success mb-1"><i class="bi bi-arrow-up-circle"></i> Ingresos fijos</h6>
                <h3 class="text-success mb-0">${{ number_format($summary['ingresos'], 2) }}</h3>
                <small class="text-muted">{{ $summary['month_label'] }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-danger h-100">
            <div class="card-body">
                <h6 class="text-danger mb-1"><i class="bi bi-arrow-down-circle"></i> Gastos fijos</h6>
                <h3 class="text-danger mb-0">${{ number_format($summary['gastos'], 2) }}</h3>
                <small class="text-muted">{{ $summary['month_label'] }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-primary h-100">
            <div class="card-body">
                <h6 class="text-primary mb-1"><i class="bi bi-graph-up-arrow"></i> Balance neto</h6>
                <h3 class="text-{{ $summary['neto'] >= 0 ? 'primary' : 'warning' }} mb-0">
                    ${{ number_format($summary['neto'], 2) }}
                </h3>
                <small class="text-muted">Ingresos − gastos del mes</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="mb-1"><i class="bi bi-list-check"></i> Registros activos</h6>
                <h3 class="mb-0">{{ $incomeBreakdown->count() + $expenseBreakdown->count() }}</h3>
                <small class="text-muted">Con impacto en {{ $summary['month_label'] }}</small>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-success bg-opacity-10">
                <h5 class="mb-0 text-success"><i class="bi bi-calendar-check"></i> Ingresos fijos del mes</h5>
            </div>
            <div class="card-body p-0">
                @if($incomeBreakdown->isNotEmpty())
                <div class="table-responsive rtbl-cards">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th>Categoría</th>
                                <th>Cobro</th>
                                <th class="text-end">Mes</th>
                                <th class="text-end">Ref. mensual</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($incomeBreakdown as $row)
                            <tr>
                                <td data-label="Concepto">
                                    <a href="{{ route('fixed-expenses.show', $row['item']) }}" class="text-decoration-none">
                                        <strong>{{ $row['item']->name }}</strong>
                                    </a>
                                </td>
                                <td data-label="Categoría"><span class="badge bg-light text-dark">{{ $row['item']->getCategoryLabel() }}</span></td>
                                <td data-label="Cobro">
                                    @if($row['due_date'])
                                        <small>{{ $row['due_date']->format('d/m') }}</small>
                                    @else
                                        <small class="text-muted">—</small>
                                    @endif
                                </td>
                                <td data-label="Mes" class="text-end text-success"><strong>${{ number_format($row['amount'], 2) }}</strong></td>
                                <td data-label="Ref. mensual" class="text-end text-muted">${{ number_format($row['monthly_equivalent'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-success">
                                <th colspan="3">Total ingresos</th>
                                <th class="text-end">${{ number_format($summary['ingresos'], 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @else
                <p class="text-muted p-3 mb-0">No hay ingresos fijos proyectados para este mes.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-danger bg-opacity-10">
                <h5 class="mb-0 text-danger"><i class="bi bi-calendar-x"></i> Gastos fijos del mes</h5>
            </div>
            <div class="card-body p-0">
                @if($expenseBreakdown->isNotEmpty())
                <div class="table-responsive rtbl-cards">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th>Categoría</th>
                                <th>Vence</th>
                                <th class="text-end">Mes</th>
                                <th class="text-end">Ref. mensual</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expenseBreakdown as $row)
                            <tr>
                                <td data-label="Concepto">
                                    <a href="{{ route('fixed-expenses.show', $row['item']) }}" class="text-decoration-none">
                                        <strong>{{ $row['item']->name }}</strong>
                                    </a>
                                </td>
                                <td data-label="Categoría"><span class="badge bg-light text-dark">{{ $row['item']->getCategoryLabel() }}</span></td>
                                <td data-label="Vence">
                                    @if($row['due_date'])
                                        <small>{{ $row['due_date']->format('d/m') }}</small>
                                    @else
                                        <small class="text-muted">—</small>
                                    @endif
                                </td>
                                <td data-label="Mes" class="text-end text-danger"><strong>${{ number_format($row['amount'], 2) }}</strong></td>
                                <td data-label="Ref. mensual" class="text-end text-muted">${{ number_format($row['monthly_equivalent'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-danger">
                                <th colspan="3">Total gastos</th>
                                <th class="text-end">${{ number_format($summary['gastos'], 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @else
                <p class="text-muted p-3 mb-0">No hay gastos fijos proyectados para este mes.</p>
                @endif
            </div>
        </div>
    </div>
</div>

@if(count($incomeByCategory) > 0 || count($expenseByCategory) > 0)
<div class="row mb-4">
    @if(count($incomeByCategory) > 0)
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0">Ingresos por categoría</h6></div>
            <ul class="list-group list-group-flush">
                @foreach($incomeByCategory as $label => $total)
                <li class="list-group-item d-flex justify-content-between">
                    <span>{{ $label }}</span>
                    <strong class="text-success">${{ number_format($total, 2) }}</strong>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif
    @if(count($expenseByCategory) > 0)
    <div class="col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0">Gastos por categoría</h6></div>
            <ul class="list-group list-group-flush">
                @foreach($expenseByCategory as $label => $total)
                <li class="list-group-item d-flex justify-content-between">
                    <span>{{ $label }}</span>
                    <strong class="text-danger">${{ number_format($total, 2) }}</strong>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif
</div>
@endif

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-table"></i> Todos los registros</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive rtbl-cards">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Categoría</th>
                        <th class="text-end">Por ocurrencia</th>
                        <th>Frecuencia</th>
                        <th class="text-end">En {{ $month->locale('es')->translatedFormat('M Y') }}</th>
                        <th class="text-end">Ref. mensual</th>
                        <th>Cobro/Pago</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                    <tr class="{{ ! $expense->is_active ? 'table-secondary' : '' }}">
                        <td data-label="Nombre">
                            <strong>{{ $expense->name }}</strong>
                            @if($expense->description)
                            <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($expense->description, 40) }}</small>
                            @endif
                        </td>
                        <td data-label="Tipo">
                            <span class="badge bg-{{ $expense->type === 'GASTO' ? 'danger' : 'success' }}">
                                {{ $expense->type === 'GASTO' ? 'Gasto' : 'Ingreso' }}
                            </span>
                        </td>
                        <td data-label="Categoría">{{ $expense->getCategoryLabel() }}</td>
                        <td data-label="Por ocurrencia" class="text-end">${{ number_format($expense->amount, 2) }}</td>
                        <td data-label="Frecuencia">{{ $expense->getFrequencyLabel() }}</td>
                        <td data-label="En {{ $month->locale('es')->translatedFormat('M Y') }}" class="text-end">
                            <strong class="text-{{ $expense->type === 'GASTO' ? 'danger' : 'success' }}">
                                ${{ number_format($expense->monthly_projected, 2) }}
                            </strong>
                        </td>
                        <td data-label="Ref. mensual" class="text-end text-muted">${{ number_format($expense->monthly_equivalent, 2) }}</td>
                        <td data-label="Cobro/Pago">
                            @if($expense->due_day)
                                <small>Día {{ $expense->due_day }}</small>
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>
                        <td data-label="Estado">
                            <span class="badge bg-{{ $expense->is_active ? 'success' : 'secondary' }}">
                                {{ $expense->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td data-label="" class="rtbl-actions">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('fixed-expenses.show', $expense) }}" class="btn btn-outline-primary" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('update', $expense)
                                <a href="{{ route('fixed-expenses.edit', $expense) }}" class="btn btn-outline-secondary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">No hay registros. Creá el primer ingreso o gasto fijo.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center p-3">
            {{ $expenses->links() }}
        </div>
    </div>
</div>
@endsection
