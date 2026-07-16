@extends('layouts.app')

@section('title', 'Edición masiva de precios')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <a href="{{ route('products.index') }}" class="btn btn-secondary mb-2">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
            <h1 class="text-white mb-1 page-hero-title">
                <i class="bi bi-grid-3x3"></i> Matriz de precios
            </h1>
            <p class="text-white-50 mb-0">Editá costo, valor de venta y ganancia de varios productos a la vez.</p>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('products.bulk-pricing') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Categoría</label>
                <select name="category_id" class="form-select">
                    <option value="">Todas</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Buscar</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Nombre del producto…">
            </div>
            <div class="col-md-3">
                <div class="form-check mt-4">
                    <input type="checkbox" class="form-check-input" id="only_without_cost" name="only_without_cost" value="1" @checked(request()->boolean('only_without_cost'))>
                    <label class="form-check-label" for="only_without_cost">Solo sin costo cargado</label>
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

@if($products->isEmpty())
<div class="card">
    <div class="card-body text-center text-muted py-5">
        No hay productos para mostrar con esos filtros.
    </div>
</div>
@else
<form method="POST" action="{{ route('products.bulk-pricing.update') }}" id="bulkPricingForm">
    @csrf
    @method('PUT')
    <input type="hidden" name="category_id" value="{{ request('category_id') }}">
    <input type="hidden" name="search" value="{{ request('search') }}">
    @if(request()->boolean('only_without_cost'))
        <input type="hidden" name="only_without_cost" value="1">
    @endif

    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="bulkApplyMargin" class="form-label mb-1">Aplicar ganancia % a todos (con costo)</label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0" class="form-control" id="bulkApplyMargin" placeholder="Ej: 100">
                        <span class="input-group-text">%</span>
                        <button type="button" class="btn btn-outline-primary" id="bulkApplyMarginBtn">Aplicar</button>
                    </div>
                </div>
                <div class="col-md-8 text-md-end">
                    <span class="text-muted me-3">{{ $products->count() }} productos</span>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Guardar todos los cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 bulk-pricing-table">
                    <thead>
                        <tr>
                            <th style="min-width: 200px;">Producto</th>
                            <th style="min-width: 120px;">Categoría</th>
                            <th style="min-width: 130px;">Costo</th>
                            <th style="min-width: 130px;">Valor de venta</th>
                            <th style="min-width: 130px;">Ganancia %</th>
                            <th style="min-width: 110px;">$ ganancia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $index => $product)
                        <tr data-product-pricing class="bulk-pricing-row">
                            <td>
                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $product->id }}">
                                <input type="hidden" name="items[{{ $index }}][pricing_source]" value="sale" data-pricing-source>
                                <strong>{{ $product->name }}</strong>
                                @if($product->description)
                                    <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($product->description, 40) }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark">{{ $product->category->name ?? '—' }}</span>
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        class="form-control"
                                        name="items[{{ $index }}][cost_price]"
                                        value="{{ old("items.$index.cost_price", $product->cost_price) }}"
                                        data-cost-price
                                        aria-label="Costo de {{ $product->name }}"
                                    >
                                </div>
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">$</span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="form-control"
                                        name="items[{{ $index }}][price]"
                                        value="{{ old("items.$index.price", $product->price) }}"
                                        required
                                        data-sale-price
                                        aria-label="Valor de venta de {{ $product->name }}"
                                    >
                                </div>
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="form-control"
                                        name="items[{{ $index }}][profit_margin]"
                                        value="{{ old("items.$index.profit_margin", $product->profit_margin) }}"
                                        data-profit-margin
                                        aria-label="Ganancia de {{ $product->name }}"
                                    >
                                    <span class="input-group-text">%</span>
                                </div>
                            </td>
                            <td>
                                <span data-pricing-gain class="text-muted">—</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
            <small class="text-muted">
                Tip: cambiá la ganancia % y el valor de venta se recalcula. Usá “Aplicar” arriba para setear el mismo margen a todos.
            </small>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle"></i> Guardar todos
            </button>
        </div>
    </div>
</form>
@endif
@endsection

@push('styles')
<style>
    .bulk-pricing-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: var(--g50, #f4f7f6);
    }
    .bulk-pricing-row.is-dirty {
        background: rgba(29, 158, 117, 0.06);
    }
    .bulk-pricing-table .form-control {
        min-width: 90px;
    }
    @media (max-width: 768px) {
        .bulk-pricing-table .form-control {
            min-height: 44px;
            font-size: 16px;
        }
    }
</style>
@endpush
