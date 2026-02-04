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
