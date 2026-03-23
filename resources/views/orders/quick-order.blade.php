@extends('layouts.app')

@section('title', 'Pedidos Rápidos')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
                <i class="bi bi-lightning-charge"></i> Pedidos Rápidos
            </h1>
            <p class="text-white">Consumo inmediato sin mesa</p>
        </div>
        <div>
            @if($activeSession)
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newQuickOrderModal">
                <i class="bi bi-plus-circle"></i> Nuevo Pedido Rápido
            </button>
            @else
            <button type="button" class="btn btn-primary" disabled title="Se requiere una sesión de caja activa">
                <i class="bi bi-plus-circle"></i> Nuevo Pedido Rápido
            </button>
            @endif
            <a href="{{ route('orders.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver a Pedidos
            </a>
        </div>
    </div>
</div>

<!-- Información de sesión de caja -->
@if($activeSession)
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> 
            <strong>Sesión activa:</strong> {{ $activeSession->cashRegister->name }} - 
            Abierta por {{ $activeSession->user->name }} a las {{ $activeSession->opened_at->format('H:i') }}
        </div>
    </div>
</div>
@else
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> 
            <strong>No hay sesión de caja activa.</strong> 
            <a href="{{ route('cash-register.index') }}">Abrir una sesión de caja</a> para realizar pedidos rápidos.
        </div>
    </div>
</div>
@endif

<!-- Lista de pedidos rápidos activos -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Pedidos Rápidos Activos</h5>
                <div>
                    <span class="badge bg-info" id="ordersCount">0</span>
                    <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="loadQuickOrders()" id="refreshBtn">
                        <i class="bi bi-arrow-clockwise"></i> Actualizar
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="ordersLoading" class="text-center py-3" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="text-muted mt-2">Actualizando pedidos...</p>
                </div>
                <div id="ordersContainer">
                    <!-- Los pedidos se cargarán dinámicamente aquí -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para nuevo pedido rápido -->
