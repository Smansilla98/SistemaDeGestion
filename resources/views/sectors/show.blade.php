@extends('layouts.app')

@section('title', 'Sector: ' . $sector->name)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('sectors.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-grid"></i> Sector: {{ $sector->name }}</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Información del Sector</h5>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-3">Nombre:</dt>
                    <dd class="col-sm-9">{{ $sector->name }}</dd>

                    <dt class="col-sm-3">Descripción:</dt>
                    <dd class="col-sm-9">{{ $sector->description ?? '-' }}</dd>

                    <dt class="col-sm-3">Estado:</dt>
                    <dd class="col-sm-9">
                        <span class="badge bg-{{ $sector->is_active ? 'success' : 'secondary' }}">
                            {{ $sector->is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </dd>

                    <dt class="col-sm-3">Mesas:</dt>
                    <dd class="col-sm-9">
                        <span class="badge bg-info">{{ $sector->tables->count() }}</span>
                    </dd>
                </dl>

                <div class="d-flex gap-2">
                    @can('update', $sector)
                    <a href="{{ route('sectors.edit', $sector) }}" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                    @endcan
                    <a href="{{ route('tables.layout', $sector->id) }}" class="btn btn-info">
                        <i class="bi bi-table"></i> Ver Layout de Mesas
                    </a>
                </div>
            </div>
        </div>

        @if($sector->tables->count() > 0)
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Mesas del Sector</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Capacidad</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sector->tables as $table)
                            <tr>
                                <td><strong>{{ $table->number }}</strong></td>
                                <td>{{ $table->capacity }} personas</td>
                                <td>
                                    <span class="badge bg-{{ $table->status === 'LIBRE' ? 'success' : ($table->status === 'OCUPADA' ? 'warning' : 'secondary') }}">
                                        {{ $table->status }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('tables.index') }}?sector={{ $sector->id }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Ver
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

