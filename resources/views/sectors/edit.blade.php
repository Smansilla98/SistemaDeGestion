@extends('layouts.app')

@section('title', 'Editar Sector')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('sectors.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-pencil-square"></i> Editar Sector: {{ $sector->name }}</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Información del Sector</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('sectors.update', $sector) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre *</label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $sector->name) }}" 
                               required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="3">{{ old('description', $sector->description) }}</textarea>
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if($sector->isSubsector())
                    <div class="mb-3">
                        <label for="capacity" class="form-label">Capacidad (Elementos)</label>
                        <input type="number" 
                               class="form-control @error('capacity') is-invalid @enderror" 
                               id="capacity" 
                               name="capacity" 
                               value="{{ old('capacity', $sector->capacity) }}" 
                               min="1">
                        @error('capacity')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Número de elementos/lugares del subsector. Al aumentar, se crearán nuevos elementos automáticamente.</small>
                    </div>
                    @endif

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="is_active" 
                                   name="is_active" 
                                   value="1" 
                                   {{ old('is_active', $sector->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                {{ $sector->isSubsector() ? 'Subsector activo' : 'Sector activo' }}
                            </label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('sectors.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Información Actual</h5>
            </div>
            <div class="card-body">
                <p><strong>Nombre:</strong> {{ $sector->name }}</p>
                <p><strong>Descripción:</strong> {{ $sector->description ?? '-' }}</p>
                <p><strong>Mesas:</strong> {{ $sector->tables()->count() }}</p>
                <p><strong>Estado:</strong> 
                    <span class="badge bg-{{ $sector->is_active ? 'success' : 'secondary' }}">
                        {{ $sector->is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                </p>
            </div>
        </div>

        @can('delete', $sector)
        <div class="card mt-3 border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Zona de Peligro</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    Eliminar este sector es una acción permanente. Asegurate de que no tenga mesas asignadas.
                </p>
                @if($sector->tables()->count() > 0)
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>No se puede eliminar:</strong> El sector tiene {{ $sector->tables()->count() }} mesa(s) asignada(s).
                </div>
                @else
                <form id="deleteSectorForm" action="{{ route('sectors.destroy', $sector) }}" method="POST" class="mt-3">
                    @csrf
                    @method('DELETE')
                    <button type="button" 
                            class="btn btn-danger w-100" 
                            onclick="confirmDeleteSector()">
                        <i class="bi bi-trash"></i> Eliminar Sector
                    </button>
                </form>
                @endif
            </div>
        </div>
        @endcan
    </div>
</div>

@push('scripts')
<script>
function confirmDeleteSector() {
    Swal.fire({
        icon: 'warning',
        title: '¿Eliminar Sector?',
        html: `
            <p>Esta acción <strong>no se puede deshacer</strong>.</p>
            <p>Se eliminará permanentemente el sector <strong>{{ $sector->name }}</strong>.</p>
            <div class="alert alert-warning mt-3">
                <small><i class="bi bi-info-circle"></i> Asegurate de que el sector no tenga mesas asignadas.</small>
            </div>
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
            
            document.getElementById('deleteSectorForm').submit();
        }
    });
}
</script>
@endpush
@endsection