@if($activeSession)
<div class="modal fade" id="newQuickOrderModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable">
        <div class="modal-content">
            <form id="newQuickOrderForm">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-plus-circle"></i> Nuevo Pedido Rápido
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                                        <h5 class="mb-0"><i class="bi bi-card-list"></i> Productos</h5>
                                        <input type="text" class="form-control form-control-lg" id="quickOrderProductSearch" 
                                               placeholder="🔍 Buscar producto..." style="max-width: 100%; min-width: 250px;">
                                    </div>
                                </div>
                                <div class="card-body quick-order-products-scroll">
                                    <div id="quickOrderProductsAccordion">
                                @foreach($products as $categoryName => $categoryProducts)
                                    <div class="category-section-modal mb-4" data-category-name="{{ strtolower($categoryName) }}">
                                        <div class="d-flex align-items-center mb-3" style="background: linear-gradient(135deg, #1e8081, #138496); padding: 0.75rem 1rem; border-radius: 8px;">
                                            <h6 class="mb-0 text-white" style="font-weight: 700;">
                                                <i class="bi bi-tag-fill"></i> {{ $categoryName }}
                                            </h6>
                                            <span class="badge bg-light text-dark ms-auto">{{ $categoryProducts->count() }} productos</span>
                                        </div>
                                        <div class="row g-2">
                                            @foreach($categoryProducts as $product)
                                                @php
                                                    $currentStock = $product->has_stock ? $product->getCurrentStock(auth()->user()->restaurant_id) : null;
                                                    $isOutOfStock = $currentStock !== null && $currentStock <= 0;
                                                    $isLowStock = $currentStock !== null && $currentStock > 0 && $currentStock <= $product->stock_minimum;
                                                @endphp
                                                <div class="col-12 col-md-6 mb-2 product-item" 
                                                     data-name="{{ strtolower($product->name) }}" 
                                                     data-category-name="{{ strtolower($categoryName) }}"
                                                     data-product-id="{{ $product->id }}">
                                                    <div class="d-flex justify-content-between align-items-start border rounded p-2 {{ $isOutOfStock ? 'border-danger bg-light' : ($isLowStock ? 'border-warning bg-light' : '') }}">
                                                        <div class="me-2 flex-grow-1">
                                                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                                                <strong class="fs-6">{{ $product->name }}</strong>
                                                                @if($isOutOfStock)
                                                                    <span class="badge bg-danger" title="Sin stock disponible">
                                                                        <i class="bi bi-x-circle-fill"></i> Sin Stock
                                                                    </span>
                                                                @elseif($isLowStock)
                                                                    <span class="badge bg-warning" title="Stock bajo">
                                                                        <i class="bi bi-exclamation-triangle-fill"></i> Stock: {{ $currentStock }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            <div class="text-muted small mb-1">{{ $categoryName }}</div>
                                                            <div class="fw-bold text-primary">${{ number_format($product->price, 2) }}</div>
                                                        </div>
                                                        <div class="d-flex flex-column align-items-end gap-1">
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-primary add-product-btn" 
                                                                    data-product-id="{{ $product->id }}"
                                                                    data-product-name="{{ $product->name }}"
                                                                    data-product-price="{{ $product->price }}"
                                                                    {{ $isOutOfStock ? 'disabled' : '' }}>
                                                                <i class="bi bi-plus-circle"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card border-0 shadow-sm sticky-top quick-order-summary" style="top: 20px;">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="bi bi-receipt"></i> Resumen del Pedido</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Observaciones (opcional)</label>
                                        <textarea class="form-control" id="quickOrderObservations" rows="3" placeholder="Ej: sin sal, alergias, etc."></textarea>
                                    </div>

                                    <div class="form-check form-switch mb-3 p-3 bg-light rounded">
                                        <input class="form-check-input" type="checkbox" role="switch" id="quickOrderSendToKitchen" checked style="min-width: 48px; min-height: 24px;">
                                        <label class="form-check-label ms-2 fw-bold" for="quickOrderSendToKitchen">Enviar a cocina al confirmar</label>
                                    </div>

                                    <div id="quickOrderItemsEmpty" class="text-muted text-center py-4">
                                        <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                        <p class="mt-2">No hay items en el pedido</p>
                                    </div>
                                    <div id="quickOrderItemsList" class="mb-3 quick-order-items-scroll"></div>

                                    <div class="border-top pt-3 pb-2">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <strong class="fs-5">Total:</strong> 
                                                <span id="quickOrderTotal" class="fs-3 fw-bold text-primary">$0.00</span>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-success btn-lg w-100" id="quickOrderConfirmBtn" disabled style="min-height: 60px; font-size: 1.25rem; font-weight: 700;">
                                            <i class="bi bi-check-circle"></i> Confirmar Pedido
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('styles')
<style>
.quick-order-products-scroll {
    max-height: 60vh;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
}

.quick-order-items-scroll {
    max-height: 300px;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
}

#newQuickOrderModal .modal-body {
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
    touch-action: pan-y;
}

@media (max-width: 768px) {
    /* En mobile evitamos scroll anidado dentro del modal: que scrollee solo la modal-body */
    #newQuickOrderModal .quick-order-products-scroll,
    #newQuickOrderModal .quick-order-items-scroll {
        max-height: none !important;
        overflow: visible !important;
    }

    /* Sticky dentro de modal en mobile suele “trabar” el scroll */
    #newQuickOrderModal .quick-order-summary {
        position: static !important;
        top: auto !important;
    }
}

.category-section-modal {
    margin-bottom: 1.5rem;
}

.product-item {
    transition: all 0.2s ease;
}

.product-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.add-product-btn {
    min-width: 40px;
    min-height: 40px;
}
</style>
@endpush

@push('scripts')
<script>
let quickOrderItems = [];
let quickOrderItemCounter = 0;
const currentUserIsAdmin = @json(in_array(auth()->user()->role, ['ADMIN', 'GERENTE']));

// Búsqueda de productos
document.getElementById('quickOrderProductSearch')?.addEventListener('input', function() {
    filterQuickOrderProducts(this.value.toLowerCase().trim());
});

function filterQuickOrderProducts(term) {
    document.querySelectorAll('#quickOrderProductsAccordion .category-section-modal').forEach(section => {
        let hasVisibleProducts = false;
        
        section.querySelectorAll('.product-item').forEach(item => {
            const name = item.dataset.name || '';
            const categoryName = item.dataset.categoryName || '';
            
            if (!term || name.includes(term) || categoryName.includes(term)) {
                item.style.display = 'block';
                hasVisibleProducts = true;
            } else {
                item.style.display = 'none';
            }
        });
        
        section.style.display = hasVisibleProducts ? 'block' : 'none';
    });
}

