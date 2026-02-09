@extends('layouts.app')

@section('title', 'Pedido Rápido - Consumo Inmediato')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
                <i class="bi bi-lightning-charge"></i> Pedido Rápido
            </h1>
            <p class="text-muted">Consumo inmediato sin mesa</p>
        </div>
        <a href="{{ route('orders.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver a Pedidos
        </a>
    </div>
</div>

<div class="row">
    <!-- Panel de Productos -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-box-seam"></i> Productos</h5>
            </div>
            <div class="card-body">
                <!-- Filtro por categoría -->
                <div class="mb-3">
                    <select id="categoryFilter" class="form-select">
                        <option value="">Todas las categorías</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Grid de productos -->
                <div class="row g-3" id="productsGrid">
                    @foreach($products as $product)
                    <div class="col-md-4 col-sm-6 product-item" data-category-id="{{ $product->category_id }}">
                        <div class="card h-100 product-card" onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->price }})">
                            <div class="card-body text-center">
                                <h6 class="card-title">{{ $product->name }}</h6>
                                <p class="text-muted small mb-2">{{ $product->category->name ?? '-' }}</p>
                                <p class="mb-0"><strong class="text-primary">${{ number_format($product->price, 2) }}</strong></p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Panel de Carrito y Pago -->
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-cart"></i> Carrito</h5>
            </div>
            <div class="card-body">
                <div id="cartItems" class="mb-3">
                    <p class="text-muted text-center">El carrito está vacío</p>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-3">
                    <strong>Total:</strong>
                    <strong id="cartTotal">$0.00</strong>
                </div>
            </div>
        </div>

        <!-- Formulario de Pago -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-cash-coin"></i> Pago</h5>
            </div>
            <div class="card-body">
                <form id="quickOrderForm" action="{{ route('orders.process-quick-order') }}" method="POST">
                    @csrf
                    <input type="hidden" name="cash_register_session_id" value="{{ $activeSession->id }}">
                    
                    <!-- Campo de nombre del consumidor -->
                    <div class="mb-3">
                        <label for="customer_name" class="form-label">Nombre del Consumidor *</label>
                        <input type="text" class="form-control @error('customer_name') is-invalid @enderror" 
                               id="customer_name" name="customer_name" 
                               value="{{ old('customer_name') }}" 
                               placeholder="Ej: Juan Pérez" required>
                        @error('customer_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Nombre de quien consume (reemplaza la mesa)</small>
                    </div>
                    
                    <div id="paymentMethodsContainer">
                        <!-- Los métodos de pago se agregarán dinámicamente -->
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-outline-primary w-100" onclick="addPaymentMethod()">
                            <i class="bi bi-plus-circle"></i> Agregar Método de Pago
                        </button>
                    </div>

                    <div id="paymentSummary" class="alert alert-info" style="display: none;">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total a Pagar:</span>
                            <strong id="totalToPay">$0.00</strong>
                        </div>
                        <div class="d-flex justify-content-between" id="changeContainer" style="display: none;">
                            <span>Cambio:</span>
                            <strong id="changeAmount" class="text-success">$0.00</strong>
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="print_ticket" name="print_ticket" value="1" checked>
                        <label class="form-check-label" for="print_ticket">
                            Imprimir ticket
                        </label>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-100" id="processBtn" disabled>
                        <i class="bi bi-check-circle"></i> Procesar Pedido
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.product-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.product-card:hover {
    border-color: var(--conurbania-primary);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(30, 128, 129, 0.2);
}

.cart-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}

