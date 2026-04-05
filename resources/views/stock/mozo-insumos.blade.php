@extends('layouts.app')

@section('title', 'Ingreso de insumos')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="text-white mb-2 page-hero-title"><i class="bi bi-box-seam"></i> Ingreso de insumos</h1>
        <p class="text-muted mb-0">Registrá la entrada de insumos al depósito (sin datos de compra). Solo aplica a ítems marcados como insumo con control de stock.</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
@endif

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Nuevo ingreso</h5>
            </div>
            <div class="card-body">
                @if($products->isEmpty())
                    <p class="text-muted mb-0">No hay insumos con stock activo. Pedile a un administrador que cargue insumos con <strong>tipo INSUMO</strong> y <strong>control de stock</strong>.</p>
                @else
                <form method="POST" action="{{ route('stock.mozo-insumos.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="product_id" class="form-label">Insumo <span class="text-danger">*</span></label>
                        <select class="form-select @error('product_id') is-invalid @enderror" id="product_id" name="product_id" required>
                            <option value="">Seleccionar…</option>
                            @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                            @endforeach
                        </select>
                        @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Cantidad <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('quantity') is-invalid @enderror" id="quantity" name="quantity" min="1" step="1" value="{{ old('quantity') }}" required>
                        @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Observación (opcional)</label>
                        <input type="text" class="form-control @error('reason') is-invalid @enderror" id="reason" name="reason" value="{{ old('reason') }}" placeholder="Ej: Recepción depósito, devolución barra…" maxlength="255">
                        @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Registrar ingreso
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
