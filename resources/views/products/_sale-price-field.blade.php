@php
    $saleProduct = $product ?? null;
    $saleValue = old('price', $saleProduct?->price);
@endphp

<div class="mb-3">
    <label for="price" class="form-label">Precio de venta *</label>
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
        >
    </div>
    @error('price')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