.cart-item:last-child {
    border-bottom: none;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quantity-controls button {
    width: 30px;
    height: 30px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
@endpush

@push('scripts')
<script>
let cart = [];
let paymentMethods = [];
let paymentCounter = 0;

const paymentMethodOptions = {
    'EFECTIVO': { icon: 'bi-cash', label: 'Efectivo', color: '#28a745' },
    'DEBITO': { icon: 'bi-credit-card', label: 'Tarjeta Débito', color: '#007bff' },
    'CREDITO': { icon: 'bi-credit-card-2-front', label: 'Tarjeta Crédito', color: '#6f42c1' },
    'TRANSFERENCIA': { icon: 'bi-bank', label: 'Transferencia', color: '#17a2b8' },
    'QR': { icon: 'bi-qr-code', label: 'QR', color: '#fd7e14' },
    'MIXTO': { icon: 'bi-wallet2', label: 'Mixto', color: '#6c757d' },
};

// Filtro por categoría
document.getElementById('categoryFilter').addEventListener('change', function() {
    const categoryId = this.value;
    document.querySelectorAll('.product-item').forEach(item => {
        if (!categoryId || item.dataset.categoryId === categoryId) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
});

// Agregar producto al carrito
function addToCart(productId, productName, price) {
    const existingItem = cart.find(item => item.product_id === productId);
    
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({
            product_id: productId,
            name: productName,
            price: price,
            quantity: 1,
            observations: ''
        });
    }
    
    updateCart();
}

// Actualizar cantidad
function updateQuantity(productId, change) {
    const item = cart.find(item => item.product_id === productId);
    if (item) {
        item.quantity += change;
        if (item.quantity <= 0) {
            cart = cart.filter(item => item.product_id !== productId);
        }
        updateCart();
    }
}

// Actualizar carrito
function updateCart() {
    const cartItems = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');
    
    if (cart.length === 0) {
        cartItems.innerHTML = '<p class="text-muted text-center">El carrito está vacío</p>';
        cartTotal.textContent = '$0.00';
        document.getElementById('processBtn').disabled = true;
        return;
    }
    
    let total = 0;
    let html = '';
    
    cart.forEach(item => {
        const subtotal = item.price * item.quantity;
        total += subtotal;
        
        html += `
            <div class="cart-item">
                <div>
                    <strong>${item.name}</strong>
                    <div class="text-muted small">$${item.price.toFixed(2)} c/u</div>
                </div>
                <div class="quantity-controls">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="updateQuantity(${item.product_id}, -1)">
                        <i class="bi bi-dash"></i>
                    </button>
                    <span class="mx-2">${item.quantity}</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="updateQuantity(${item.product_id}, 1)">
                        <i class="bi bi-plus"></i>
                    </button>
                </div>
                <div class="text-end">
                    <strong>$${subtotal.toFixed(2)}</strong>
                </div>
            </div>
        `;
    });
    
    cartItems.innerHTML = html;
    cartTotal.textContent = `$${total.toFixed(2)}`;
    
    updatePaymentSummary();
    document.getElementById('processBtn').disabled = false;
}

// Agregar método de pago
function addPaymentMethod() {
    paymentCounter++;
    const paymentId = `payment_${paymentCounter}`;
    
    paymentMethods.push({
        id: paymentId,
        method: 'EFECTIVO',
        amount: 0,
        operation_number: '',
        notes: ''
    });
    
    renderPaymentMethods();
    updatePaymentSummary();
}

// Remover método de pago
function removePaymentMethod(paymentId) {
    paymentMethods = paymentMethods.filter(p => p.id !== paymentId);
    renderPaymentMethods();
    updatePaymentSummary();
}

// Actualizar método de pago
function updatePaymentMethod(paymentId, field, value) {
    const payment = paymentMethods.find(p => p.id === paymentId);
    if (payment) {
        if (field === 'amount') {
            payment.amount = parseFloat(value) || 0;
        } else {
            payment[field] = value;
        }
        updatePaymentSummary();
    }
}

// Renderizar métodos de pago
function renderPaymentMethods() {
    const container = document.getElementById('paymentMethodsContainer');
    
    if (paymentMethods.length === 0) {
        container.innerHTML = '<p class="text-muted text-center small">Agrega un método de pago</p>';
        return;
    }
    
    container.innerHTML = paymentMethods.map(payment => {
        const methodInfo = paymentMethodOptions[payment.method] || paymentMethodOptions['EFECTIVO'];
        return `
            <div class="card mb-2">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0"><i class="bi ${methodInfo.icon}"></i> ${methodInfo.label}</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePaymentMethod('${payment.id}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <select class="form-select form-select-sm mb-2" onchange="updatePaymentMethod('${payment.id}', 'method', this.value)">
                        ${Object.entries(paymentMethodOptions).map(([key, info]) => 
                            `<option value="${key}" ${payment.method === key ? 'selected' : ''}>${info.label}</option>`
                        ).join('')}
                    </select>
                    <input type="number" step="0.01" class="form-control form-control-sm mb-2" 
                           placeholder="Monto" value="${payment.amount.toFixed(2)}"
                           onchange="updatePaymentMethod('${payment.id}', 'amount', this.value)">
                    <input type="text" class="form-control form-control-sm" 
                           placeholder="N° Operación (opcional)" value="${payment.operation_number}"
                           onchange="updatePaymentMethod('${payment.id}', 'operation_number', this.value)">
                </div>
            </div>
        `;
    }).join('');
}

// Actualizar resumen de pago
function updatePaymentSummary() {
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const totalPaid = paymentMethods.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0);
    const change = totalPaid - total;
    
    document.getElementById('totalToPay').textContent = `$${total.toFixed(2)}`;
    
    const summary = document.getElementById('paymentSummary');
    const changeContainer = document.getElementById('changeContainer');
    
    if (total > 0) {
        summary.style.display = 'block';
        
        if (change > 0.01) {
            changeContainer.style.display = 'flex';
            document.getElementById('changeAmount').textContent = `$${change.toFixed(2)}`;
        } else {
            changeContainer.style.display = 'none';
        }
    } else {
        summary.style.display = 'none';
    }
}

// Procesar pedido
document.getElementById('quickOrderForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const customerName = document.getElementById('customer_name').value.trim();
    
    if (!customerName) {
        Swal.fire({
            icon: 'warning',
            title: 'Nombre requerido',
            text: 'Debes ingresar el nombre del consumidor',
            confirmButtonColor: '#ffc107'
        });
        return;
    }
    
    if (cart.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Carrito vacío',
            text: 'Debes agregar al menos un producto al carrito',
            confirmButtonColor: '#ffc107'
        });
        return;
    }
    
    if (paymentMethods.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin método de pago',
            text: 'Debes agregar al menos un método de pago',
            confirmButtonColor: '#ffc107'
        });
        return;
    }
    
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const totalPaid = paymentMethods.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0);
    const change = totalPaid - total;
    
    if (totalPaid < total - 0.01) {
        Swal.fire({
            icon: 'warning',
            title: 'Pago insuficiente',
            text: `Faltan $${(total - totalPaid).toFixed(2)}. El total pagado debe ser igual o mayor al total a pagar.`,
            confirmButtonColor: '#ffc107'
        });
        return;
    }
    
    // Preparar datos
    const items = cart.map(item => ({
        product_id: item.product_id,
        quantity: item.quantity,
        observations: item.observations || ''
    }));
    
    const payments = paymentMethods.map(payment => {
        const amount = parseFloat(payment.amount) || 0;
        if (isNaN(amount) || amount <= 0) {
            throw new Error(`El monto del método de pago "${payment.method}" no es válido`);
        }
        return {
            payment_method: payment.method,
            amount: amount,
            operation_number: payment.operation_number || null,
            notes: payment.notes || null
        };
    });
    
    // Confirmar
    Swal.fire({
        title: '¿Confirmar pedido rápido?',
        html: `
            <p>Total: <strong>$${total.toFixed(2)}</strong></p>
            <p>Total pagado: <strong>$${totalPaid.toFixed(2)}</strong></p>
            ${change > 0.01 ? `<p>Cambio: <strong>$${change.toFixed(2)}</strong></p>` : ''}
            <p>Se procesará el pago y se cerrará el pedido inmediatamente.</p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1e8081',
        cancelButtonColor: '#7b7d84',
        confirmButtonText: 'Sí, procesar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Procesando...',
                text: 'Por favor espera',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Preparar FormData
            const form = document.getElementById('quickOrderForm');
            const formData = new FormData(form);
            
            // Asegurar que customer_name esté incluido
            formData.set('customer_name', customerName);
            
            // Agregar items
            items.forEach((item, index) => {
                formData.append(`items[${index}][product_id]`, item.product_id);
                formData.append(`items[${index}][quantity]`, item.quantity);
                if (item.observations) {
                    formData.append(`items[${index}][observations]`, item.observations);
                }
            });
            
            // Agregar payments
            payments.forEach((payment, index) => {
                formData.append(`payments[${index}][payment_method]`, payment.payment_method);
                formData.append(`payments[${index}][amount]`, payment.amount);
                if (payment.operation_number) {
                    formData.append(`payments[${index}][operation_number]`, payment.operation_number);
                }
                if (payment.notes) {
                    formData.append(`payments[${index}][notes]`, payment.notes);
                }
            });
            
            // Enviar formulario
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async response => {
                const data = await response.json();
                
                if (data.success) {
                    // Abrir PDF automáticamente
                    if (data.print_url) {
                        window.open(data.print_url, '_blank');
                    }
                    
                    Swal.fire({
                        icon: 'success',
                        title: '¡Pedido procesado!',
                        text: data.message,
                        confirmButtonColor: '#1e8081'
                    }).then(() => {
                        // Limpiar carrito y recargar
                        cart = [];
                        paymentMethods = [];
                        updateCart();
                        renderPaymentMethods();
                        document.getElementById('customer_name').value = '';
                        
                        // Opcional: redirigir a lista de pedidos
                        window.location.href = '{{ route("orders.index") }}';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Ocurrió un error al procesar el pedido',
                        confirmButtonColor: '#c94a2d'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo conectar con el servidor',
                    confirmButtonColor: '#c94a2d'
                });
            });
        }
    });
});

// Inicializar con un método de pago
document.addEventListener('DOMContentLoaded', function() {
    addPaymentMethod();
});
</script>
@endpush
@endsection

