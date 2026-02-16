@extends('layouts.app')

@section('title', 'Tipos de Descuentos')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-percent"></i> Tipos de Descuentos</h1>
            <p class="text-muted">Gestiona los tipos de descuentos disponibles</p>
        </div>
        @if(auth()->user()->role === 'ADMIN')
        <a href="{{ route('discount-types.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo Descuento
        </a>
        @endif
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Porcentaje</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($discountTypes as $discountType)
                    <tr>
                        <td>
                            <strong>{{ $discountType->name }}</strong>
                        </td>
                        <td>
                            <span class="badge bg-primary" style="font-size: 1rem;">{{ number_format($discountType->percentage, 2) }}%</span>
                        </td>
                        <td>
                            {{ $discountType->description ?? '-' }}
                        </td>
                        <td>
                            @if($discountType->is_active)
                            <span class="badge bg-success">Activo</span>
                            @else
                            <span class="badge bg-secondary">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                @if(auth()->user()->role === 'ADMIN')
                                <a href="{{ route('discount-types.edit', $discountType) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                                <form action="{{ route('discount-types.destroy', $discountType) }}" method="POST" class="d-inline" id="deleteDiscountTypeForm{{ $discountType->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteDiscountType({{ $discountType->id }}, '{{ $discountType->name }}')">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-3">No hay tipos de descuentos registrados</p>
                            @if(auth()->user()->role === 'ADMIN')
                            <a href="{{ route('discount-types.create') }}" class="btn btn-primary mt-2">
                                <i class="bi bi-plus-circle"></i> Crear Primer Descuento
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $discountTypes->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmDeleteDiscountType(discountTypeId, discountTypeName) {
    Swal.fire({
        icon: 'warning',
        title: '¿Eliminar Tipo de Descuento?',
        html: `¿Estás seguro de eliminar el descuento <strong>${discountTypeName}</strong>?<br><small class="text-muted">Esta acción no se puede deshacer.</small>`,
        showCancelButton: true,
        confirmButtonColor: '#c94a2d',
        cancelButtonColor: '#7b7d84',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteDiscountTypeForm' + discountTypeId).submit();
        }
    });
}
</script>
@endpush
@endsection

