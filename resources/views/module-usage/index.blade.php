@extends('layouts.app')

@section('title', 'Monitor de Módulos')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="text-white mb-2 page-hero-title">
            <i class="bi bi-speedometer2"></i> Monitor de utilización de módulos
        </h1>
        <p class="text-white-50 mb-0">
            Solo visible para superadmin. Conteos basados en registros, auditoría y movimientos del sistema.
        </p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <form method="GET" action="{{ route('module-usage.index') }}" class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label">Restaurante</label>
                <select name="restaurant_id" class="form-select">
                    <option value="">Todos</option>
                    @foreach($restaurants as $restaurant)
                        <option value="{{ $restaurant->id }}" @selected($restaurantId === $restaurant->id)>
                            {{ $restaurant->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label">Desde</label>
                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label">Hasta</label>
                <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
            </div>
            <div class="col-12 col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <h6 class="mb-1">Módulos utilizados</h6>
                <h2 class="mb-0">{{ $summary['totals']['modules_used'] }} / {{ $summary['totals']['modules_defined'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <h6 class="mb-1">Total operaciones</h6>
                <h2 class="mb-0">{{ number_format($summary['totals']['total_operations']) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body">
                <h6 class="mb-1">Sin uso detectado</h6>
                <h2 class="mb-0">{{ $summary['totals']['modules_unused'] }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <h6 class="mb-1">Tasa de adopción</h6>
                <h2 class="mb-0">{{ $summary['totals']['usage_rate'] }}%</h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-check"></i> Módulos y cantidad de uso</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Módulo</th>
                                <th class="text-end">Usos totales</th>
                                <th>Estado</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($summary['modules'] as $index => $module)
                            <tr class="{{ $module['used'] ? '' : 'table-secondary' }}">
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $module['label'] }}</strong>
                                    <div class="text-muted small"><code>{{ $module['key'] }}</code></div>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-{{ $module['used'] ? 'primary' : 'secondary' }} fs-6">
                                        {{ number_format($module['total']) }}
                                    </span>
                                </td>
                                <td>
                                    @if($module['used'])
                                        <span class="badge bg-success">En uso</span>
                                    @else
                                        <span class="badge bg-secondary">Sin actividad</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($module['sources']))
                                        <ul class="list-unstyled mb-0 small">
                                            @foreach($module['sources'] as $source => $count)
                                                @if($count > 0)
                                                <li>{{ $source }}: <strong>{{ number_format($count) }}</strong></li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No hay datos para mostrar.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person-lines-fill"></i> Usuarios más activos (auditoría)</h5>
            </div>
            <div class="card-body p-0">
                @if(count($summary['top_users']) > 0)
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th class="text-end">Ops.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($summary['top_users'] as $user)
                            <tr>
                                <td>{{ $user['name'] }}</td>
                                <td><span class="badge bg-light text-dark">{{ $user['role'] }}</span></td>
                                <td class="text-end">{{ number_format($user['operations']) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted p-3 mb-0">Sin registros de auditoría en el período seleccionado.</p>
                @endif
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <h6 class="card-title"><i class="bi bi-info-circle"></i> Fuentes de datos</h6>
                <ul class="small text-muted mb-0">
                    <li>Registros en tablas operativas (pedidos, mesas, productos, etc.)</li>
                    <li>Logs de auditoría (<code>audit_logs</code>)</li>
                    <li>Movimientos de stock y caja</li>
                    <li>Sesiones de mesa, pagos y logins</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
