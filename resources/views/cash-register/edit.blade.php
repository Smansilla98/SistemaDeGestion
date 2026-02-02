@extends('layouts.app')

@section('title', 'Editar Caja')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('cash-register.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-pencil-square"></i> Editar Caja: {{ $cashRegister->name }}</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Información de la Caja</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('cash-register.update', $cashRegister) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre *</label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $cashRegister->name) }}" 
                               required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="is_active" 
                                   name="is_active" 
                                   value="1" 
                                   {{ old('is_active', $cashRegister->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Caja activa
                            </label>
                            <small class="form-text text-muted d-block">Solo las cajas activas pueden abrir sesiones</small>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('cash-register.index') }}" class="btn btn-secondary">
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
                <p><strong>Nombre:</strong> {{ $cashRegister->name }}</p>
                <p><strong>Sesiones:</strong> {{ $cashRegister->sessions()->count() }}</p>
                <p><strong>Estado:</strong> 
                    <span class="badge bg-{{ $cashRegister->is_active ? 'success' : 'secondary' }}">
                        {{ $cashRegister->is_active ? 'Activa' : 'Inactiva' }}
                    </span>
                </p>
            </div>
        </div>

        <div class="card mt-3 border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Zona de Peligro</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    Eliminar esta caja es una acción permanente. Asegurate de que no tenga sesiones abiertas o históricas.
                </p>
                @if($cashRegister->sessions()->where('status', 'ABIERTA')->count() > 0)
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>No se puede eliminar:</strong> La caja tiene sesiones abiertas.
                </div>
                @elseif($cashRegister->sessions()->count() > 0)
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>No se puede eliminar:</strong> La caja tiene sesiones históricas. Desactívala en su lugar.
                </div>
                @else
                <form id="deleteCashRegisterForm" action="{{ route('cash-register.destroy', $cashRegister) }}" method="POST" class="mt-3">
                    @csrf
                    @method('DELETE')
                    <button type="button" 
                            class="btn btn-danger w-100" 
                            onclick="confirmDeleteCashRegister()">
                        <i class="bi bi-trash"></i> Eliminar Caja
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmDeleteCashRegister() {
    Swal.fire({
        icon: 'warning',
        title: '¿Eliminar Caja?',
        html: `
            <p>Esta acción <strong>no se puede deshacer</strong>.</p>
            <p>Se eliminará permanentemente la caja <strong>{{ $cashRegister->name }}</strong>.</p>
            <div class="alert alert-warning mt-3">
                <small><i class="bi bi-info-circle"></i> Asegurate de que la caja no tenga sesiones abiertas o históricas.</small>
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
            
            document.getElementById('deleteCashRegisterForm').submit();
        }
    });
}
</script>
@endpush
@endsection

