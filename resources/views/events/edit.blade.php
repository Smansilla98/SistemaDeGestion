@extends('layouts.app')

@section('title', 'Editar Evento')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('events.show', $event) }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver al Evento
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
            <i class="bi bi-pencil"></i> Editar Evento: {{ $event->name }}
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
                <form action="{{ route('events.update', $event) }}" method="POST" id="eventForm">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="name" class="form-label">Nombre del Evento *</label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $event->name) }}" 
                                   required>
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
                                   value="{{ old('date', $event->date->format('Y-m-d')) }}" 
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
                                   value="{{ old('time', $event->time ? (is_string($event->time) ? $event->time : \Carbon\Carbon::parse($event->time)->format('H:i')) : '') }}">
                            @error('time')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="expected_attendance" class="form-label">Asistencia Esperada</label>
                            <input type="number" 
                                   class="form-control @error('expected_attendance') is-invalid @enderror" 
                                   id="expected_attendance" 
                                   name="expected_attendance" 
                                   value="{{ old('expected_attendance', $event->expected_attendance) }}"
                                   min="0">
                            @error('expected_attendance')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="status" class="form-label">Estado *</label>
                            <select class="form-select @error('status') is-invalid @enderror" 
                                    id="status" 
                                    name="status" 
                                    required>
                                <option value="PROGRAMADO" {{ old('status', $event->status) === 'PROGRAMADO' ? 'selected' : '' }}>Programado</option>
                                <option value="EN_CURSO" {{ old('status', $event->status) === 'EN_CURSO' ? 'selected' : '' }}>En Curso</option>
                                <option value="FINALIZADO" {{ old('status', $event->status) === 'FINALIZADO' ? 'selected' : '' }}>Finalizado</option>
                                <option value="CANCELADO" {{ old('status', $event->status) === 'CANCELADO' ? 'selected' : '' }}>Cancelado</option>
                            </select>
                            @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="3">{{ old('description', $event->description) }}</textarea>
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr>

                    <h5 class="mb-3">Productos Necesarios</h5>

                    <div id="productsContainer">
                        <!-- Los productos se agregarán dinámicamente -->
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addProductRow()">
                            <i class="bi bi-plus-circle"></i> Agregar Producto
                        </button>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('events.show', $event) }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Guardar Cambios
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
const eventProducts = @json($event->products->map(function($p) {
    return [
        'product_id' => $p->id,
        'expected_quantity' => $p->pivot->expected_quantity,
        'notes' => $p->pivot->notes ?? ''
    ];
}));

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
                        ${p.name} ${p.current_stock !== undefined ? `(Stock: ${p.current_stock})` : ''}
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

// Cargar productos del evento al editar
document.addEventListener('DOMContentLoaded', function() {
    if (eventProducts.length > 0) {
        eventProducts.forEach(product => {
            addProductRow(product.product_id, product.expected_quantity, product.notes);
        });
    } else {
        addProductRow();
    }
});
</script>
@endpush
@endsection

