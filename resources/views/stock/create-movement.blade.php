@extends('layouts.app')

@section('title', 'Registrar Movimiento de Inventario')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('stock.movements') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-plus-circle"></i> Registrar Movimiento de Inventario</h1>
        <p class="text-muted">Registra entradas (compras) o salidas de stock</p>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Datos del Movimiento</h5>
            </div>
            <div class="card-body">
                <form id="movementForm" method="POST" action="{{ route('stock.store-movement') }}">
                    @csrf

                    <!-- Producto -->
                    <div class="mb-3">
                        <label for="product_id" class="form-label">Producto <span class="text-danger">*</span></label>
                        <select class="form-select @error('product_id') is-invalid @enderror" id="product_id" name="product_id" required>
                            <option value="">Seleccionar producto...</option>
                            @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('product_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Tipo de Movimiento -->
                    <div class="mb-3">
                        <label for="type" class="form-label">Tipo de Movimiento <span class="text-danger">*</span></label>
                        <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                            <option value="">Seleccionar tipo...</option>
                            <option value="ENTRADA" {{ old('type') == 'ENTRADA' ? 'selected' : '' }}>Entrada (Compra)</option>
                            <option value="SALIDA" {{ old('type') == 'SALIDA' ? 'selected' : '' }}>Salida</option>
                            <option value="AJUSTE" {{ old('type') == 'AJUSTE' ? 'selected' : '' }}>Ajuste</option>
                        </select>
                        @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Cantidad -->
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Cantidad <span class="text-danger">*</span></label>
                        <input type="number" 
                               class="form-control @error('quantity') is-invalid @enderror" 
                               id="quantity" 
                               name="quantity" 
                               min="1" 
                               step="1"
                               value="{{ old('quantity') }}" 
                               required>
                        @error('quantity')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Campos para ENTRADA (Compra) - Se muestran dinámicamente -->
                    <div id="purchaseFields" style="display: none;">
                        <hr class="my-4">
                        <h6 class="mb-3"><i class="bi bi-cart-plus"></i> Información de Compra</h6>

                        <!-- Proveedor -->
                        <div class="mb-3">
                            <label for="supplier_id" class="form-label">Proveedor <span class="text-danger">*</span></label>
                            <div class="d-flex gap-2">
                                <select class="form-select @error('supplier_id') is-invalid @enderror" id="supplier_id" name="supplier_id">
                                    <option value="">Seleccionar proveedor...</option>
                                    @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-primary" onclick="toggleNewSupplier()">
                                    <i class="bi bi-plus-circle"></i> Nuevo
                                </button>
                            </div>
                            @error('supplier_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">O crea un nuevo proveedor</small>
                        </div>

                        <!-- Formulario de nuevo proveedor -->
                        <div id="newSupplierFields" style="display: none;" class="mb-3 p-3 border rounded bg-light">
                            <h6 class="mb-3">Nuevo Proveedor</h6>
                            <div class="mb-2">
                                <label for="new_supplier_name" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="new_supplier_name" name="new_supplier_name" placeholder="Ej: Distribuidora X">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label for="new_supplier_contact" class="form-label">Contacto</label>
                                    <input type="text" class="form-control" id="new_supplier_contact" name="new_supplier_contact" placeholder="Nombre del contacto">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label for="new_supplier_phone" class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" id="new_supplier_phone" name="new_supplier_phone" placeholder="Ej: 11-1234-5678">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label for="new_supplier_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="new_supplier_email" name="new_supplier_email" placeholder="proveedor@ejemplo.com">
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="toggleNewSupplier()">Cancelar</button>
                        </div>

                        <!-- Costo Unitario -->
                        <div class="mb-3">
                            <label for="unit_cost" class="form-label">Costo Unitario <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" 
                                       class="form-control @error('unit_cost') is-invalid @enderror" 
                                       id="unit_cost" 
                                       name="unit_cost" 
                                       min="0" 
                                       step="0.01"
                                       value="{{ old('unit_cost') }}" 
                                       placeholder="0.00">
                            </div>
                            @error('unit_cost')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Fecha de Compra -->
                        <div class="mb-3">
                            <label for="purchase_date" class="form-label">Fecha de Compra <span class="text-danger">*</span></label>
                            <input type="date" 
                                   class="form-control @error('purchase_date') is-invalid @enderror" 
                                   id="purchase_date" 
                                   name="purchase_date" 
                                   max="{{ date('Y-m-d') }}"
                                   value="{{ old('purchase_date', date('Y-m-d')) }}" 
                                   required>
                            @error('purchase_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">No puede ser una fecha futura</small>
                        </div>

                        <!-- Número de Factura/Remito -->
                        <div class="mb-3">
                            <label for="invoice_number" class="form-label">Número de Factura/Remito</label>
                            <input type="text" 
                                   class="form-control @error('invoice_number') is-invalid @enderror" 
                                   id="invoice_number" 
                                   name="invoice_number" 
                                   value="{{ old('invoice_number') }}" 
                                   placeholder="Ej: FACT-001234">
                            @error('invoice_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Notas de Compra -->
                        <div class="mb-3">
                            <label for="purchase_notes" class="form-label">Notas de Compra</label>
                            <textarea class="form-control @error('purchase_notes') is-invalid @enderror" 
                                      id="purchase_notes" 
                                      name="purchase_notes" 
                                      rows="2"
                                      placeholder="Observaciones adicionales sobre la compra...">{{ old('purchase_notes') }}</textarea>
                            @error('purchase_notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Resumen de Costo Total -->
                        <div class="alert alert-info">
                            <strong>Costo Total:</strong> $<span id="totalCost">0.00</span>
                            <small class="d-block text-muted">Cantidad × Costo Unitario</small>
                        </div>
                    </div>

                    <!-- Motivo (Opcional) -->
                    <div class="mb-3">
                        <label for="reason" class="form-label">Motivo</label>
                        <input type="text" 
                               class="form-control @error('reason') is-invalid @enderror" 
                               id="reason" 
                               name="reason" 
                               value="{{ old('reason') }}" 
                               placeholder="Ej: Reposición de stock, Venta, etc.">
                        @error('reason')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Referencia (Opcional) -->
                    <div class="mb-3">
                        <label for="reference" class="form-label">Referencia</label>
                        <input type="text" 
                               class="form-control @error('reference') is-invalid @enderror" 
                               id="reference" 
                               name="reference" 
                               value="{{ old('reference') }}" 
                               placeholder="Ej: Número de orden, remito, etc.">
                        @error('reference')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('stock.movements') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="bi bi-check-circle"></i> Registrar Movimiento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Información</h6>
            </div>
            <div class="card-body">
                <h6>Tipos de Movimiento:</h6>
                <ul class="small">
                    <li><strong>Entrada:</strong> Registra una compra al proveedor. Requiere costo y proveedor.</li>
                    <li><strong>Salida:</strong> Reduce stock (ej: venta, pérdida).</li>
                    <li><strong>Ajuste:</strong> Corrige el stock a un valor específico.</li>
                </ul>
                <hr>
                <h6>Para Entradas:</h6>
                <ul class="small">
                    <li>Debes seleccionar o crear un proveedor</li>
                    <li>El costo unitario es obligatorio</li>
                    <li>La fecha de compra no puede ser futura</li>
                    <li>El stock se actualizará automáticamente</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const purchaseFields = document.getElementById('purchaseFields');
    const quantityInput = document.getElementById('quantity');
    const unitCostInput = document.getElementById('unit_cost');
    const totalCostSpan = document.getElementById('totalCost');
    const supplierIdSelect = document.getElementById('supplier_id');
    const newSupplierFields = document.getElementById('newSupplierFields');
    const form = document.getElementById('movementForm');
    const submitBtn = document.getElementById('submitBtn');

    // Mostrar/ocultar campos de compra según el tipo
    function togglePurchaseFields() {
        if (typeSelect.value === 'ENTRADA') {
            purchaseFields.style.display = 'block';
            // Hacer campos requeridos
            supplierIdSelect.setAttribute('required', 'required');
            unitCostInput.setAttribute('required', 'required');
            document.getElementById('purchase_date').setAttribute('required', 'required');
        } else {
            purchaseFields.style.display = 'none';
            // Quitar requeridos
            supplierIdSelect.removeAttribute('required');
            unitCostInput.removeAttribute('required');
            document.getElementById('purchase_date').removeAttribute('required');
            // Limpiar campos
            supplierIdSelect.value = '';
            unitCostInput.value = '';
            document.getElementById('purchase_date').value = '{{ date('Y-m-d') }}';
            document.getElementById('invoice_number').value = '';
            document.getElementById('purchase_notes').value = '';
            newSupplierFields.style.display = 'none';
            document.getElementById('new_supplier_name').value = '';
        }
        calculateTotalCost();
    }

    // Calcular costo total
    function calculateTotalCost() {
        const quantity = parseFloat(quantityInput.value) || 0;
        const unitCost = parseFloat(unitCostInput.value) || 0;
        const total = quantity * unitCost;
        totalCostSpan.textContent = total.toFixed(2);
    }

    // Toggle formulario de nuevo proveedor
    window.toggleNewSupplier = function() {
        const isVisible = newSupplierFields.style.display !== 'none';
        newSupplierFields.style.display = isVisible ? 'none' : 'block';
        if (!isVisible) {
            supplierIdSelect.value = '';
            supplierIdSelect.removeAttribute('required');
            document.getElementById('new_supplier_name').setAttribute('required', 'required');
        } else {
            document.getElementById('new_supplier_name').removeAttribute('required');
            supplierIdSelect.setAttribute('required', 'required');
        }
    }

    // Event listeners
    typeSelect.addEventListener('change', togglePurchaseFields);
    quantityInput.addEventListener('input', calculateTotalCost);
    unitCostInput.addEventListener('input', calculateTotalCost);

    // Validación antes de enviar
    form.addEventListener('submit', function(e) {
        if (typeSelect.value === 'ENTRADA') {
            const hasSupplier = supplierIdSelect.value || document.getElementById('new_supplier_name').value;
            if (!hasSupplier) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Proveedor Requerido',
                    text: 'Debe seleccionar o crear un proveedor para registrar una entrada.',
                    confirmButtonColor: 'var(--conurbania-danger)'
                });
                return false;
            }
        }

        // Mostrar loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Registrando...';
    });

    // Inicializar
    togglePurchaseFields();
});
</script>
@endpush
@endsection

