@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-tags"></i> Categorías</h1>
            <p class="text-muted">Gestiona las categorías de productos</p>
        </div>
        @can('create', App\Models\Category::class)
        <a href="{{ route('categories.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nueva Categoría
        </a>
        @endcan
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
                        <th>Descripción</th>
                        <th>Productos</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                    <tr>
                        <td>
                            <strong>{{ $category->name }}</strong>
                        </td>
                        <td>
                            {{ $category->description ?? '-' }}
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $category->products_count }}</span>
                        </td>
                        <td>
                            @if($category->is_active)
                            <span class="badge bg-success">Activa</span>
                            @else
                            <span class="badge bg-secondary">Inactiva</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('categories.show', $category) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('update', $category)
                                <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endcan
                                @can('delete', $category)
                                <form action="{{ route('categories.destroy', $category) }}" method="POST" class="d-inline" id="deleteCategoryForm{{ $category->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteCategory({{ $category->id }}, '{{ $category->name }}')">
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
                            No hay categorías registradas
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $categories->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmDeleteCategory(categoryId, categoryName) {
    Swal.fire({
        icon: 'warning',
        title: '¿Eliminar Categoría?',
        html: `¿Estás seguro de eliminar la categoría <strong>${categoryName}</strong>?<br><small class="text-muted">Esta acción no se puede deshacer.</small>`,
        showCancelButton: true,
        confirmButtonColor: '#c94a2d',
        cancelButtonColor: '#7b7d84',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('deleteCategoryForm' + categoryId).submit();
        }
    });
}
</script>
@endpush
@endsection