// Agregar producto al pedido
document.querySelectorAll('.add-product-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = parseInt(this.dataset.productId);
        const productName = this.dataset.productName;
        const productPrice = parseFloat(this.dataset.productPrice);
        
        const existingItem = quickOrderItems.find(item => item.product_id === productId);
        
        if (existingItem) {
            existingItem.quantity++;
        } else {
            quickOrderItems.push({
                product_id: productId,
                name: productName,
                price: productPrice,
                quantity: 1,
                observations: ''
            });
        }
        
        renderQuickOrderItems();
    });
});

// Renderizar items del pedido
function renderQuickOrderItems() {
    const container = document.getElementById('quickOrderItemsList');
    const emptyMsg = document.getElementById('quickOrderItemsEmpty');
    const totalEl = document.getElementById('quickOrderTotal');
    const confirmBtn = document.getElementById('quickOrderConfirmBtn');
    
    if (quickOrderItems.length === 0) {
        container.innerHTML = '';
        emptyMsg.style.display = 'block';
        totalEl.textContent = '$0.00';
        confirmBtn.disabled = true;
        return;
    }
    
    emptyMsg.style.display = 'none';
    
    let total = 0;
    let html = '';
    
    quickOrderItems.forEach((item, index) => {
        const price = Number(item.price) || 0;
        const qty = parseInt(item.quantity, 10) || 1;
        const subtotal = price * qty;
        total += subtotal;
        
        html += `
            <div class="card mb-2">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="flex-grow-1">
                            <strong>${item.name}</strong>
                            <div class="text-muted small">$${price.toFixed(2)} c/u</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="updateQuickOrderQuantity(${index}, -1)">
                                <i class="bi bi-dash"></i>
                            </button>
                            <span class="fw-bold">${qty}</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="updateQuickOrderQuantity(${index}, 1)">
                                <i class="bi bi-plus"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeQuickOrderItem(${index})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="text-end">
                        <strong>Subtotal: $${subtotal.toFixed(2)}</strong>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    totalEl.textContent = `$${(Number(total)).toFixed(2)}`;
    
    validateQuickOrderForm();
}

// Actualizar cantidad
function updateQuickOrderQuantity(index, change) {
    if (quickOrderItems[index]) {
        quickOrderItems[index].quantity += change;
        if (quickOrderItems[index].quantity <= 0) {
            quickOrderItems.splice(index, 1);
        }
        renderQuickOrderItems();
    }
}

// Eliminar item
function removeQuickOrderItem(index) {
    quickOrderItems.splice(index, 1);
    renderQuickOrderItems();
}

// Función para validar el formulario (solo items; sin selector de cliente)
function validateQuickOrderForm() {
    const confirmBtn = document.getElementById('quickOrderConfirmBtn');
    confirmBtn.disabled = quickOrderItems.length === 0;
}

// Enviar formulario
document.getElementById('newQuickOrderForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (quickOrderItems.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Pedido vacío',
            text: 'Debes agregar al menos un producto',
            confirmButtonColor: '#ffc107'
        });
        return;
    }
    
    const items = quickOrderItems.map(item => ({
        product_id: item.product_id,
        name: item.name,
        quantity: item.quantity,
        observations: item.observations || ''
    }));
    
    const formData = {
        customer_name: '',
        observations: document.getElementById('quickOrderObservations').value,
        send_to_kitchen: document.getElementById('quickOrderSendToKitchen').checked,
        items: items
    };

    var printWin = window.open('', 'kitchen_print', 'noopener,noreferrer,width=450,height=700');

    // Mostrar loading
    Swal.fire({
        title: 'Creando pedido...',
        text: 'Por favor espera',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    try {
        const response = await fetch('{{ route("orders.quick.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(formData)
        });
        
        // Verificar si la respuesta es JSON
        const contentType = response.headers.get('content-type');
        let data;
        
        if (contentType && contentType.includes('application/json')) {
            data = await response.json();
        } else {
            // Si no es JSON, intentar parsear el texto
            const text = await response.text();
            try {
                data = JSON.parse(text);
            } catch (e) {
                // Si no se puede parsear, es probable que sea HTML de error
                throw new Error('El servidor devolvió una respuesta no válida. Por favor, verifica los datos e intenta nuevamente.');
            }
        }
        
        if (data.success) {
            // Órdenes rápidas: imprimir desde /orders/{id}/print/item/{itemId}/ticket (un ticket por ítem)
            const urls = data.item_ticket_urls && data.item_ticket_urls.length ? data.item_ticket_urls : (data.kitchen_ticket_url ? [data.kitchen_ticket_url] : []);
            if (urls.length) {
                if (printWin && !printWin.closed) {
                    printWin.location.href = urls[0];
                } else {
                    window.open(urls[0], 'item_ticket_print', 'noopener,noreferrer,width=450,height=700');
                }
                for (let i = 1; i < urls.length; i++) {
                    setTimeout(function() {
                        window.open(urls[i], 'item_ticket_print_' + i, 'noopener,noreferrer,width=450,height=700');
                    }, 400 * i);
                }
                setTimeout(function() { try { if (printWin && !printWin.closed) printWin.close(); } catch (e) {} }, 3500);
            }

            Swal.fire({
                icon: 'success',
                title: '¡Pedido creado!',
                text: data.message,
                confirmButtonColor: '#1e8081',
                timer: 2000,
                showConfirmButton: false
            });
            
            // Cerrar modal y limpiar
            const modal = bootstrap.Modal.getInstance(document.getElementById('newQuickOrderModal'));
            if (modal) {
                modal.hide();
            }
            
            // Limpiar formulario
            quickOrderItems = [];
            document.getElementById('quickOrderObservations').value = '';
            document.getElementById('quickOrderProductSearch').value = '';
            renderQuickOrderItems();
            filterQuickOrderProducts('');
            
            // Actualizar lista de pedidos después de un breve delay
            setTimeout(() => {
                loadQuickOrders(false);
            }, 500);
        } else {
            try { if (printWin && !printWin.closed) printWin.close(); } catch (e) {}
            // Manejar errores de validación
            let errorMessage = data.message || 'Error al crear el pedido';
            if (data.errors) {
                const errorMessages = Object.values(data.errors).flat();
                errorMessage = errorMessages.join('<br>');
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: errorMessage,
                confirmButtonColor: '#dc3545'
            });
        }
    } catch (error) {
        try { if (printWin && !printWin.closed) printWin.close(); } catch (e) {}
        console.error('Error al crear pedido:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error de conexión. Por favor intenta nuevamente.',
            confirmButtonColor: '#dc3545'
        });
    }
});

// Limpiar modal al cerrar
document.getElementById('newQuickOrderModal')?.addEventListener('hidden.bs.modal', function() {
    quickOrderItems = [];
    quickOrderItemCounter = 0;
    document.getElementById('quickOrderObservations').value = '';
    document.getElementById('quickOrderProductSearch').value = '';
    renderQuickOrderItems();
    filterQuickOrderProducts('');
});

// ========== SISTEMA DE ACTUALIZACIÓN DINÁMICA DE PEDIDOS ==========

let ordersUpdateInterval = null;
let isUpdating = false;

// Función para cargar pedidos rápidos
async function loadQuickOrders(showLoading = false) {
    if (isUpdating) return;
    isUpdating = true;
    
    const container = document.getElementById('ordersContainer');
    const loading = document.getElementById('ordersLoading');
    const refreshBtn = document.getElementById('refreshBtn');
    
    if (showLoading) {
        loading.style.display = 'block';
        container.style.opacity = '0.5';
    }
    
    if (refreshBtn) {
        refreshBtn.disabled = true;
        refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise spin"></i>';
    }
    
    try {
        const response = await fetch('{{ route("orders.quick.api") }}', {
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error('Error al cargar pedidos');
        }
        
        const data = await response.json();
        
        if (data.success) {
            renderOrders(data.orders);
            document.getElementById('ordersCount').textContent = data.orders.length;
        } else {
            container.innerHTML = `
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle"></i> ${data.message || 'Error al cargar pedidos'}
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading orders:', error);
        container.innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-x-circle"></i> Error al cargar pedidos. Por favor recarga la página.
            </div>
        `;
    } finally {
        loading.style.display = 'none';
        container.style.opacity = '1';
        if (refreshBtn) {
            refreshBtn.disabled = false;
            refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Actualizar';
        }
        isUpdating = false;
    }
}

// Función para renderizar pedidos con animación suave
function renderOrders(orders) {
    const container = document.getElementById('ordersContainer');
    
    if (!orders || orders.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; color: var(--conurbania-medium);"></i>
                <p class="text-muted mt-3">No hay pedidos rápidos activos</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newQuickOrderModal">
                    <i class="bi bi-plus-circle"></i> Crear Primer Pedido Rápido
                </button>
            </div>
        `;
        return;
    }
    
    // Guardar el estado actual de las filas visibles
    const currentRows = container.querySelectorAll('tr[data-order-id]');
    const currentOrderIds = Array.from(currentRows).map(row => parseInt(row.dataset.orderId));
    const newOrderIds = orders.map(o => o.id);
    
    // Identificar nuevos pedidos
    const newOrders = orders.filter(o => !currentOrderIds.includes(o.id));
    
    let html = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Cliente</th>
                        <th>Usuario</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    orders.forEach(order => {
        const statusBadge = getStatusBadge(order.status);
        const canClose = order.status !== 'CERRADO' && order.status !== 'CANCELADO';
        const isNew = newOrders.some(o => o.id === order.id);
        
        html += `
            <tr data-order-id="${order.id}" class="${isNew ? 'table-success' : ''}" style="transition: all 0.3s ease;">
                <td><strong>${order.number}</strong></td>
                <td>${order.customer_name || 'Sin nombre'}</td>
                <td>${order.user_name}</td>
                <td>${order.items_count}</td>
                <td><strong>$${parseFloat(order.total).toFixed(2)}</strong></td>
                <td>${statusBadge}</td>
                <td>${order.created_at}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <a href="/orders/quick/${order.id}" class="btn btn-primary">
                            <i class="bi bi-eye"></i> Ver
                        </a>
                        ${canClose ? `
                        <a href="/orders/quick/${order.id}/close" class="btn btn-success">
                            <i class="bi bi-cash-coin"></i> Cerrar Cuenta
                        </a>
                        ` : ''}
                        ${(currentUserIsAdmin || order.status === 'ABIERTO' || order.status === 'CANCELADO') ? `
                        <form action="/orders/${order.id}" method="POST" class="d-inline" id="deleteQuickOrderForm${order.id}">
                            <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''}">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDeleteQuickOrder(${order.id}, '${String(order.number).replace(/'/g, "\\'")}')" title="Eliminar pedido">
                                <i class="bi bi-trash"></i> Eliminar
                            </button>
                        </form>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    // Actualizar con animación
    container.style.opacity = '0.7';
    setTimeout(() => {
        container.innerHTML = html;
        container.style.opacity = '1';
        
        // Remover clase de nuevo después de 2 segundos
        setTimeout(() => {
            container.querySelectorAll('.table-success').forEach(row => {
                row.classList.remove('table-success');
            });
        }, 2000);
    }, 200);
}

// Confirmar y enviar eliminación de pedido rápido
function confirmDeleteQuickOrder(orderId, orderNumber) {
    Swal.fire({
        icon: 'warning',
        title: '¿Eliminar pedido?',
        html: `¿Eliminar el pedido <strong>#${orderNumber}</strong>? Esta acción no se puede deshacer.`,
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-trash"></i> Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.getElementById('deleteQuickOrderForm' + orderId);
            if (form) form.submit();
        }
    });
}

