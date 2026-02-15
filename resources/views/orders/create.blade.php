@extends('layouts.app')

@section('title', 'Nuevo Pedido')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-receipt"></i> Nuevo Pedido</h1>
        <p class="text-muted">Crear un nuevo pedido</p>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">Seleccionar Productos</h5>
                    <div class="input-group" style="max-width: 400px;">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" 
                               class="form-control" 
                               id="productSearch" 
                               placeholder="🔍 Buscar producto..." 
                               autocomplete="off">
                    </div>
                </div>
            </div>
            <div class="card-body">
                @foreach($products as $categoryName => $categoryProducts)
                <div class="mb-4 category-section" data-category-name="{{ strtolower($categoryName) }}">
                    <h5 class="border-bottom pb-2">{{ $categoryName }}</h5>
                    <div class="row">
                        @foreach($categoryProducts as $product)
                        <div class="col-md-4 mb-3 product-item" 
                             data-product-name="{{ strtolower($product->name) }}"
                             data-category-name="{{ strtolower($categoryName) }}">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">{{ $product->name }}</h6>
                                    <p class="card-text text-muted small">{{ $product->description }}</p>
                                    <p class="card-text"><strong>${{ number_format($product->price, 2) }}</strong></p>
                                    <button type="button" class="btn btn-sm btn-primary" onclick="addProduct({{ $product->id }}, '{{ $product->name }}', {{ $product->price }})">
                                        <i class="bi bi-plus"></i> Agregar
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

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Pedido</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('orders.store') }}" method="POST" id="orderForm">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="table_id" class="form-label">Mesa</label>
                        <select class="form-select" id="table_id" name="table_id" required>
                            <option value="">Seleccionar mesa</option>
                            @foreach($tables as $table)
                            <option value="{{ $table->id }}" {{ $selectedTable && $selectedTable->id === $table->id ? 'selected' : '' }}>
                                {{ $table->number }} - {{ $table->status }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="observations" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observations" name="observations" rows="3"></textarea>
                    </div>

                    <div id="orderItems" class="mb-3">
                        <h6>Items del Pedido</h6>
                        <div id="itemsList"></div>
                        <div id="order-items-data"></div>
                        <p class="text-muted" id="emptyMessage">No hay items en el pedido</p>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                            <i class="bi bi-check-circle"></i> Crear Pedido
                        </button>
                    </div>
                </form>
                
                <script id="order-items-data" type="application/json"></script>
            </div>
        </div>
    </div>
</div>

<script>
// Búsqueda de productos
document.getElementById('productSearch')?.addEventListener('input', function() {
    filterProducts(this.value.toLowerCase().trim());
});

function filterProducts(searchTerm) {
    const categorySections = document.querySelectorAll('.category-section');
    
    categorySections.forEach(section => {
        let hasVisibleProducts = false;
        const productItems = section.querySelectorAll('.product-item');
        
        productItems.forEach(item => {
            const productName = item.dataset.productName || '';
            const categoryName = item.dataset.categoryName || '';
            
            if (!searchTerm || 
                productName.includes(searchTerm) || 
                categoryName.includes(searchTerm)) {
                item.style.display = 'block';
                hasVisibleProducts = true;
            } else {
                item.style.display = 'none';
            }
        });
        
        // Mostrar/ocultar la sección de categoría según si tiene productos visibles
        section.style.display = hasVisibleProducts ? 'block' : 'none';
    });
}

let orderItems = [];
let itemCounter = 0;

function addProduct(productId, productName, productPrice) {
    itemCounter++;
    const item = {
        id: itemCounter,
        product_id: productId,
        name: productName,
        price: productPrice,
        quantity: 1
    };
    orderItems.push(item);
    updateItemsList();
}

function removeItem(itemId) {
    orderItems = orderItems.filter(item => item.id !== itemId);
    updateItemsList();
}

function updateQuantity(itemId, quantity) {
    const item = orderItems.find(i => i.id === itemId);
    if (item) {
        item.quantity = parseInt(quantity) || 1;
        updateItemsList();
    }
}

function updateItemsList() {
    const itemsList = document.getElementById('itemsList');
    const emptyMessage = document.getElementById('emptyMessage');
    const submitBtn = document.getElementById('submitBtn');
    
    if (orderItems.length === 0) {
        itemsList.innerHTML = '';
        emptyMessage.style.display = 'block';
        submitBtn.disabled = true;
        return;
    }

    emptyMessage.style.display = 'none';
    submitBtn.disabled = false;

    let html = '';
    let total = 0;

    orderItems.forEach(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        
        html += `
            <div class="border rounded p-2 mb-2" data-item-id="${item.id}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <strong>${item.name}</strong><br>
                        <small class="text-muted">$${item.price.toFixed(2)} c/u</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(${item.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <div class="mt-2">
                    <label class="small">Cantidad:</label>
                    <input type="number" class="form-control form-control-sm" 
                           value="${item.quantity}" min="1" 
                           onchange="updateQuantity(${item.id}, this.value)">
                </div>
                <div class="mt-2 text-end">
                    <strong>Subtotal: $${itemTotal.toFixed(2)}</strong>
                </div>
            </div>
        `;
    });

    html += `
        <div class="border-top pt-2 mt-2">
            <div class="d-flex justify-content-between">
                <strong>Total:</strong>
                <strong>$${total.toFixed(2)}</strong>
            </div>
        </div>
    `;

    itemsList.innerHTML = html;
    
    // Actualizar inputs ocultos para el formulario
    const itemsContainer = document.getElementById('order-items-data');
    itemsContainer.innerHTML = '';
    orderItems.forEach((item, index) => {
        const productInput = document.createElement('input');
        productInput.type = 'hidden';
        productInput.name = `items[${index}][product_id]`;
        productInput.value = item.product_id;
        itemsContainer.appendChild(productInput);
        
        const quantityInput = document.createElement('input');
        quantityInput.type = 'hidden';
        quantityInput.name = `items[${index}][quantity]`;
        quantityInput.value = item.quantity;
        itemsContainer.appendChild(quantityInput);
    });
}

// Interceptar envío del formulario
document.getElementById('orderForm').addEventListener('submit', function(e) {
    if (orderItems.length === 0) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Pedido vacío',
            text: 'Debes agregar al menos un item al pedido',
            confirmButtonColor: '#1e8081'
        });
        return false;
    }
    
    // Los inputs ocultos ya están agregados por updateItemsList
    return true;
});

// Mostrar alerta de error si hay un error de stock
@if(session('error'))
    @if(str_contains(session('error'), 'Stock insuficiente'))
        Swal.fire({
            icon: 'error',
            title: 'Stock Insuficiente',
            text: '{{ session('error') }}',
            confirmButtonColor: '#c94a2d',
            confirmButtonText: 'Entendido'
        });
    @else
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '{{ session('error') }}',
            confirmButtonColor: '#c94a2d',
            confirmButtonText: 'Entendido'
        });
    @endif
@endif

// Mostrar alerta de éxito
@if(session('success'))
    Swal.fire({
        icon: 'success',
        title: 'Éxito',
        text: '{{ session('success') }}',
        confirmButtonColor: '#1e8081',
        confirmButtonText: 'Entendido'
    });
@endif
</script>
@endsection

