@extends('layouts.app')

@section('title', 'Gestión de Mesas')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-table"></i> Gestión de Mesas</h1>
            <p class="text-muted">Administra las mesas del restaurante</p>
        </div>
        <div>
            @can('create', App\Models\Table::class)
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTableModal">
                <i class="bi bi-plus-circle"></i> Nueva Mesa
            </button>
            @endcan
            <a href="{{ route('tables.layout') }}" class="btn btn-outline-primary">
                <i class="bi bi-diagram-3"></i> Layout Visual
            </a>
        </div>
    </div>
</div>

@foreach($sectors as $sector)
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-door-open"></i> {{ $sector->name }}
            @if($sector->description)
                <small class="text-muted">- {{ $sector->description }}</small>
            @endif
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            @forelse($sector->tables as $table)
            <div class="col-md-3 mb-3">
                <div class="card h-100 border-{{ $table->status === 'LIBRE' ? 'success' : ($table->status === 'OCUPADA' ? 'warning' : 'secondary') }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0">{{ $table->number }}</h5>
                            @can('update', $table)
                            <button type="button" 
                                    class="badge bg-{{ $table->status === 'LIBRE' ? 'success' : ($table->status === 'OCUPADA' ? 'warning' : 'secondary') }} border-0"
                                    style="cursor: pointer;"
                                    onclick="openChangeStatusModal({{ $table->id }}, '{{ $table->status }}', {{ $table->capacity }})">
                                {{ $table->status }}
                            </button>
                            @else
                            <span class="badge bg-{{ $table->status === 'LIBRE' ? 'success' : ($table->status === 'OCUPADA' ? 'warning' : 'secondary') }}">
                                {{ $table->status }}
                            </span>
                            @endcan
                        </div>
                        <p class="text-muted mb-2">
                            <i class="bi bi-people"></i> Capacidad: {{ $table->capacity }} personas
                        </p>
                        @if($table->status === 'OCUPADA' && $table->currentSession && $table->currentSession->waiter)
                        <p class="mb-2">
                            <span class="badge bg-info">
                                <i class="bi bi-person-badge"></i> Mozo: {{ $table->currentSession->waiter->name }}
                            </span>
                        </p>
                        @endif
                        @if($table->status === 'OCUPADA' || $table->status === 'LIBRE')
                        <p class="mb-2">
                            <a href="{{ route('tables.orders', $table) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-receipt"></i> Ver Pedidos
                            </a>
                        </p>
                        @endif
                        <div class="d-flex gap-2 flex-wrap">
                            @if($table->status === 'LIBRE')
                            {{-- Mesa LIBRE: Solo puede reservar o cambiar estado a OCUPADA --}}
                            <a href="{{ route('tables.reserve', $table) }}" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-calendar-check"></i> Reservar
                            </a>
                            @can('update', $table)
                            <button type="button" 
                                    class="btn btn-sm btn-primary"
                                    onclick="openChangeStatusModal({{ $table->id }}, '{{ $table->status }}', {{ $table->capacity }})">
                                <i class="bi bi-check-circle"></i> Marcar Ocupada
                            </button>
                            @endcan
                            @elseif($table->status === 'OCUPADA')
                            {{-- Mesa OCUPADA: Puede tomar pedidos o cerrar mesa --}}
                            @if(in_array(auth()->user()->role, ['ADMIN', 'MOZO']))
                            <button type="button"
                                    class="btn btn-sm btn-primary"
                                    onclick="openNewOrderModal({{ $table->id }}, '{{ $table->number }}')">
                                <i class="bi bi-plus-circle"></i> Nuevo Pedido
                            </button>
                            @endif
                            <a href="{{ route('tables.orders', $table) }}" class="btn btn-sm btn-warning">
                                <i class="bi bi-receipt"></i> Ver Pedidos
                            </a>
                            @can('update', $table)
                            <form action="{{ route('tables.close', $table) }}" method="POST" class="d-inline" id="closeTableForm{{ $table->id }}">
                                @csrf
                                <button type="button" class="btn btn-sm btn-success" onclick="confirmCloseTable({{ $table->id }})">
                                    <i class="bi bi-check-circle"></i> Cerrar Mesa
                                </button>
                            </form>
                            @endcan
                            @elseif($table->status === 'RESERVADA')
                            {{-- Mesa RESERVADA: Puede cambiar estado --}}
                            @can('update', $table)
                            <button type="button" 
                                    class="btn btn-sm btn-primary"
                                    onclick="openChangeStatusModal({{ $table->id }}, '{{ $table->status }}', {{ $table->capacity }})">
                                <i class="bi bi-check-circle"></i> Cambiar Estado
                            </button>
                            @endcan
                            @endif
                            @can('update', $table)
                            <a href="{{ route('tables.edit', $table) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <p class="text-muted text-center">No hay mesas en este sector</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endforeach

