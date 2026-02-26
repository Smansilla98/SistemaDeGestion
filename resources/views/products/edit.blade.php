@extends('layouts.app')

@section('title', $product->type === 'INSUMO' ? 'Editar Insumo' : 'Editar Producto')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('products.index', ['type' => $product->type]) }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
            <i class="bi bi-pencil"></i> 
            {{ $product->type === 'INSUMO' ? 'Editar Insumo' : 'Editar Producto' }}
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('products.update', $product) }}" method="POST" id="productForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="type" value="{{ $product->type }}">

                    @if($product->type === 'PRODUCT')
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Categoría *</label>
                        <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                            <option value="">Seleccionar categoría</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $product->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $product->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @if($product->type === 'PRODUCT')
                    <div class="mb-3">
                        <label for="price" class="form-label">Precio de Venta *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $product->price) }}" required min="0">
                        </div>
                        @error('price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @else
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="unit" class="form-label">Unidad de Medida *</label>
                            @php
                                $unitValue = old('unit', $product->unit);
                                $isCustomUnit = !in_array($unitValue, ['unidad', 'caja', 'paquete', 'kg', 'litro', 'metro', 'rollo']);
                            @endphp
                            <select class="form-select @error('unit') is-invalid @enderror" id="unit" name="unit" required>
                                <option value="">Seleccionar unidad</option>
                                <option value="unidad" {{ $unitValue == 'unidad' ? 'selected' : '' }}>Unidad</option>
                                <option value="caja" {{ $unitValue == 'caja' ? 'selected' : '' }}>Caja</option>
                                <option value="paquete" {{ $unitValue == 'paquete' ? 'selected' : '' }}>Paquete</option>
                                <option value="kg" {{ $unitValue == 'kg' ? 'selected' : '' }}>Kilogramo (kg)</option>
                                <option value="litro" {{ $unitValue == 'litro' ? 'selected' : '' }}>Litro (L)</option>
                                <option value="metro" {{ $unitValue == 'metro' ? 'selected' : '' }}>Metro (m)</option>
                                <option value="rollo" {{ $unitValue == 'rollo' ? 'selected' : '' }}>Rollo</option>
                                <option value="otro" {{ $isCustomUnit ? 'selected' : '' }}>Otro</option>
                            </select>
                            @error('unit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3" id="unit_other_group" style="display: {{ $isCustomUnit ? 'block' : 'none' }};">
                            <label for="unit_other" class="form-label">Especificar unidad</label>
                            <input type="text" class="form-control" id="unit_other" name="unit_other" value="{{ $isCustomUnit ? $unitValue : old('unit_other') }}" placeholder="Ej: bolsa, botella, etc.">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="unit_cost" class="form-label">Costo Unitario *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" class="form-control @error('unit_cost') is-invalid @enderror" id="unit_cost" name="unit_cost" value="{{ old('unit_cost', $product->unit_cost) }}" required min="0">
                        </div>
                        @error('unit_cost')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Costo de compra por unidad</small>
                    </div>

                    <div class="mb-3">
                        <label for="supplier_id" class="form-label">Proveedor</label>
                        <select class="form-select @error('supplier_id') is-invalid @enderror" id="supplier_id" name="supplier_id">
                            <option value="">Sin proveedor</option>
                            @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id', $product->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Opcional: Proveedor habitual de este insumo</small>
                    </div>
                    @endif

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="has_stock" name="has_stock" {{ old('has_stock', $product->has_stock) ? 'checked' : '' }}>
                        <label class="form-check-label" for="has_stock">
                            Maneja stock
                        </label>
                    </div>

                    <div class="mb-3" id="stock_minimum_group" style="display: {{ $product->has_stock ? 'block' : 'none' }};">
                        <label for="stock_minimum" class="form-label">Stock Mínimo</label>
                        <input type="number" class="form-control @error('stock_minimum') is-invalid @enderror" id="stock_minimum" name="stock_minimum" value="{{ old('stock_minimum', $product->stock_minimum) }}" min="0">
                        @error('stock_minimum')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Cantidad mínima antes de generar alerta</small>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">
                            Activo
                        </label>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="{{ route('products.index', ['type' => $product->type]) }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Actualizar {{ $product->type === 'INSUMO' ? 'Insumo' : 'Producto' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($product->type === 'PRODUCT')
    <div class="col-md-4 mt-4 mt-md-0">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Receta (insumos)</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small">Cada unidad vendida descontará estos insumos del stock. Las cantidades están en la unidad del insumo (ej. ml).</p>

                @if(session('success') && (str_contains(session('success'), 'receta') || str_contains(session('success'), 'Insumo')))
                    <div class="alert alert-success alert-dismissible py-2 small">{{ session('success') }} <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button></div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible py-2 small">{{ session('error') }} <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button></div>
                @endif

                <table class="table table-sm table-borderless mb-3">
                    <tbody>
                        @forelse($product->ingredients as $ing)
                        <tr>
                            <td>{{ $ing->name }}</td>
                            <td class="text-end">{{ $ing->pivot->quantity }} {{ $ing->pivot->unit ?? $ing->unit ?? '' }}</td>
                            <td class="text-end">
                                <form action="{{ route('products.ingredients.destroy', [$product, $ing]) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Quitar este insumo de la receta?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td class="text-muted small">Sin insumos. Agregá los que usa este producto.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <form action="{{ route('products.ingredients.store', $product) }}" method="POST" class="border-top pt-3">
                    @csrf
                    <div class="mb-2">
                        <label for="ingredient_id" class="form-label small">Insumo</label>
                        <select name="ingredient_id" id="ingredient_id" class="form-select form-select-sm" required>
                            <option value="">Seleccionar insumo</option>
                            @foreach($insumos as $i)
                                @if(!$product->ingredients->contains('id', $i->id))
                                <option value="{{ $i->id }}">{{ $i->name }} ({{ $i->unit ?? 'u' }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label for="quantity" class="form-label small">Cantidad por unidad</label>
                        <input type="number" name="quantity" id="quantity" class="form-control form-control-sm" step="1" min="1" value="1" required>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-plus"></i> Agregar a la receta</button>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
document.getElementById('has_stock').addEventListener('change', function() {
    const stockMinimumGroup = document.getElementById('stock_minimum_group');
    stockMinimumGroup.style.display = this.checked ? 'block' : 'none';
});

@if($product->type === 'INSUMO')
// Manejar unidad "otro"
document.getElementById('unit').addEventListener('change', function() {
    const unitOtherGroup = document.getElementById('unit_other_group');
    const unitOtherInput = document.getElementById('unit_other');
    
    if (this.value === 'otro') {
        unitOtherGroup.style.display = 'block';
        unitOtherInput.required = true;
    } else {
        unitOtherGroup.style.display = 'none';
        unitOtherInput.required = false;
        unitOtherInput.value = '';
    }
});

// Si se selecciona "otro", usar el valor del input
document.getElementById('productForm').addEventListener('submit', function(e) {
    const unitSelect = document.getElementById('unit');
    const unitOther = document.getElementById('unit_other');
    
    if (unitSelect.value === 'otro' && unitOther.value) {
        // Crear un input hidden con el valor personalizado
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'unit';
        hiddenInput.value = unitOther.value;
        this.appendChild(hiddenInput);
        
        // Deshabilitar el select para que no se envíe
        unitSelect.disabled = true;
    }
});
@endif
</script>
@endpush
@endsection
