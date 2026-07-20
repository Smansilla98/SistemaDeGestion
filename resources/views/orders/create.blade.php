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
            <div class="card-body">
                @include('orders.partials.product-picker', [
                    'products' => $products,
                    'searchId' => 'productSearch',
                    'addFn' => 'addProduct',
                    'colClass' => 'col-md-4 mb-3',
                    'sectionClass' => 'category-section',
                ])
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
                    
                    @include('orders.partials.table-selector', [
                        'tables' => $tables,
                        'selectedTable' => $selectedTable ?? null,
                    ])

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
// Selector de mesa (chip fijo o mapa visual)
(function initOrderTablePicker() {
    const root = document.querySelector('[data-order-table-picker]');
    if (!root) return;

    const chipBlock = root.querySelector('[data-table-chip]');
    const mapBlock = root.querySelector('[data-table-map]');
    const changeBtn = root.querySelector('[data-change-table]');

    function getActiveTableInput() {
        return document.getElementById('table_id') || document.getElementById('table_id_map');
    }

    function selectTable(btn) {
        root.querySelectorAll('[data-pick-table]').forEach(el => el.classList.remove('is-selected'));
        btn.classList.add('is-selected');
        const input = getActiveTableInput();
        if (input) {
            input.value = btn.dataset.tableId;
            input.disabled = false;
            input.name = 'table_id';
            input.id = 'table_id';
        }
    }

    root.querySelectorAll('[data-pick-table]').forEach(btn => {
        btn.addEventListener('click', () => selectTable(btn));
    });

    changeBtn?.addEventListener('click', () => {
        if (!chipBlock || !mapBlock) return;
        chipBlock.classList.add('d-none');
        const hidden = chipBlock.querySelector('#table_id');
        if (hidden) {
            hidden.removeAttribute('name');
            hidden.disabled = true;
            hidden.id = 'table_id_chip';
        }
        mapBlock.classList.remove('d-none');
        const mapInput = mapBlock.querySelector('#table_id_map');
        if (mapInput) {
            mapInput.disabled = false;
            mapInput.name = 'table_id';
            mapInput.id = 'table_id';
        }
    });
})();

// Búsqueda de productos (product-picker partial)
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
        item.quantity = Math.max(1, parseInt(quantity, 10) || 1);
        updateItemsList();
    }
}

function bumpQuantity(itemId, delta) {
    const item = orderItems.find(i => i.id === itemId);
    if (!item) return;
    const next = (parseInt(item.quantity, 10) || 1) + delta;
    if (next <= 0) {
        removeItem(itemId);
        return;
    }
    item.quantity = next;
    updateItemsList();
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
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(${item.id})" aria-label="Quitar ${item.name}">
                        <i class="bi bi-trash" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="mt-2 d-flex align-items-center justify-content-between">
                    <span class="small text-muted">Cantidad</span>
                    <div class="order-qty-stepper">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="bumpQuantity(${item.id}, -1)" aria-label="Restar">
                            <i class="bi bi-dash" aria-hidden="true"></i>
                        </button>
                        <span class="qty-value" aria-live="polite">${item.quantity}</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="bumpQuantity(${item.id}, 1)" aria-label="Sumar">
                            <i class="bi bi-plus" aria-hidden="true"></i>
                        </button>
                    </div>
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

// Interceptar envío del formulario: AJAX + ventana de impresión en el clic
document.getElementById('orderForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    if (orderItems.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Pedido vacío',
            text: 'Debes agregar al menos un item al pedido',
            confirmButtonColor: '#1e8081'
        });
        return;
    }

    var printWin = window.open('', 'kitchen_print', 'noopener,noreferrer,width=450,height=700');
    var submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creando...';

    try {
        var formData = {
            table_id: document.getElementById('table_id').value,
            observations: document.getElementById('observations').value,
            items: orderItems.map(function(i) {
                return { product_id: i.product_id, quantity: parseInt(i.quantity) || 1 };
            }),
            _token: document.querySelector('input[name="_token"]').value
        };
        var res = await fetch('{{ route("orders.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': formData._token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                table_id: formData.table_id,
                observations: formData.observations,
                items: formData.items
            })
        });
        var data = await res.json().catch(function() { return {}; });
        if (!data.success) {
            throw new Error(data.message || 'No se pudo crear el pedido');
        }
        // Abrir ticket de cocina en ventana nueva; el usuario solo acepta en el diálogo de impresión
        if (data.kitchen_ticket_url && printWin && !printWin.closed) {
            printWin.location.href = data.kitchen_ticket_url;
            setTimeout(function() { try { if (printWin && !printWin.closed) printWin.close(); } catch (e) {} }, 3500);
        } else if (data.kitchen_ticket_url) {
            var w = window.open(data.kitchen_ticket_url, 'kitchen_print', 'noopener,noreferrer,width=450,height=700');
            if (w) setTimeout(function() { try { if (w && !w.closed) w.close(); } catch (e) {} }, 3500);
        }
        window.location.href = data.redirect || '{{ url("/orders") }}/' + data.order_id;
    } catch (err) {
        try { if (printWin && !printWin.closed) printWin.close(); } catch (e) {}
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Crear Pedido';
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: err.message || 'No se pudo crear el pedido',
            confirmButtonColor: '#c94a2d'
        });
    }
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