@can('create', App\Models\Table::class)
<!-- Modal Crear Mesa -->
<div class="modal fade" id="createTableModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('tables.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Mesa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="sector_id" class="form-label">Sector</label>
                        <select class="form-select" id="sector_id" name="sector_id" required>
                            <option value="">Seleccionar sector</option>
                            @foreach($sectors as $sector)
                            <option value="{{ $sector->id }}">{{ $sector->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="number" class="form-label">Número de Mesa</label>
                        <input type="text" class="form-control" id="number" name="number" required>
                    </div>
                    <div class="mb-3">
                        <label for="capacity" class="form-label">Capacidad</label>
                        <input type="number" class="form-control" id="capacity" name="capacity" min="1" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="position_x" class="form-label">Posición X (opcional)</label>
                            <input type="number" class="form-control" id="position_x" name="position_x">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="position_y" class="form-label">Posición Y (opcional)</label>
                            <input type="number" class="form-control" id="position_y" name="position_y">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Mesa</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@if(in_array(auth()->user()->role, ['ADMIN', 'MOZO']))
<!-- Modal Cambiar Estado de Mesa -->
<div class="modal fade" id="changeStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="changeStatusForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Cambiar Estado de Mesa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="status" class="form-label">Estado</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="LIBRE">Libre</option>
                            <option value="OCUPADA">Ocupada</option>
                            <option value="RESERVADA">Reservada</option>
                            <option value="CERRADA">Cerrada</option>
                        </select>
                        <small class="text-muted" id="statusHelp"></small>
                    </div>
                    <div class="mb-3" id="waiterContainer" style="display: none;">
                        <label for="waiter_id" class="form-label">Asignar Mozo <span class="text-danger">*</span></label>
                        <select class="form-select" id="waiter_id" name="waiter_id">
                            <option value="">Seleccionar mozo...</option>
                            @foreach($waiters as $waiter)
                            <option value="{{ $waiter->id }}">{{ $waiter->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Selecciona el mozo que atenderá esta mesa</small>
                    </div>
                    <div class="mb-3" id="guestsCountContainer">
                        <label for="guests_count" class="form-label">Cantidad de Personas</label>
                        <input type="number" class="form-control" id="guests_count" name="guests_count" min="1" value="1">
                        <small class="text-muted">Capacidad máxima: <span id="maxCapacity">0</span> personas</small>
                    </div>
                    <input type="hidden" id="tableId" name="table_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@if(in_array(auth()->user()->role, ['ADMIN', 'MOZO']))
<!-- Modal Nuevo Pedido (desde Mesas) -->
<div class="modal fade" id="newOrderModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form id="newOrderForm">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle"></i> Nuevo Pedido
                        <small class="text-muted">- <span id="newOrderTableLabel"></span></small>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="newOrderTableId" />

                    <div class="row g-3">
                        <div class="col-lg-7">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0"><i class="bi bi-card-list"></i> Productos</h6>
                                <input type="text" class="form-control form-control-sm" id="productSearch" placeholder="Buscar producto..." style="max-width: 260px;">
                            </div>

                            <div class="accordion" id="productsAccordion">
                                @foreach($products as $categoryName => $categoryProducts)
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading-{{ \Illuminate\Support\Str::slug($categoryName) }}">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ \Illuminate\Support\Str::slug($categoryName) }}">
                                                {{ $categoryName }}
                                                <span class="badge bg-secondary ms-2">{{ $categoryProducts->count() }}</span>
                                            </button>
                                        </h2>
                                        <div id="collapse-{{ \Illuminate\Support\Str::slug($categoryName) }}" class="accordion-collapse collapse" data-bs-parent="#productsAccordion">
                                            <div class="accordion-body">
                                                <div class="row">
                                                    @foreach($categoryProducts as $product)
                                                        @php
                                                            $currentStock = $product->has_stock ? $product->getCurrentStock(auth()->user()->restaurant_id) : null;
                                                            $isOutOfStock = $currentStock !== null && $currentStock <= 0;
                                                            $isLowStock = $currentStock !== null && $currentStock > 0 && $currentStock <= $product->stock_minimum;
                                                        @endphp
                                                        <div class="col-md-6 mb-2 product-item" data-name="{{ strtolower($product->name) }}">
                                                            <div class="d-flex justify-content-between align-items-center border rounded p-2 {{ $isOutOfStock ? 'border-danger bg-light' : ($isLowStock ? 'border-warning bg-light' : '') }}">
                                                                <div class="me-2 flex-grow-1">
                                                                    <div class="d-flex align-items-center gap-2">
                                                                        <strong>{{ $product->name }}</strong>
                                                                        @if($isOutOfStock)
                                                                            <span class="badge bg-danger" title="Sin stock disponible">
                                                                                <i class="bi bi-x-circle-fill"></i> Sin Stock
                                                                            </span>
                                                                        @elseif($isLowStock)
                                                                            <span class="badge bg-warning text-dark" title="Stock bajo">
                                                                                <i class="bi bi-exclamation-circle-fill"></i> Stock Bajo
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                    @if($product->description)
                                                                        <div class="text-muted small">{{ $product->description }}</div>
                                                                    @endif
                                                                    @if($product->has_stock && $currentStock !== null)
                                                                        <div class="small mt-1">
                                                                            <span class="badge bg-{{ $isOutOfStock ? 'danger' : ($isLowStock ? 'warning' : 'success') }}">
                                                                                Stock: {{ $currentStock }}
                                                                                @if($product->stock_minimum > 0)
                                                                                    (Mín: {{ $product->stock_minimum }})
                                                                                @endif
                                                                            </span>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                <div class="text-end">
                                                                    <div><strong>${{ number_format($product->price, 2) }}</strong></div>
                                                                    <button type="button"
                                                                            class="btn btn-sm btn-outline-primary mt-1 {{ $isOutOfStock ? 'disabled' : '' }}"
                                                                            onclick="addModalItem({{ $product->id }}, '{{ addslashes($product->name) }}', {{ (float) $product->price }}, {{ $currentStock ?? 'null' }}, {{ $product->stock_minimum ?? 0 }})"
                                                                            {{ $isOutOfStock ? 'disabled title="Producto sin stock"' : '' }}>
                                                                        <i class="bi bi-plus"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <h6 class="mb-2"><i class="bi bi-receipt"></i> Pedido</h6>

                            <div class="mb-2">
                                <label class="form-label">Observaciones (opcional)</label>
                                <textarea class="form-control" id="newOrderObservations" rows="2" placeholder="Ej: sin sal, alergias, etc."></textarea>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="sendToKitchen" checked>
                                <label class="form-check-label" for="sendToKitchen">Enviar a cocina al confirmar</label>
                            </div>

                            <div id="modalItemsEmpty" class="text-muted">No hay items en el pedido.</div>
                            <div id="modalItemsList"></div>

                            <div class="border-top pt-3 mt-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Total:</strong> <span id="modalTotal">$0.00</span>
                                </div>
                                <button type="submit" class="btn btn-success" id="confirmOrderBtn" disabled>
                                    <i class="bi bi-check-circle"></i> Confirmar Pedido
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
function openChangeStatusModal(tableId, currentStatus, capacity) {
    const modal = new bootstrap.Modal(document.getElementById('changeStatusModal'));
    const form = document.getElementById('changeStatusForm');
    const statusSelect = document.getElementById('status');
    const guestsInput = document.getElementById('guests_count');
    const guestsContainer = document.getElementById('guestsCountContainer');
    const waiterContainer = document.getElementById('waiterContainer');
    const waiterSelect = document.getElementById('waiter_id');
    const tableIdInput = document.getElementById('tableId');
    const maxCapacitySpan = document.getElementById('maxCapacity');
    const statusHelp = document.getElementById('statusHelp');
    
    // Configurar formulario
    form.action = `/tables/${tableId}/status`;
    tableIdInput.value = tableId;
    maxCapacitySpan.textContent = capacity;
    guestsInput.max = capacity;
    
    // Limpiar event listeners anteriores clonando el select
    const newStatusSelect = statusSelect.cloneNode(true);
    statusSelect.parentNode.replaceChild(newStatusSelect, statusSelect);
    const newStatusSelectRef = document.getElementById('status');
    
    // Función para mostrar/ocultar campos según el estado
    function updateFieldsForStatus(status) {
        if (status === 'OCUPADA') {
            guestsContainer.style.display = 'block';
            guestsInput.required = true;
            if (!guestsInput.value || guestsInput.value === '0') {
                guestsInput.value = 1;
            }
            waiterContainer.style.display = 'block';
            waiterSelect.required = true;
        } else if (status === 'LIBRE') {
            guestsContainer.style.display = 'none';
            guestsInput.required = false;
            guestsInput.value = 0;
            waiterContainer.style.display = 'none';
            waiterSelect.required = false;
            waiterSelect.value = '';
        } else if (status === 'RESERVADA') {
            guestsContainer.style.display = 'block';
            guestsInput.required = true;
            if (!guestsInput.value || guestsInput.value === '0') {
                guestsInput.value = 1;
            }
            waiterContainer.style.display = 'none';
            waiterSelect.required = false;
            waiterSelect.value = '';
        } else {
            guestsContainer.style.display = 'none';
            guestsInput.required = false;
            guestsInput.value = 0;
            waiterContainer.style.display = 'none';
            waiterSelect.required = false;
            waiterSelect.value = '';
        }
    }
    
    // Configurar opciones según el estado actual
    newStatusSelectRef.innerHTML = '';
    
    if (currentStatus === 'LIBRE') {
        // Si está LIBRE, solo puede cambiar a OCUPADA o RESERVADA
        newStatusSelectRef.innerHTML = `
            <option value="OCUPADA">Ocupada</option>
            <option value="RESERVADA">Reservada</option>
        `;
        statusHelp.textContent = 'Una mesa libre solo puede marcarse como Ocupada o Reservada.';
        guestsContainer.style.display = 'block';
        guestsInput.required = true;
        guestsInput.value = 1;
        waiterContainer.style.display = 'none';
        waiterSelect.required = false;
        waiterSelect.value = '';
    } else if (currentStatus === 'OCUPADA') {
        // Si está OCUPADA, puede cambiar a LIBRE o CERRADA
        newStatusSelectRef.innerHTML = `
            <option value="LIBRE">Libre</option>
            <option value="CERRADA">Cerrada</option>
        `;
        statusHelp.textContent = 'Para cerrar la mesa y generar el recibo, usa el botón "Cerrar Mesa" en lugar de cambiar el estado.';
        guestsContainer.style.display = 'none';
        guestsInput.required = false;
        guestsInput.value = 0;
        waiterContainer.style.display = 'none';
        waiterSelect.required = false;
        waiterSelect.value = '';
    } else if (currentStatus === 'RESERVADA') {
        // Si está RESERVADA, puede cambiar a OCUPADA o LIBRE
        newStatusSelectRef.innerHTML = `
            <option value="LIBRE">Libre</option>
            <option value="OCUPADA">Ocupada</option>
        `;
        statusHelp.textContent = 'Una mesa reservada puede marcarse como Ocupada o Libre.';
        guestsContainer.style.display = 'block';
        guestsInput.required = true;
        guestsInput.value = 1;
        waiterContainer.style.display = 'none';
        waiterSelect.required = false;
        waiterSelect.value = '';
    } else {
        // Cualquier otro estado, permitir todos los cambios
        newStatusSelectRef.innerHTML = `
            <option value="LIBRE">Libre</option>
            <option value="OCUPADA">Ocupada</option>
            <option value="RESERVADA">Reservada</option>
            <option value="CERRADA">Cerrada</option>
        `;
        statusHelp.textContent = '';
        guestsContainer.style.display = 'block';
        guestsInput.required = true;
        guestsInput.value = 1;
        waiterContainer.style.display = 'none';
        waiterSelect.required = false;
        waiterSelect.value = '';
    }
    
    // Actualizar cuando cambie el estado seleccionado
    newStatusSelectRef.addEventListener('change', function() {
        updateFieldsForStatus(this.value);
    });
    
    // Verificar el estado inicial después de configurar las opciones
    setTimeout(() => {
        const initialValue = newStatusSelectRef.value;
        updateFieldsForStatus(initialValue);
    }, 50);
    
    // También verificar cuando el modal se muestra completamente
    const modalElement = document.getElementById('changeStatusModal');
    const handleShown = function() {
        const currentValue = newStatusSelectRef.value;
        updateFieldsForStatus(currentValue);
        modalElement.removeEventListener('shown.bs.modal', handleShown);
    };
    modalElement.addEventListener('shown.bs.modal', handleShown);
    
    modal.show();
}

function editTable(tableId) {
    // TODO: Implementar edición de mesa
    Swal.fire({
        icon: 'info',
        title: 'Editar Mesa',
        text: 'Funcionalidad de edición de mesa ' + tableId + ' en desarrollo',
        confirmButtonColor: '#1e8081',
        confirmButtonText: 'Entendido'
    });
}

function confirmCloseTable(tableId) {
    Swal.fire({
        icon: 'question',
        title: '¿Cerrar Mesa?',
        text: 'Se cerrarán todos los pedidos activos y se generará el recibo consolidado.',
        showCancelButton: true,
        confirmButtonColor: '#1e8081',
        cancelButtonColor: '#7b7d84',
        confirmButtonText: 'Sí, cerrar mesa',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('closeTableForm' + tableId).submit();
        }
    });
}

// ===== Modal Nuevo Pedido =====
let newOrderModal;
let modalItems = [];
let modalItemCounter = 0;

function openNewOrderModal(tableId, tableLabel) {
    if (!newOrderModal) {
        newOrderModal = new bootstrap.Modal(document.getElementById('newOrderModal'));
    }

    document.getElementById('newOrderTableId').value = tableId;
    document.getElementById('newOrderTableLabel').textContent = tableLabel;
    document.getElementById('newOrderObservations').value = '';
    document.getElementById('sendToKitchen').checked = true;
    document.getElementById('productSearch').value = '';

    modalItems = [];
    modalItemCounter = 0;
    renderModalItems();
    filterProducts('');

    newOrderModal.show();
}

function addModalItem(productId, name, price, currentStock, stockMinimum) {
    // Validar stock si aplica
    if (currentStock !== null && currentStock !== undefined) {
        const existing = modalItems.find(i => i.product_id === productId);
        const requestedQty = existing ? existing.quantity + 1 : 1;
        
        if (currentStock <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Sin Stock',
                text: `El producto "${name}" no tiene stock disponible.`,
                confirmButtonColor: '#c94a2d',
                confirmButtonText: 'Entendido'
            });
            return;
        }
        
        if (requestedQty > currentStock) {
            Swal.fire({
                icon: 'warning',
                title: 'Stock Insuficiente',
                html: `El producto "${name}" tiene stock limitado.<br><strong>Disponible: ${currentStock}</strong><br>Solicitado: ${requestedQty}`,
                confirmButtonColor: '#ffc107',
                confirmButtonText: 'Entendido'
            });
            return;
        }
        
        if (currentStock <= stockMinimum && stockMinimum > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Stock Bajo',
                html: `El producto "${name}" tiene stock bajo.<br><strong>Disponible: ${currentStock}</strong><br>Mínimo recomendado: ${stockMinimum}`,
                confirmButtonColor: '#ffc107',
                confirmButtonText: 'Continuar',
                showCancelButton: true,
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    addItemToModal(productId, name, price, existing);
                }
            });
            return;
        }
    }
    
    addItemToModal(productId, name, price, modalItems.find(i => i.product_id === productId));
}

