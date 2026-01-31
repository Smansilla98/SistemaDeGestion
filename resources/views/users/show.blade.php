@extends('layouts.app')

@section('title', 'Detalles de Usuario')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-person-circle"></i> {{ $user->name }}</h1>
            <p class="text-muted">{{ $user->email }}</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('users.edit', $user) }}" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="avatar-circle mx-auto mb-3" style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #1e8081, #22565e); display: flex; align-items: center; justify-content: center; color: white; font-size: 2.5rem; font-weight: 700;">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <h4>{{ $user->name }}</h4>
                <p class="text-muted mb-3">{{ $user->email }}</p>
                
                @php
                    $roleColors = [
                        'ADMIN' => 'danger',
                        'SUPERVISOR' => 'warning',
                        'ENCARGADO' => 'info',
                        'MOZO' => 'primary',
                        'COCINA' => 'secondary',
                        'CAJERO' => 'success',
                    ];
                    $color = $roleColors[$user->role] ?? 'secondary';
                @endphp
                <span class="badge bg-{{ $color }} fs-6 mb-3">{{ $user->role }}</span>
                
                <div class="mt-3">
                    @if($user->is_active)
                        <span class="badge bg-success">Activo</span>
                    @else
                        <span class="badge bg-secondary">Inactivo</span>
                    @endif
                </div>

                @if($user->last_login_at)
                    <p class="text-muted mt-3 mb-0">
                        <small>Último acceso: {{ $user->last_login_at->diffForHumans() }}</small>
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-8">
        @if($user->role === 'MOZO')
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-table"></i> Mesas Asignadas Actualmente</h5>
            </div>
            <div class="card-body">
                @if($activeTables->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Mesa</th>
                                <th>Sector</th>
                                <th>Estado</th>
                                <th>Capacidad</th>
                                <th>Inicio de Sesión</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activeTables as $table)
                            <tr>
                                <td><strong>{{ $table->number }}</strong></td>
                                <td>{{ $table->sector->name ?? 'Sin sector' }}</td>
                                <td>
                                    <span class="badge bg-{{ $table->status === 'OCUPADA' ? 'warning' : 'success' }}">
                                        {{ $table->status }}
                                    </span>
                                </td>
                                <td>{{ $table->capacity }} personas</td>
                                <td>
                                    <small class="text-muted">
                                        {{ $table->currentSession->started_at->diffForHumans() }}
                                    </small>
                                </td>
                                <td>
                                    <a href="{{ route('tables.index') }}?table={{ $table->id }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Ver
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Este mozo no tiene mesas asignadas actualmente.
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Historial de Sesiones de Mesa</h5>
            </div>
            <div class="card-body">
                @if($tableSessions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Mesa</th>
                                <th>Sector</th>
                                <th>Inicio</th>
                                <th>Fin</th>
                                <th>Estado</th>
                                <th>Duración</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tableSessions as $session)
                            <tr>
                                <td><strong>{{ $session->table->number }}</strong></td>
                                <td>{{ $session->table->sector->name ?? 'Sin sector' }}</td>
                                <td>
                                    <small>{{ $session->started_at->format('d/m/Y H:i') }}</small>
                                </td>
                                <td>
                                    @if($session->ended_at)
                                        <small>{{ $session->ended_at->format('d/m/Y H:i') }}</small>
                                    @else
                                        <small class="text-muted">-</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $session->status === 'OPEN' ? 'success' : 'secondary' }}">
                                        {{ $session->status === 'OPEN' ? 'Abierta' : 'Cerrada' }}
                                    </span>
                                </td>
                                <td>
                                    @if($session->ended_at)
                                        <small class="text-muted">
                                            {{ $session->started_at->diffInMinutes($session->ended_at) }} min
                                        </small>
                                    @else
                                        <small class="text-success">
                                            {{ $session->started_at->diffInMinutes(now()) }} min (activa)
                                        </small>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No hay historial de sesiones de mesa.
                </div>
                @endif
            </div>
        </div>
        @else
        <div class="card">
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> 
                    La información de mesas asignadas solo está disponible para usuarios con rol MOZO.
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