// Función para obtener el badge de estado
function getStatusBadge(status) {
    // Vista simplificada: colapsamos todos los estados intermedios a "Se toma el pedido"
    if (status === 'CERRADO') {
        return `<span class="badge bg-success">Se cierra el pedido</span>`;
    }
    if (status === 'ENTREGADO') {
        return `<span class="badge bg-success">Se entrega el producto</span>`;
    }
    if (status === 'CANCELADO') {
        return `<span class="badge bg-danger">Cancelado</span>`;
    }
    return `<span class="badge bg-secondary">Se toma el pedido</span>`;
}

// Inicializar carga de pedidos
document.addEventListener('DOMContentLoaded', function() {
    // Cargar pedidos al inicio
    loadQuickOrders(true);
    
    // Configurar actualización automática cada 5 segundos
    ordersUpdateInterval = setInterval(() => {
        loadQuickOrders(false);
    }, 5000);
    
    // Limpiar intervalo al salir de la página
    window.addEventListener('beforeunload', function() {
        if (ordersUpdateInterval) {
            clearInterval(ordersUpdateInterval);
        }
    });
});

// Actualizar después de crear un pedido
window.addEventListener('quickOrderCreated', function() {
    setTimeout(() => {
        loadQuickOrders(false);
    }, 1000);
});

// Agregar estilo para spinner
const style = document.createElement('style');
style.textContent = `
    .spin {
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
</script>
@endpush
@endsection