function addItemToModal(productId, name, price, existing) {
    if (existing) {
        existing.quantity += 1;
    } else {
        modalItemCounter++;
        modalItems.push({
            id: modalItemCounter,
            product_id: productId,
            name,
            price,
            quantity: 1,
            observations: ''
        });
    }
    renderModalItems();
}

function removeModalItem(itemId) {
    modalItems = modalItems.filter(i => i.id !== itemId);
    renderModalItems();
}

function updateModalQty(itemId, qty) {
    const item = modalItems.find(i => i.id === itemId);
    if (!item) return;
    item.quantity = Math.max(1, parseInt(qty || '1', 10));
    renderModalItems();
}

function updateModalObs(itemId, obs) {
    const item = modalItems.find(i => i.id === itemId);
    if (!item) return;
    item.observations = obs || '';
}

function renderModalItems() {
    const list = document.getElementById('modalItemsList');
    const empty = document.getElementById('modalItemsEmpty');
    const totalEl = document.getElementById('modalTotal');
    const btn = document.getElementById('confirmOrderBtn');

    if (modalItems.length === 0) {
        list.innerHTML = '';
        empty.style.display = 'block';
        totalEl.textContent = '$0.00';
        btn.disabled = true;
        return;
    }

    empty.style.display = 'none';
    btn.disabled = false;

    let total = 0;
    list.innerHTML = modalItems.map(item => {
        const subtotal = item.price * item.quantity;
        total += subtotal;
        return `
            <div class="border rounded p-2 mb-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="me-2">
                        <strong>${item.name}</strong>
                        <div class="text-muted small">$${item.price.toFixed(2)} c/u</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeModalItem(${item.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <div class="row g-2 mt-2">
                    <div class="col-4">
                        <label class="small">Cant.</label>
                        <input type="number" class="form-control form-control-sm" min="1" value="${item.quantity}"
                               onchange="updateModalQty(${item.id}, this.value)">
                    </div>
                    <div class="col-8">
                        <label class="small">Obs. (opcional)</label>
                        <input type="text" class="form-control form-control-sm" value="${item.observations || ''}"
                               oninput="updateModalObs(${item.id}, this.value)">
                    </div>
                </div>
                <div class="text-end mt-2"><strong>Subtotal: $${subtotal.toFixed(2)}</strong></div>
            </div>
        `;
    }).join('');

    totalEl.textContent = `$${total.toFixed(2)}`;
}

