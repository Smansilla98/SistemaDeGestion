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

                    @if($sector->subsectors->count() > 0)
                    <dt class="col-sm-3">Subsectores:</dt>
                    <dd class="col-sm-9">
                        <span class="badge bg-primary">{{ $sector->subsectors->count() }}</span>
                    </dd>
                    @endif
                </dl>

                <div class="d-flex gap-2 flex-wrap">
                    @can('update', $sector)
                    <a href="{{ route('sectors.edit', $sector) }}" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                    @endcan
                    @can('create', App\Models\Sector::class)
                    <a href="{{ route('sectors.create', ['parent_id' => $sector->id]) }}" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Nuevo Subsector
                    </a>
                    @endcan
                    <a href="{{ route('tables.layout', $sector->id) }}" class="btn btn-info">
                        <i class="bi bi-table"></i> Ver Layout
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

        @if($sector->subsectors->count() > 0)
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Subsectores</h5>
                @can('create', App\Models\Sector::class)
                <a href="{{ route('sectors.create', ['parent_id' => $sector->id]) }}" class="btn btn-sm btn-success">
                    <i class="bi bi-plus-circle"></i> Nuevo Subsector
                </a>
                @endcan
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($sector->subsectors as $subsector)
                    <div class="col-md-6 mb-3">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="bi bi-grid-3x3-gap"></i> {{ $subsector->name }}
                                    </h6>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('sectors.edit', $subsector) }}" class="btn btn-light" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @can('delete', $subsector)
                                        <form action="{{ route('sectors.destroy', $subsector) }}" method="POST" class="d-inline" id="deleteSubsectorForm{{ $subsector->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-light" onclick="confirmDeleteSubsector({{ $subsector->id }}, '{{ addslashes($subsector->name) }}')" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                @if($subsector->description)
                                <p class="small text-muted mb-2">{{ $subsector->description }}</p>
                                @endif
                                
                                @if($subsector->capacity)
                                <p class="mb-2">
                                    <strong>Capacidad:</strong> {{ $subsector->capacity }} elementos
                                </p>
                                @endif

                                @if($subsector->items->count() > 0)
                                <div class="mb-2">
                                    <strong>Elementos:</strong>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        @foreach($subsector->items as $item)
                                        <span class="badge bg-{{ $item->status === 'LIBRE' ? 'success' : ($item->status === 'OCUPADA' ? 'warning' : 'secondary') }}">
                                            {{ $item->name }} ({{ $item->status }})
                                        </span>
                                        @endforeach
                                    </div>
                                </div>
                                @endif

                                <div class="d-flex gap-2 mt-2">
                                    <a href="{{ route('sectors.show', $subsector) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Ver Detalles
                                    </a>
                                    @if($subsector->items->count() > 0)
                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="openItemsModal({{ $subsector->id }})">
                                        <i class="bi bi-list-ul"></i> Gestionar Elementos
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function confirmDeleteSubsector(subsectorId, subsectorName) {
    Swal.fire({
        icon: 'warning',
        title: '¿Eliminar Subsector?',
        html: `
            <p>¿Estás seguro de eliminar el subsector <strong>${subsectorName}</strong>?</p>
            <div class="alert alert-warning mt-3">
                <small><i class="bi bi-info-circle"></i> Se eliminarán todos los elementos asociados.</small>
            </div>
            <p class="text-danger small mt-2"><strong>Esta acción no se puede deshacer.</strong></p>
        `,
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-trash"></i> Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Eliminando...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            document.getElementById('deleteSubsectorForm' + subsectorId).submit();
        }
    });
}

function openItemsModal(subsectorId) {
    // TODO: Implementar modal para gestionar items del subsector
    Swal.fire({
        icon: 'info',
        title: 'Gestionar Elementos',
        text: 'Funcionalidad en desarrollo',
        confirmButtonColor: '#1e8081',
        confirmButtonText: 'Entendido'
    });
}
</script>
@endpush
@endsection

