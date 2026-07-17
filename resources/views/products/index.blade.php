@extends('layouts.app')

@section('title', 'Productos')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
                <i class="bi bi-box-seam"></i> 
                {{ $selectedType === 'INSUMO' ? 'Insumos' : 'Productos' }}
            </h1>
        </div>
        @can('create', App\Models\Product::class)
        <div class="btn-group flex-wrap">
            @can('managePricing', App\Models\Product::class)
            <x-button href="{{ route('products.bulk-pricing') }}" variant="outline" icon="bi-grid-3x3">Editar precios</x-button>
            @endcan
            <x-button href="{{ route('products.create', ['type' => 'PRODUCT']) }}" icon="bi-plus-circle">Nuevo Producto</x-button>
            <x-button href="{{ route('products.create', ['type' => 'INSUMO']) }}" variant="success" icon="bi-plus-circle">Nuevo Insumo</x-button>
        </div>
        @endcan
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <form method="GET" action="{{ route('products.index') }}" class="row g-3">
            <div class="col-md-3">
                <select name="type" class="form-select" onchange="this.form.submit()">
                    <option value="PRODUCT" {{ $selectedType === 'PRODUCT' ? 'selected' : '' }}>Productos Vendibles</option>
                    <option value="INSUMO" {{ $selectedType === 'INSUMO' ? 'selected' : '' }}>Insumos</option>
                </select>
            </div>
            @if($selectedType === 'PRODUCT')
            <div class="col-md-3">
                <select name="category_id" class="form-select" onchange="this.form.submit()">
                    <option value="">Todas las categorías</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-{{ $selectedType === 'PRODUCT' ? '4' : '7' }}">
                <input type="text" name="search" class="form-control" placeholder="Buscar {{ $selectedType === 'INSUMO' ? 'insumo' : 'producto' }}..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Buscar</button>
            </div>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive rtbl-cards">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        @if($selectedType === 'PRODUCT')
                        <th>Categoría</th>
                        @can('managePricing', App\Models\Product::class)
                        <th>Costo</th>
                        @endcan
                        <th>Valor de venta</th>
                        @can('managePricing', App\Models\Product::class)
                        <th>Ganancia</th>
                        @endcan
                        @else
                        <th>Unidad</th>
                        <th>Costo Unitario</th>
                        <th>Proveedor</th>
                        @endif
                        <th>Stock</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td data-label="Nombre">
                            <strong>{{ $product->name }}</strong>
                            @if($product->description)
                            <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($product->description, 50) }}</small>
                            @endif
                        </td>
                        @if($product->type === 'PRODUCT')
                        <td data-label="Categoría">{{ $product->category->name ?? '-' }}</td>
                        @can('managePricing', App\Models\Product::class)
                        <td data-label="Costo">
                            @if($product->cost_price !== null)
                                ${{ number_format($product->cost_price, 2) }}
                            @else
                                <span class="text-muted">Sin cargar</span>
                            @endif
                        </td>
                        @endcan
                        <td data-label="Valor de venta"><strong>${{ number_format($product->price, 2) }}</strong></td>
                        @can('managePricing', App\Models\Product::class)
                        <td data-label="Ganancia">
                            @if($product->profit_margin !== null)
                                <x-badge tone="{{ $product->profit_margin >= 0 ? 'green' : 'red' }}">
                                    {{ number_format($product->profit_margin, 2) }}%
                                </x-badge>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        @endcan
                        @else
                        <td data-label="Unidad">{{ $product->unit ?? '-' }}</td>
                        <td data-label="Costo Unitario"><strong>${{ number_format($product->unit_cost ?? 0, 2) }}</strong></td>
                        <td data-label="Proveedor">{{ $product->supplier->name ?? '-' }}</td>
                        @endif
                        <td data-label="Stock">
                            @if($product->has_stock)
                                @php
                                    $currentStock = $product->getCurrentStock(auth()->user()->restaurant_id);
                                    $isLowStock = $currentStock <= $product->stock_minimum;
                                    $isOutOfStock = $currentStock <= 0;
                                @endphp
                                <div class="d-flex align-items-center gap-2 justify-content-end flex-wrap">
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
                        <td data-label="Estado">
                            <span class="badge bg-{{ $product->is_active ? 'success' : 'secondary' }}">
                                {{ $product->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td data-label="" class="rtbl-actions">
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
                        <td colspan="{{ $selectedType === 'PRODUCT' ? (auth()->user()->can('managePricing', App\Models\Product::class) ? 8 : 6) : 7 }}" class="text-center text-muted">
                            No hay {{ $selectedType === 'INSUMO' ? 'insumos' : 'productos' }}
                        </td>
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