function filterProducts(term) {
    const t = (term || '').toLowerCase().trim();
    document.querySelectorAll('.product-item').forEach(el => {
        const name = el.getAttribute('data-name') || '';
        el.style.display = (!t || name.includes(t)) ? '' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const search = document.getElementById('productSearch');
    if (search) {
        search.addEventListener('input', (e) => filterProducts(e.target.value));
    }

    const form = document.getElementById('newOrderForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (modalItems.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pedido vacío',
                    text: 'Agregá al menos un producto.',
                    confirmButtonColor: '#1e8081'
                });
                return;
            }

            const tableId = document.getElementById('newOrderTableId').value;
            const payload = {
                observations: document.getElementById('newOrderObservations').value || null,
                send_to_kitchen: document.getElementById('sendToKitchen').checked,
                items: modalItems.map(i => ({
                    product_id: i.product_id,
                    quantity: i.quantity,
                    observations: i.observations || null,
                }))
            };

            try {
                const res = await fetch(`/tables/${tableId}/orders`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'No se pudo crear el pedido');
                }

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Pedido creado (${data.order_number})`,
                    showConfirmButton: false,
                    timer: 2200,
                    timerProgressBar: true,
                });

                newOrderModal.hide();
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: err.message || 'No se pudo crear el pedido',
                    confirmButtonColor: '#c94a2d',
                });
            }
        });
    }
});
</script>
@endsection

