{{--
  Selector de productos compartido (mesa + pedido rápido).
  Params:
    $products — agrupados por categoría
    $searchId — id del input de búsqueda
    $addFn — nombre de función global JS: addFn(productId, name, price)
    $colClass — clases de columna Bootstrap
    $sectionClass — clase de sección de categoría
--}}
@php
    $searchId = $searchId ?? 'productSearch';
    $addFn = $addFn ?? 'addProduct';
    $colClass = $colClass ?? 'col-md-4 mb-3';
    $sectionClass = $sectionClass ?? 'category-section';
    $showSearch = $showSearch ?? true;
    $restaurantId = auth()->user()->restaurant_id;
@endphp

<div class="product-picker" data-product-picker data-search-id="{{ $searchId }}">
    @if($showSearch)
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h5 class="mb-0">Seleccionar Productos</h5>
        <div class="input-group" style="max-width: 400px;">
            <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
            <input type="text"
                   class="form-control"
                   id="{{ $searchId }}"
                   placeholder="Buscar producto..."
                   autocomplete="off">
        </div>
    </div>
    @endif

    @foreach($products as $categoryName => $categoryProducts)
    <div class="mb-4 {{ $sectionClass }}" data-category-name="{{ strtolower($categoryName) }}">
        <div class="d-flex align-items-center mb-2 border-bottom pb-2">
            <h6 class="mb-0">{{ $categoryName }}</h6>
            <span class="badge bg-light text-dark ms-2">{{ $categoryProducts->count() }}</span>
        </div>
        <div class="row g-2">
            @foreach($categoryProducts as $product)
                @php
                    $currentStock = $product->has_stock ? $product->getCurrentStock($restaurantId) : null;
                    $isOutOfStock = $currentStock !== null && $currentStock <= 0;
                    $isLowStock = $currentStock !== null && $currentStock > 0 && $currentStock <= $product->stock_minimum;
                @endphp
                <div class="{{ $colClass }} product-item"
                     data-product-name="{{ strtolower($product->name) }}"
                     data-name="{{ strtolower($product->name) }}"
                     data-category-name="{{ strtolower($categoryName) }}">
                    <div class="card h-100 {{ $isOutOfStock ? 'border-danger' : ($isLowStock ? 'border-warning' : '') }}">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                                <h6 class="card-title mb-0">{{ $product->name }}</h6>
                                @if($isOutOfStock)
                                    <span class="badge bg-danger">Sin stock</span>
                                @elseif($isLowStock)
                                    <span class="badge bg-warning text-dark">Stock {{ $currentStock }}</span>
                                @endif
                            </div>
                            @if($product->description)
                                <p class="card-text text-muted small mb-2">{{ \Illuminate\Support\Str::limit($product->description, 60) }}</p>
                            @endif
                            <p class="card-text mb-2"><strong>${{ number_format($product->price, 2) }}</strong></p>
                            <button type="button"
                                    class="btn btn-sm btn-primary"
                                    onclick="{{ $addFn }}({{ $product->id }}, @js($product->name), {{ (float) $product->price }})"
                                    {{ $isOutOfStock ? 'disabled' : '' }}
                                    aria-label="Agregar {{ $product->name }}">
                                <i class="bi bi-plus" aria-hidden="true"></i> Agregar
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>

@once
@push('scripts')
<script>
(function () {
    document.querySelectorAll('[data-product-picker]').forEach(function (picker) {
        const searchId = picker.getAttribute('data-search-id');
        const input = searchId ? document.getElementById(searchId) : null;
        if (!input) return;

        input.addEventListener('input', function () {
            const term = this.value.toLowerCase().trim();
            picker.querySelectorAll('.category-section, .category-section-modal').forEach(function (section) {
                let visible = false;
                section.querySelectorAll('.product-item').forEach(function (item) {
                    const name = item.dataset.productName || item.dataset.name || '';
                    const cat = item.dataset.categoryName || '';
                    const show = !term || name.includes(term) || cat.includes(term);
                    item.style.display = show ? '' : 'none';
                    if (show) visible = true;
                });
                section.style.display = visible ? '' : 'none';
            });
        });
    });
})();
</script>
@endpush
@endonce
