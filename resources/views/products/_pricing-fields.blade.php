@php
    $pricingProduct = $product ?? null;
    $costValue = old('cost_price', $pricingProduct?->cost_price);
    $saleValue = old('price', $pricingProduct?->price);
    $marginValue = old('profit_margin', $pricingProduct?->profit_margin);
@endphp

<div class="card border mb-3" data-product-pricing>
    <div class="card-header py-3">
        <div>
            <h5 class="mb-1"><i class="bi bi-percent" aria-hidden="true"></i> Costos y rentabilidad</h5>
            <small class="text-muted">Modificá cualquiera de los valores; los demás se calculan automáticamente.</small>
        </div>
    </div>
    <div class="card-body">
        <input type="hidden" name="pricing_source" value="sale" data-pricing-source>

        <div class="row g-3">
            <div class="col-md-4">
                <label for="cost_price" class="form-label">Costo</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        class="form-control @error('cost_price') is-invalid @enderror"
                        id="cost_price"
                        name="cost_price"
                        value="{{ $costValue }}"
                        placeholder="0,00"
                        data-cost-price
                    >
                </div>
                @error('cost_price')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <small class="text-muted">Lo que cuesta adquirir o producir una unidad.</small>
            </div>

            <div class="col-md-4">
                <label for="price" class="form-label">Valor de venta *</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        class="form-control @error('price') is-invalid @enderror"
                        id="price"
                        name="price"
                        value="{{ $saleValue }}"
                        required
                        data-sale-price
                    >
                </div>
                @error('price')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <small class="text-muted">Precio final que verá el cliente.</small>
            </div>

            <div class="col-md-4">
                <label for="profit_margin" class="form-label">Ganancia sobre costo</label>
                <div class="input-group">
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        max="100000"
                        class="form-control @error('profit_margin') is-invalid @enderror"
                        id="profit_margin"
                        name="profit_margin"
                        value="{{ $marginValue }}"
                        placeholder="0,00"
                        data-profit-margin
                    >
                    <span class="input-group-text">%</span>
                </div>
                @error('profit_margin')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <small class="text-muted">Ej.: costo $1 + 100% = venta $2.</small>
            </div>
        </div>

        <div class="alert alert-info mt-3 mb-0 py-2" data-pricing-summary role="status" aria-live="polite">
            Ingresá un costo mayor a cero para calcular la ganancia.
        </div>
    </div>
</div>
