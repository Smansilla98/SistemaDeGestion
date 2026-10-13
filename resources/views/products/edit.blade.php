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
                    @can('managePricing', App\Models\Product::class)
                    @include('products._pricing-fields', ['product' => $product])
                    @else
                    @include('products._sale-price-field', ['product' => $product])
                    @endcan
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
    @php
        $variants = $product->modifiers->where('kind', \App\Models\ProductModifier::KIND_VARIANT)->sortBy('sort_order');
        $extras = $product->modifiers->where('kind', '!=', \App\Models\ProductModifier::KIND_VARIANT)->sortBy('sort_order');
        $usedPercentage = (float) $product->ingredients->sum(fn ($i) => (float) $i->pivot->percentage);
        $recipeCost = $product->ingredients->sum(fn ($i) => (float) $i->pivot->quantity * (float) ($i->unit_cost ?? 0));
    @endphp
    <div class="col-md-4 mt-4 mt-md-0">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible py-2 small">{{ session('success') }} <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible py-2 small">{{ session('error') }} <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button></div>
        @endif

        {{-- Variantes y extras --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-ui-radios"></i> Variantes y extras</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    Una <strong>variante</strong> define el precio de venta (ej. Pizza → Napolitana $20.000, Jamón y morrones $24.000) y se elige una sola al pedir.
                    Un <strong>extra</strong> suma un recargo. El stock se descuenta de este producto y, además, de los insumos propios de la variante.
                </p>

                @forelse($variants->concat($extras) as $modifier)
                    <div class="border rounded-3 p-2 mb-2 {{ $modifier->is_active ? '' : 'opacity-50' }}">
                        <form action="{{ route('products.variants.update', [$product, $modifier]) }}" method="POST" class="row g-1 align-items-center">
                            @csrf
                            @method('PUT')
                            <div class="col-12 mb-1">
                                <span class="badge {{ $modifier->isVariant() ? 'bg-primary' : 'bg-secondary' }}">{{ $modifier->isVariant() ? 'Variante' : 'Extra' }}</span>
                                @unless($modifier->is_active)<span class="badge bg-danger">Inactiva</span>@endunless
                            </div>
                            <div class="col-6">
                                <input type="text" name="name" class="form-control form-control-sm" value="{{ $modifier->name }}" required aria-label="Nombre">
                            </div>
                            <div class="col-4">
                                <input type="number" step="0.01" min="0" name="price" class="form-control form-control-sm"
                                       value="{{ $modifier->isVariant() ? $modifier->price : $modifier->price_modifier }}" required
                                       aria-label="{{ $modifier->isVariant() ? 'Precio final' : 'Recargo' }}">
                            </div>
                            <div class="col-2 text-end">
                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Guardar"><i class="bi bi-check-lg"></i></button>
                            </div>
                        </form>

                        <div class="small mt-2">
                            @foreach($modifier->ingredients as $ing)
                                <form action="{{ route('products.variants.ingredients.destroy', [$product, $modifier, $ing]) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <span class="badge bg-light text-dark border">
                                        {{ $ing->name }} · {{ $ing->pivot->quantity }} {{ $ing->unit }}
                                        <button type="submit" class="btn btn-link btn-sm p-0 ms-1 text-danger" title="Quitar insumo"><i class="bi bi-x"></i></button>
                                    </span>
                                </form>
                            @endforeach
                        </div>

                        <details class="mt-1">
                            <summary class="small text-muted">Insumos propios</summary>
                            <form action="{{ route('products.variants.ingredients.store', [$product, $modifier]) }}" method="POST" class="row g-1 mt-1">
                                @csrf
                                <div class="col-7">
                                    <select name="ingredient_id" class="form-select form-select-sm" required aria-label="Insumo">
                                        <option value="">Insumo</option>
                                        @foreach($insumos as $i)
                                            <option value="{{ $i->id }}">{{ $i->name }} ({{ $i->unit ?? 'u' }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-3">
                                    <input type="number" name="quantity" min="1" step="1" value="1" class="form-control form-control-sm" required aria-label="Cantidad">
                                </div>
                                <div class="col-2 text-end">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-plus"></i></button>
                                </div>
                            </form>
                            <form action="{{ route('products.variants.destroy', [$product, $modifier]) }}" method="POST" class="mt-2"
                                  onsubmit="return confirm('¿Quitar «{{ $modifier->name }}»?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-link text-danger p-0">Quitar</button>
                            </form>
                        </details>
                    </div>
                @empty
                    <p class="text-muted small mb-2">Sin variantes: se vende al precio del producto.</p>
                @endforelse

                <form action="{{ route('products.variants.store', $product) }}" method="POST" class="border-top pt-3">
                    @csrf
                    <div class="row g-2">
                        <div class="col-12">
                            <select name="kind" class="form-select form-select-sm" aria-label="Tipo">
                                <option value="VARIANT">Variante (define el precio)</option>
                                <option value="EXTRA">Extra (suma un recargo)</option>
                            </select>
                        </div>
                        <div class="col-7">
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="Ej. Napolitana" required aria-label="Nombre">
                        </div>
                        <div class="col-5">
                            <input type="number" step="0.01" min="0" name="price" class="form-control form-control-sm" placeholder="Precio" required aria-label="Precio">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary w-100 mt-2"><i class="bi bi-plus"></i> Agregar</button>
                </form>
            </div>
        </div>

        {{-- Receta --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Receta (insumos)</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    Cada unidad vendida descuenta estos insumos del stock, en la unidad del insumo (ej. ml).
                    Para tragos, cargá la medida del vaso y armá la receta por porcentaje.
                </p>

                <form action="{{ route('products.serving.update', $product) }}" method="POST" class="row g-2 align-items-end mb-3">
                    @csrf
                    @method('PUT')
                    <div class="col-8">
                        <label for="serving_size" class="form-label small mb-1">Medida del vaso (cc)</label>
                        <input type="number" min="1" step="1" name="serving_size" id="serving_size" class="form-control form-control-sm"
                               value="{{ $product->serving_size }}" placeholder="Ej. 500">
                    </div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">Guardar</button>
                    </div>
                </form>

                <table class="table table-sm table-borderless mb-2">
                    <tbody>
                        @forelse($product->ingredients as $ing)
                        <tr>
                            <td>{{ $ing->name }}</td>
                            <td class="text-end">
                                @if($ing->pivot->percentage !== null)
                                    <span class="badge bg-info text-dark">{{ rtrim(rtrim(number_format((float) $ing->pivot->percentage, 2, ',', ''), '0'), ',') }}%</span>
                                @endif
                                {{ $ing->pivot->quantity }} {{ $ing->pivot->unit ?? $ing->unit ?? '' }}
                            </td>
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

                @if($product->serving_size && $usedPercentage > 0)
                    <div class="progress mb-1" style="height: 8px;" role="progressbar" aria-label="Proporción usada del vaso" aria-valuenow="{{ $usedPercentage }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar" style="width: {{ min(100, $usedPercentage) }}%"></div>
                    </div>
                    <p class="small text-muted mb-2">
                        {{ rtrim(rtrim(number_format($usedPercentage, 2, ',', ''), '0'), ',') }}% del vaso de {{ $product->serving_size }} cc
                        @if($usedPercentage < 100) · queda {{ rtrim(rtrim(number_format(100 - $usedPercentage, 2, ',', ''), '0'), ',') }}% (hielo, espuma) @endif
                    </p>
                @endif
                @if($recipeCost > 0)
                    <p class="small mb-2">Costo de insumos por unidad: <strong>${{ number_format($recipeCost, 2, ',', '.') }}</strong></p>
                @endif

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
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label for="recipe_mode" class="form-label small">Cargar por</label>
                            <select name="mode" id="recipe_mode" class="form-select form-select-sm">
                                <option value="quantity">Cantidad fija</option>
                                <option value="percentage" {{ $product->serving_size ? 'selected' : 'disabled' }}>% del vaso</option>
                            </select>
                        </div>
                        <div class="col-6" id="recipe_quantity_group">
                            <label for="quantity" class="form-label small">Cantidad por unidad</label>
                            <input type="number" name="quantity" id="quantity" class="form-control form-control-sm" step="1" min="1" value="1">
                        </div>
                        <div class="col-6 d-none" id="recipe_percentage_group">
                            <label for="percentage" class="form-label small">Porcentaje</label>
                            <div class="input-group input-group-sm">
                                <input type="number" name="percentage" id="percentage" class="form-control" step="0.01" min="0.01" max="100" placeholder="30">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                    <p class="small text-muted mb-2 d-none" id="recipe_percentage_hint"></p>
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-plus"></i> Agregar a la receta</button>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
(function () {
    const mode = document.getElementById('recipe_mode');
    if (!mode) return;
    const qtyGroup = document.getElementById('recipe_quantity_group');
    const pctGroup = document.getElementById('recipe_percentage_group');
    const pct = document.getElementById('percentage');
    const hint = document.getElementById('recipe_percentage_hint');
    const serving = {{ (int) ($product->serving_size ?? 0) }};

    function sync() {
        const byPct = mode.value === 'percentage';
        qtyGroup.classList.toggle('d-none', byPct);
        pctGroup.classList.toggle('d-none', !byPct);
        hint.classList.toggle('d-none', !byPct);
        const value = parseFloat(pct.value);
        hint.textContent = byPct && value > 0
            ? `${value}% de ${serving} cc = ${Math.max(1, Math.round(serving * value / 100))} por unidad vendida`
            : 'Indicá qué porcentaje del vaso ocupa este insumo.';
    }
    mode.addEventListener('change', sync);
    pct.addEventListener('input', sync);
    sync();
})();
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
