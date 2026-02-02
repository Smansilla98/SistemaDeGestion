@extends('layouts.app')

@section('title', 'Sectores')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-grid"></i> Sectores</h1>
            <p class="text-muted">Gestiona los sectores del restaurante</p>
        </div>
        @can('create', App\Models\Sector::class)
        <a href="{{ route('sectors.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo Sector
        </a>
        @endcan
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Mesas</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sectors as $sector)
                    <tr>
                        <td>
                            <strong>{{ $sector->name }}</strong>
                        </td>
                        <td>
                            {{ $sector->description ?? '-' }}
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $sector->tables_count }}</span>
                        </td>
                        <td>
                            @if($sector->is_active)
                            <span class="badge bg-success">Activo</span>
                            @else
                            <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('sectors.show', $sector) }}" class="btn btn-sm btn-outline-primary" title="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('update', $sector)
                                <a href="{{ route('sectors.edit', $sector) }}" class="btn btn-sm btn-outline-secondary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endcan
                                @can('delete', $sector)
                                <form action="{{ route('sectors.destroy', $sector) }}" method="POST" class="d-inline" id="deleteSectorForm{{ $sector->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteSector({{ $sector->id }}, '{{ addslashes($sector->name) }}')" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            No hay sectores registrados
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $sectors->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmDeleteSector(sectorId, sectorName) {
    Swal.fire({
        icon: 'warning',
        title: '¿Eliminar Sector?',
        html: `
            <p>¿Estás seguro de eliminar el sector <strong>${sectorName}</strong>?</p>
            <div class="alert alert-warning mt-3">
                <small><i class="bi bi-info-circle"></i> No se puede eliminar un sector que tiene mesas asignadas.</small>
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
            
            document.getElementById('deleteSectorForm' + sectorId).submit();
        }
    });
}
</script>
@endpush
@endsection

