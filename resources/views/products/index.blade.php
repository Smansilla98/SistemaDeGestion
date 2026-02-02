@extends('layouts.app')

@section('title', 'Productos')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-box-seam"></i> Productos</h1>
        </div>
        @can('create', App\Models\Product::class)
        <a href="{{ route('products.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nuevo Producto
        </a>
        @endcan
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <form method="GET" action="{{ route('products.index') }}" class="row g-3">
            <div class="col-md-4">
                <select name="category_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Todas las categorías</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <input type="text" name="search" class="form-control" placeholder="Buscar producto..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Buscar</button>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td>
                            <strong>{{ $product->name }}</strong>
                            @if($product->description)
                            <br><small class="text-muted">{{ Str::limit($product->description, 50) }}</small>
                            @endif
                        </td>
                        <td>{{ $product->category->name }}</td>
                        <td><strong>${{ number_format($product->price, 2) }}</strong></td>
                        <td>
                            @if($product->has_stock)
                                @php
                                    $currentStock = $product->getCurrentStock(auth()->user()->restaurant_id);
                                    $isLowStock = $currentStock <= $product->stock_minimum;
                                    $isOutOfStock = $currentStock <= 0;
                                @endphp
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-{{ $isOutOfStock ? 'danger' : ($isLowStock ? 'warning' : 'success') }} fs-6">
                                        {{ $currentStock }}
                                    </span>
                                    @if($isOutOfStock)
                                        <span class="badge bg-danger" title="Sin stock">
                                            <i class="bi bi-exclamation-triangle-fill"></i> Sin Stock
                                        </span>
                                    @elseif($isLowStock)
                                        <span class="badge bg-warning text-dark" title="Stock bajo">
                                            <i class="bi bi-exclamation-circle-fill"></i> Stock Bajo
                            </span>
                                    @endif
                                    @if($product->stock_minimum > 0)
                                        <small class="text-muted">(Mín: {{ $product->stock_minimum }})</small>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted">Sin control de stock</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $product->is_active ? 'success' : 'secondary' }}">
                                {{ $product->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('products.show', $product) }}" class="btn btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('update', $product)
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endcan
                                @can('delete', $product)
                                <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline" id="deleteProductForm{{ $product->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" 
                                            class="btn btn-outline-danger" 
                                            onclick="confirmDeleteProduct({{ $product->id }}, '{{ addslashes($product->name) }}')"
                                            title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No hay productos</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $products->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmDeleteProduct(productId, productName) {
    Swal.fire({
        icon: 'warning',
        title: '¿Eliminar Producto?',
        html: `
            <p>¿Estás seguro de eliminar el producto <strong>${productName}</strong>?</p>
            <div class="alert alert-warning mt-3">
                <small><i class="bi bi-info-circle"></i> Esta acción eliminará el producto permanentemente. Si tiene pedidos asociados, se mantendrán en el historial.</small>
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
            // Mostrar loading
            Swal.fire({
                title: 'Eliminando...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Enviar formulario
            document.getElementById('deleteProductForm' + productId).submit();
        }
    });
}
</script>
@endpush
@endsection

