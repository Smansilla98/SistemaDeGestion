@extends('layouts.app')

@section('title', 'Detalle de Actividad Recurrente')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('recurring-activities.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
            <i class="bi bi-calendar-repeat"></i> {{ $recurringActivity->name }}
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
                    <dt class="col-sm-4">Día de la Semana:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-primary">{{ $recurringActivity->getDayLabel() }}</span>
                    </dd>

                    <dt class="col-sm-4">Horario:</dt>
                    <dd class="col-sm-8">
                        {{ substr($recurringActivity->start_time, 0, 5) }}hs
                        @if($recurringActivity->end_time)
                        - {{ substr($recurringActivity->end_time, 0, 5) }}hs
                        @endif
                    </dd>

                    @if($recurringActivity->expected_attendance)
                    <dt class="col-sm-4">Asistencia Esperada:</dt>
                    <dd class="col-sm-8">{{ $recurringActivity->expected_attendance }} personas</dd>
                    @endif

                    @if($recurringActivity->expected_revenue)
                    <dt class="col-sm-4">Ingreso Esperado:</dt>
                    <dd class="col-sm-8"><strong>${{ number_format($recurringActivity->expected_revenue, 2) }}</strong></dd>
                    @endif

                    <dt class="col-sm-4">Fecha de Inicio:</dt>
                    <dd class="col-sm-8">
                        {{ $recurringActivity->start_date ? $recurringActivity->start_date->format('d/m/Y') : 'Inmediato' }}
                    </dd>

                    <dt class="col-sm-4">Fecha de Fin:</dt>
                    <dd class="col-sm-8">
                        {{ $recurringActivity->end_date ? $recurringActivity->end_date->format('d/m/Y') : 'Indefinido' }}
                    </dd>

                    <dt class="col-sm-4">Estado:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-{{ $recurringActivity->is_active ? 'success' : 'secondary' }}">
                            {{ $recurringActivity->is_active ? 'Activa' : 'Inactiva' }}
                        </span>
                    </dd>

                    @if($recurringActivity->description)
                    <dt class="col-sm-4">Descripción:</dt>
                    <dd class="col-sm-8">{{ $recurringActivity->description }}</dd>
                    @endif
                </dl>

                <div class="mt-3">
                    @can('update', $recurringActivity)
                    <a href="{{ route('recurring-activities.edit', $recurringActivity) }}" class="btn btn-primary">
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
                <h5 class="mb-0">Próximas Instancias</h5>
            </div>
            <div class="card-body">
                @if(count($instances) > 0)
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($instances as $instance)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($instance['date'])->format('d/m/Y') }}</td>
                                <td>{{ substr($instance['time'], 0, 5) }}hs</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted text-center">No hay instancias programadas</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

