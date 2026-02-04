@extends('layouts.app')

@section('title', 'Control de Stock')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-inboxes"></i> Control de Stock</h1>
    </div>
</div>

@if(count($lowStockAlerts) > 0)
<div class="alert alert-warning mb-4">
    <h5><i class="bi bi-exclamation-triangle"></i> Alertas de Stock Bajo</h5>
    <ul class="mb-0">
        @foreach($lowStockAlerts as $alert)
        <li>
            <strong>{{ $alert['product_name'] }}</strong>: 
            Stock actual: {{ $alert['current_stock'] }} | 
            Mínimo: {{ $alert['minimum_stock'] }}
        </li>
        @endforeach
    </ul>
</div>
@endif

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Productos con Stock</h5>
        <a href="{{ route('stock.movements') }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-list-ul"></i> Ver Movimientos
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Stock Actual</th>
                        <th>Stock Mínimo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr class="{{ $product->is_low_stock ? 'table-warning' : '' }}">
                        <td><strong>{{ $product->name }}</strong></td>
                        <td>
                            @if($product->category)
                                {{ $product->category->name }}
                            @else
                                <span class="text-muted">Sin categoría</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $product->current_stock <= $product->stock_minimum ? 'danger' : 'success' }}">
                                {{ $product->current_stock }}
                            </span>
                        </td>
                        <td>{{ $product->stock_minimum }}</td>
                        <td>
                            @if($product->is_low_stock)
                            <span class="badge bg-warning">Stock Bajo</span>
                            @else
                            <span class="badge bg-success">Normal</span>
                            @endif
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary" onclick="openMovementModal({{ $product->id }}, '{{ addslashes($product->name) }}')">
                                <i class="bi bi-plus-circle"></i> Movimiento
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No hay productos con stock configurado</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para movimiento (fuera del loop) -->
<div class="modal fade" id="movementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="movementForm" action="{{ route('stock.store-movement') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="movementModalTitle">Registrar Movimiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="movementProductId">
                    
                    <div class="mb-3">
                        <label for="movementType" class="form-label">Tipo de Movimiento</label>
                        <select class="form-select" id="movementType" name="type" required>
                            <option value="ENTRADA">Entrada</option>
                            <option value="SALIDA">Salida</option>
                            <option value="AJUSTE">Ajuste</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="movementQuantity" class="form-label">Cantidad</label>
                        <input type="number" class="form-control" id="movementQuantity" name="quantity" required min="1" step="1">
                    </div>

                    <div class="mb-3">
                        <label for="movementReason" class="form-label">Motivo</label>
                        <input type="text" class="form-control" id="movementReason" name="reason" placeholder="Opcional">
                    </div>

                    <div class="mb-3">
                        <label for="movementReference" class="form-label">Referencia</label>
                        <input type="text" class="form-control" id="movementReference" name="reference" placeholder="Opcional">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Registrar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openMovementModal(productId, productName) {
    // Establecer valores del modal
    document.getElementById('movementProductId').value = productId;
    document.getElementById('movementModalTitle').textContent = 'Registrar Movimiento - ' + productName;
    
    // Resetear el formulario
    document.getElementById('movementForm').reset();
    document.getElementById('movementProductId').value = productId; // Restaurar el product_id después del reset
    
    // Resetear valores por defecto
    document.getElementById('movementType').value = 'ENTRADA';
    document.getElementById('movementQuantity').value = '';
    document.getElementById('movementReason').value = '';
    document.getElementById('movementReference').value = '';
    
    // Mostrar el modal
    const modal = new bootstrap.Modal(document.getElementById('movementModal'));
    modal.show();
}

// Manejar el submit del formulario
document.getElementById('movementForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    
    // Deshabilitar botón y mostrar loading
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Registrando...';
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(response => {
        if (response.redirected) {
            // Si hay una redirección, seguirla
            window.location.href = response.url;
            return;
        }
        return response.json().catch(() => ({}));
    })
    .then(data => {
        if (data.success || data.message) {
            // Cerrar el modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('movementModal'));
            modal.hide();
            
            // Mostrar mensaje de éxito
            Swal.fire({
                icon: 'success',
                title: 'Movimiento Registrado',
                text: data.message || 'El movimiento de stock se ha registrado correctamente.',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                // Recargar la página para actualizar los datos
                window.location.reload();
            });
        } else {
            throw new Error(data.message || 'Error al registrar el movimiento');
        }
    })
    .catch(error => {
        // Restaurar botón
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
        
        // Mostrar error
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Ocurrió un error al registrar el movimiento. Por favor, intenta nuevamente.',
        });
    });
});
</script>
@endpush
@endsection

