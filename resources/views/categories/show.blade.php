@extends('layouts.app')

@section('title', 'Categoría: ' . $category->name)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('categories.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-tag"></i> {{ $category->name }}</h1>
        <p class="text-muted">
            @if($category->description)
            {{ $category->description }}
            @endif
        </p>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Productos de esta Categoría ({{ $category->products->count() }})</h5>
                @can('update', $category)
                <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-pencil"></i> Editar
                </a>
                @endcan
            </div>
            <div class="card-body">
                @if($category->products->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Precio</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($category->products as $product)
                            <tr>
                                <td>
                                    <strong>{{ $product->name }}</strong>
                                </td>
                                <td>${{ number_format($product->price, 2) }}</td>
                                <td>
                                    @if($product->is_active)
                                    <span class="badge bg-success">Activo</span>
                                    @else
                                    <span class="badge bg-secondary">Inactivo</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted text-center">No hay productos en esta categoría</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Información</h5>
            </div>
            <div class="card-body">
                <p><strong>Nombre:</strong> {{ $category->name }}</p>
                @if($category->description)
                <p><strong>Descripción:</strong> {{ $category->description }}</p>
                @endif
                <p><strong>Productos:</strong> {{ $category->products->count() }}</p>
                <p><strong>Estado:</strong> 
                    @if($category->is_active)
                    <span class="badge bg-success">Activa</span>
                    @else
                    <span class="badge bg-secondary">Inactiva</span>
                    @endif
                </p>

                <div class="mt-3">
                    @can('update', $category)
                    <a href="{{ route('categories.edit', $category) }}" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-pencil"></i> Editar Categoría
                    </a>
                    @endcan
                    @can('delete', $category)
                    <form action="{{ route('categories.destroy', $category) }}" method="POST" id="deleteCategoryForm{{ $category->id }}">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-danger w-100" onclick="confirmDeleteCategory({{ $category->id }}, '{{ $category->name }}')">
                            <i class="bi bi-trash"></i> Eliminar Categoría
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
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

