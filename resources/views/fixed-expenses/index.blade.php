@extends('layouts.app')

@section('title', 'Gastos Fijos')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
                <i class="bi bi-cash-stack"></i> Gastos e Ingresos Fijos
            </h1>
        </div>
        @can('create', App\Models\FixedExpense::class)
        <a href="{{ route('fixed-expenses.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo Gasto/Ingreso
        </a>
        @endcan
    </div>
</div>

<!-- Resumen de Totales -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card border-danger">
            <div class="card-body">
                <h5 class="card-title text-danger">
                    <i class="bi bi-arrow-down-circle"></i> Total Gastos (Este Mes)
                </h5>
                <h2 class="text-danger">${{ number_format($totalGastos, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-success">
            <div class="card-body">
                <h5 class="card-title text-success">
                    <i class="bi bi-arrow-up-circle"></i> Total Ingresos (Este Mes)
                </h5>
                <h2 class="text-success">${{ number_format($totalIngresos, 2) }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" action="{{ route('fixed-expenses.index') }}" class="row g-3">
            <div class="col-md-3">
                <select name="type" class="form-select" onchange="this.form.submit()">
                    <option value="">Todos (Gastos e Ingresos)</option>
                    <option value="GASTO" {{ request('type') == 'GASTO' ? 'selected' : '' }}>Solo Gastos</option>
                    <option value="INGRESO" {{ request('type') == 'INGRESO' ? 'selected' : '' }}>Solo Ingresos</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select" onchange="this.form.submit()">
                    <option value="">Todas las categorías</option>
                    <option value="ALQUILER" {{ request('category') == 'ALQUILER' ? 'selected' : '' }}>Alquiler</option>
                    <option value="SERVICIOS" {{ request('category') == 'SERVICIOS' ? 'selected' : '' }}>Servicios</option>
                    <option value="PERSONAL" {{ request('category') == 'PERSONAL' ? 'selected' : '' }}>Personal</option>
                    <option value="OPERATIVOS" {{ request('category') == 'OPERATIVOS' ? 'selected' : '' }}>Operativos</option>
                    <option value="TALLER" {{ request('category') == 'TALLER' ? 'selected' : '' }}>Taller</option>
                    <option value="OTROS" {{ request('category') == 'OTROS' ? 'selected' : '' }}>Otros</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="is_active" class="form-select" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Activos</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactivos</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Categoría</th>
                        <th>Monto</th>
                        <th>Frecuencia</th>
                        <th>Período</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                    <tr>
                        <td>
                            <strong>{{ $expense->name }}</strong>
                            @if($expense->description)
                            <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($expense->description, 50) }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $expense->type === 'GASTO' ? 'danger' : 'success' }}">
                                {{ $expense->type === 'GASTO' ? 'Gasto' : 'Ingreso' }}
                            </span>
                        </td>
                        <td>{{ $expense->getCategoryLabel() }}</td>
                        <td><strong>${{ number_format($expense->amount, 2) }}</strong></td>
                        <td>
                            @php
                                $freqLabels = [
                                    'MENSUAL' => 'Mensual',
                                    'QUINCENAL' => 'Quincenal',
                                    'SEMANAL' => 'Semanal',
                                    'DIARIO' => 'Diario',
                                    'ANUAL' => 'Anual',
                                ];
                            @endphp
                            {{ $freqLabels[$expense->frequency] ?? $expense->frequency }}
                        </td>
                        <td>
                            <small>
                                Desde: {{ $expense->start_date->format('d/m/Y') }}<br>
                                @if($expense->end_date)
                                Hasta: {{ $expense->end_date->format('d/m/Y') }}
                                @else
                                <span class="text-muted">Indefinido</span>
                                @endif
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-{{ $expense->is_active ? 'success' : 'secondary' }}">
                                {{ $expense->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('fixed-expenses.show', $expense) }}" class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('update', $expense)
                                <a href="{{ route('fixed-expenses.edit', $expense) }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endcan
                                @can('delete', $expense)
                                <form action="{{ route('fixed-expenses.destroy', $expense) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" 
                                            onclick="return confirm('¿Estás seguro de eliminar este gasto/ingreso?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No hay gastos/ingresos fijos registrados</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center">
            {{ $expenses->links() }}
        </div>
    </div>
</div>
@endsection

