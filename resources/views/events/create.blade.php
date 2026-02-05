@extends('layouts.app')

@section('title', 'Nuevo Evento')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('events.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver al Calendario
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
            <i class="bi bi-plus-circle"></i> Nuevo Evento
        </h1>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Información del Evento</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('events.store') }}" method="POST" id="eventForm">
                    @csrf

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="name" class="form-label">Nombre del Evento *</label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   required
                                   placeholder="Ej: Show Saborido">
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="date" class="form-label">Fecha *</label>
                            <input type="date" 
                                   class="form-control @error('date') is-invalid @enderror" 
                                   id="date" 
                                   name="date" 
                                   value="{{ old('date', $selectedDate) }}" 
                                   required>
                            @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="time" class="form-label">Hora</label>
                            <input type="time" 
                                   class="form-control @error('time') is-invalid @enderror" 
                                   id="time" 
                                   name="time" 
                                   value="{{ old('time') }}">
                            @error('time')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Opcional: hora del evento</small>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="expected_attendance" class="form-label">Asistencia Esperada</label>
                            <input type="number" 
                                   class="form-control @error('expected_attendance') is-invalid @enderror" 
                                   id="expected_attendance" 
                                   name="expected_attendance" 
                                   value="{{ old('expected_attendance') }}"
                                   min="0">
                            @error('expected_attendance')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Número aproximado de personas</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="3"
                                  placeholder="Descripción del evento...">{{ old('description') }}</textarea>
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr>

                    <h5 class="mb-3">Productos Necesarios</h5>
                    <p class="text-muted small mb-3">
                        Selecciona los productos que necesitarás para este evento y la cantidad esperada. 
                        El sistema te alertará si no hay suficiente stock.
                    </p>

                    <div id="productsContainer">
                        <!-- Los productos se agregarán dinámicamente -->
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addProductRow()">
                            <i class="bi bi-plus-circle"></i> Agregar Producto
                        </button>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('events.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Crear Evento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let productCounter = 0;
const products = @json($products);

function addProductRow(productId = null, quantity = 1, notes = '') {
    productCounter++;
    const container = document.getElementById('productsContainer');
    
    const row = document.createElement('div');
    row.className = 'row mb-3 product-row';
    row.id = `productRow_${productCounter}`;
    
    row.innerHTML = `
        <div class="col-md-5">
            <select class="form-select product-select" name="products[${productCounter}][product_id]" required>
                <option value="">Seleccionar producto</option>
                ${products.map(p => `
                    <option value="${p.id}" ${productId == p.id ? 'selected' : ''}>
                        ${p.name} ${p.current_stock !== undefined ? `(Stock actual: ${p.current_stock})` : ''}
                    </option>
                `).join('')}
            </select>
        </div>
        <div class="col-md-3">
            <input type="number" 
                   class="form-control" 
                   name="products[${productCounter}][expected_quantity]" 
                   placeholder="Cantidad" 
                   value="${quantity}"
                   required 
                   min="1">
        </div>
        <div class="col-md-3">
            <input type="text" 
                   class="form-control" 
                   name="products[${productCounter}][notes]" 
                   placeholder="Notas (opcional)"
                   value="${notes}">
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeProductRow(${productCounter})">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    
    container.appendChild(row);
}

function removeProductRow(id) {
    const row = document.getElementById(`productRow_${id}`);
    if (row) {
        row.remove();
    }
}

// Agregar una fila inicial si no hay productos
document.addEventListener('DOMContentLoaded', function() {
    if (productCounter === 0) {
        addProductRow();
    }
});
</script>
@endpush
@endsection

