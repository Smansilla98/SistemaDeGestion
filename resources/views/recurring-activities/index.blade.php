@extends('layouts.app')

@section('title', 'Actividades Recurrentes')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
                <i class="bi bi-calendar-repeat"></i> Actividades Recurrentes
            </h1>
        </div>
        @can('create', App\Models\RecurringActivity::class)
        <a href="{{ route('recurring-activities.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nueva Actividad
        </a>
        @endcan
    </div>
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" action="{{ route('recurring-activities.index') }}" class="row g-3">
            <div class="col-md-4">
                <select name="day_of_week" class="form-select" onchange="this.form.submit()">
                    <option value="">Todos los días</option>
                    <option value="MONDAY" {{ request('day_of_week') == 'MONDAY' ? 'selected' : '' }}>Lunes</option>
                    <option value="TUESDAY" {{ request('day_of_week') == 'TUESDAY' ? 'selected' : '' }}>Martes</option>
                    <option value="WEDNESDAY" {{ request('day_of_week') == 'WEDNESDAY' ? 'selected' : '' }}>Miércoles</option>
                    <option value="THURSDAY" {{ request('day_of_week') == 'THURSDAY' ? 'selected' : '' }}>Jueves</option>
                    <option value="FRIDAY" {{ request('day_of_week') == 'FRIDAY' ? 'selected' : '' }}>Viernes</option>
                    <option value="SATURDAY" {{ request('day_of_week') == 'SATURDAY' ? 'selected' : '' }}>Sábado</option>
                    <option value="SUNDAY" {{ request('day_of_week') == 'SUNDAY' ? 'selected' : '' }}>Domingo</option>
                </select>
            </div>
            <div class="col-md-4">
                <select name="is_active" class="form-select" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Activas</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactivas</option>
                </select>
            </div>
            <div class="col-md-4">
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
                        <th>Día</th>
                        <th>Horario</th>
                        <th>Asistencia Esperada</th>
                        <th>Ingreso Esperado</th>
                        <th>Período</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $activity)
                    <tr>
                        <td>
                            <strong>{{ $activity->name }}</strong>
                            @if($activity->description)
                            <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($activity->description, 50) }}</small>
                            @endif
                        </td>
                        <td>{{ $activity->getDayLabel() }}</td>
                        <td>
                            {{ substr($activity->start_time, 0, 5) }}hs
                            @if($activity->end_time)
                            - {{ substr($activity->end_time, 0, 5) }}hs
                            @endif
                        </td>
                        <td>
                            @if($activity->expected_attendance)
                            {{ $activity->expected_attendance }} personas
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($activity->expected_revenue)
                            <strong>${{ number_format($activity->expected_revenue, 2) }}</strong>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <small>
                                @if($activity->start_date)
                                Desde: {{ $activity->start_date->format('d/m/Y') }}<br>
                                @endif
                                @if($activity->end_date)
                                Hasta: {{ $activity->end_date->format('d/m/Y') }}
                                @else
                                <span class="text-muted">Indefinido</span>
                                @endif
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-{{ $activity->is_active ? 'success' : 'secondary' }}">
                                {{ $activity->is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('recurring-activities.show', $activity) }}" class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('update', $activity)
                                <a href="{{ route('recurring-activities.edit', $activity) }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endcan
                                @can('delete', $activity)
                                <form action="{{ route('recurring-activities.destroy', $activity) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" 
                                            onclick="return confirm('¿Estás seguro de eliminar esta actividad?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No hay actividades recurrentes registradas</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center">
            {{ $activities->links() }}
        </div>
    </div>
</div>
@endsection

