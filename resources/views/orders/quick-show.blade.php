@extends('layouts.app')

@section('title', 'Pedido Rápido: ' . $order->number)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('orders.quick.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver a Pedidos Rápidos
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
            <i class="bi bi-receipt"></i> Pedido Rápido: {{ $order->number }}
        </h1>
        <p class="text-muted">
            Cliente: <strong>{{ $order->customer_name }}</strong> | 
            Estado: <span class="badge bg-{{ 
                $order->status === 'CERRADO' ? 'success' : 
                ($order->status === 'LISTO' ? 'info' : 
                ($order->status === 'ABIERTO' ? 'secondary' : 'warning')) 
            }}">{{ $order->status }}</span> |
            Creado por: {{ $order->user->name }} | 
            Fecha: {{ $order->created_at->format('d/m/Y H:i') }}
        </p>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        @php
            $totalItems = $individualItems->count();
            $completedItems = $individualItems->filter(function($item) {
                return strtoupper(trim($item->status ?? '')) === 'ENTREGADO';
            })->count();
            $pendingItems = $totalItems - $completedItems;
        @endphp
        
        <!-- Contador de progreso -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1"><i class="bi bi-list-check"></i> Progreso del Pedido</h6>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar {{ $completedItems === $totalItems ? 'bg-success' : 'bg-primary' }}" 
                                 role="progressbar" 
                                 style="width: {{ $totalItems > 0 ? ($completedItems / $totalItems * 100) : 0 }}%"
                                 aria-valuenow="{{ $completedItems }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="{{ $totalItems }}">
                                <strong>{{ $completedItems }}/{{ $totalItems }} items completados</strong>
                            </div>
                        </div>
                    </div>
                    <div class="text-end ms-3">
                        <div class="badge bg-success fs-6">{{ $completedItems }}</div>
                        <div class="text-muted small">de {{ $totalItems }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Items del Pedido</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio Unit.</th>
                                <th>Subtotal</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($individualItems as $item)
                            <tr data-item-id="{{ $item->id }}" class="{{ $item->status === 'ENTREGADO' ? 'table-success' : '' }}">
                                <td>
                                    <strong>{{ $item->product->name }}</strong>
                                    @if($item->product->category)
                                        <br><small class="text-muted">{{ $item->product->category->name }}</small>
                                    @endif
                                    @if($item->modifiers->count() > 0)
                                        <br><small class="text-info">
                                            @foreach($item->modifiers as $modifier)
                                                + {{ $modifier->name }} 
                                            @endforeach
                                        </small>
                                    @endif
                                    @if($item->observations)
                                        <br><small class="text-muted"><em>{{ $item->observations }}</em></small>
                                    @endif
                                </td>
                                <td>{{ $item->quantity }}</td>
                                <td>${{ number_format($item->unit_price, 2) }}</td>
                                <td><strong>${{ number_format($item->subtotal, 2) }}</strong></td>
                                <td>
                                    <span class="badge bg-{{ 
                                        $item->status === 'ENTREGADO' ? 'success' : 
                                        ($item->status === 'LISTO' ? 'info' : 
                                        ($item->status === 'EN_PREPARACION' ? 'warning' : 'secondary')) 
                                    }}" id="item-status-{{ $item->id }}">
                                        {{ $item->status }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $userRole = auth()->user()->role ?? null;
                                        $itemStatus = strtoupper(trim($item->status ?? 'PENDIENTE'));
                                        $canUpdate = in_array($userRole, ['ADMIN', 'MOZO', 'COCINA']);
                                    @endphp
                                    
                                    @if($canUpdate)
                                        @if($itemStatus === 'PENDIENTE')
                                            <button class="btn btn-sm btn-warning update-item-status" 
                                                    data-item-id="{{ $item->id }}" 
                                                    data-status="EN_PREPARACION"
                                                    title="Marcar en preparación">
                                                <i class="bi bi-gear"></i> En Preparación
                                            </button>
                                        @elseif($itemStatus === 'EN_PREPARACION')
                                            <button class="btn btn-sm btn-info update-item-status" 
                                                    data-item-id="{{ $item->id }}" 
                                                    data-status="LISTO"
                                                    title="Marcar como listo">
                                                <i class="bi bi-check2-circle"></i> Listo
                                            </button>
                                        @elseif($itemStatus === 'LISTO')
                                            <button class="btn btn-sm btn-success update-item-status" 
                                                    data-item-id="{{ $item->id }}" 
                                                    data-status="ENTREGADO"
                                                    title="Marcar como entregado">
                                                <i class="bi bi-check-circle"></i> Entregado
                                            </button>
                                        @elseif($itemStatus === 'ENTREGADO')
                                            <span class="text-muted small"><i class="bi bi-check-circle-fill text-success"></i> Completado</span>
                                        @else
                                            {{-- Estado desconocido, mostrar botón por defecto --}}
                                            <button class="btn btn-sm btn-warning update-item-status" 
                                                    data-item-id="{{ $item->id }}" 
                                                    data-status="EN_PREPARACION"
                                                    title="Marcar en preparación">
                                                <i class="bi bi-gear"></i> En Preparación
                                            </button>
                                        @endif
                                    @else
                                        <span class="text-muted" title="Rol: {{ $userRole }}">-</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Subtotal:</th>
                                <th>${{ number_format($order->subtotal, 2) }}</th>
                                <th colspan="2"></th>
                            </tr>
                            @if($order->discount > 0)
                            <tr>
                                <th colspan="3" class="text-end">Descuento:</th>
                                <th class="text-danger">-${{ number_format($order->discount, 2) }}</th>
                                <th colspan="2"></th>
                            </tr>
                            @endif
                            <tr class="table-primary">
                                <th colspan="3" class="text-end">Total:</th>
                                <th class="fs-5">${{ number_format($order->total, 2) }}</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($order->observations)
                <div class="mt-3">
                    <strong>Observaciones del pedido:</strong>
                    <p class="text-muted">{{ $order->observations }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Información</h5>
            </div>
            <div class="card-body">
                <p><strong>Número:</strong> {{ $order->number }}</p>
                <p><strong>Cliente:</strong> {{ $order->customer_name }}</p>
                <p><strong>Estado:</strong> 
                    <span class="badge bg-{{ 
                        $order->status === 'CERRADO' ? 'success' : 
                        ($order->status === 'LISTO' ? 'info' : 
                        ($order->status === 'ABIERTO' ? 'secondary' : 'warning')) 
                    }}">{{ $order->status }}</span>
                </p>
                <p><strong>Creado por:</strong> {{ $order->user->name }}</p>
                <p><strong>Fecha creación:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                @if($order->closed_at)
                <p><strong>Fecha cierre:</strong> {{ $order->closed_at->format('d/m/Y H:i') }}</p>
                @endif
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-cash-coin"></i> Pagos</h5>
            </div>
            <div class="card-body">
                @if($order->payments->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Método</th>
                                <th>Monto</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->payments as $payment)
                            <tr>
                                <td>
                                    <span class="badge bg-{{ 
                                        $payment->payment_method === 'EFECTIVO' ? 'success' : 
                                        ($payment->payment_method === 'DEBITO' ? 'primary' : 
                                        ($payment->payment_method === 'CREDITO' ? 'info' : 'secondary')) 
                                    }}">
                                        {{ $payment->payment_method }}
                                    </span>
                                </td>
                                <td><strong>${{ number_format($payment->amount, 2) }}</strong></td>
                                <td>{{ $payment->created_at->format('d/m H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total pagado:</th>
                                <th>${{ number_format($order->payments->sum('amount'), 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @else
                <p class="text-muted text-center">No hay pagos registrados</p>
                @endif
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-gear"></i> Acciones</h5>
            </div>
            <div class="card-body">
                @if(in_array(auth()->user()->role, ['ADMIN', 'MOZO']))
                    @if($order->status === 'ABIERTO')
                        <form action="{{ route('orders.update-status', $order) }}" method="POST" class="mb-2">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="EN_PREPARACION">
                            <button type="submit" class="btn btn-warning w-100" onclick="return confirm('¿Marcar pedido como EN PREPARACIÓN?')">
                                <i class="bi bi-gear"></i> Marcar en Preparación
                            </button>
                        </form>
                    @elseif($order->status === 'ENVIADO')
                        <form action="{{ route('orders.update-status', $order) }}" method="POST" class="mb-2">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="EN_PREPARACION">
                            <button type="submit" class="btn btn-warning w-100" onclick="return confirm('¿Marcar pedido como EN PREPARACIÓN?')">
                                <i class="bi bi-gear"></i> Marcar en Preparación
                            </button>
                        </form>
                        <form action="{{ route('orders.update-status', $order) }}" method="POST" class="mb-2">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="ENTREGADO">
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('¿Marcar pedido como ENTREGADO?')">
                                <i class="bi bi-check-circle"></i> Marcar como Entregado
                            </button>
                        </form>
                    @elseif($order->status === 'EN_PREPARACION')
                        <form action="{{ route('orders.update-status', $order) }}" method="POST" class="mb-2">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="LISTO">
                            <button type="submit" class="btn btn-info w-100" onclick="return confirm('¿Marcar pedido como LISTO?')">
                                <i class="bi bi-check2-circle"></i> Marcar como Listo
                            </button>
                        </form>
                        <form action="{{ route('orders.update-status', $order) }}" method="POST" class="mb-2">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="ENTREGADO">
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('¿Marcar pedido como ENTREGADO?')">
                                <i class="bi bi-check-circle"></i> Marcar como Entregado
                            </button>
                        </form>
                    @elseif($order->status === 'LISTO')
                        <form action="{{ route('orders.update-status', $order) }}" method="POST" class="mb-2">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="ENTREGADO">
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('¿Marcar pedido como ENTREGADO?')">
                                <i class="bi bi-check-circle"></i> Marcar como Entregado
                            </button>
                        </form>
                    @endif
                @endif

                <div class="mt-3">
                    <h6>Imprimir:</h6>
                    <div class="d-grid gap-2">
                        <a href="{{ route('orders.print.kitchen', $order) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> Ver PDF (Cocina)
                        </a>
                        @if($order->status === 'CERRADO')
                        <a href="{{ route('orders.print.invoice', $order) }}" target="_blank" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-printer"></i> Factura
                        </a>
                        <a href="{{ route('orders.print.ticket', $order) }}" target="_blank" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-printer"></i> Ticket Simple
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($order->status !== 'CERRADO')
        <div class="card mt-4">
            <div class="card-body">
                <button type="button" class="btn btn-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#addItemsModal">
                    <i class="bi bi-plus-circle"></i> Agregar Items
                </button>
                <a href="{{ route('orders.quick.close', $order) }}" class="btn btn-success w-100">
                    <i class="bi bi-cash-coin"></i> Cerrar Cuenta
                </a>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal para agregar items -->
@if($order->status !== 'CERRADO')
<div class="modal fade" id="addItemsModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen-md-down modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle"></i> Agregar Items al Pedido {{ $order->number }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-lg-7">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3 gap-2">
                            <h6 class="mb-0"><i class="bi bi-card-list"></i> Productos</h6>
                            <input type="text" class="form-control" id="addItemsProductSearch" placeholder="🔍 Buscar producto..." style="max-width: 100%;">
                        </div>

                        <div id="addItemsProductsAccordion">
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
                                                                class="btn btn-sm btn-primary add-product-to-order-btn" 
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

                    <div class="col-lg-5">
                        <div class="sticky-top" style="top: 70px;">
                            <h6 class="mb-3"><i class="bi bi-receipt"></i> Items a Agregar</h6>

                            <div id="addItemsEmpty" class="text-muted text-center py-3">No hay items seleccionados.</div>
                            <div id="addItemsList" class="mb-3"></div>

                            <div class="border-top pt-3 pb-2">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <strong class="fs-5">Total a Agregar:</strong> <span id="addItemsTotal" class="fs-4 fw-bold text-primary">$0.00</span>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-success w-100" id="addItemsConfirmBtn" disabled style="min-height: 52px; font-size: 1.125rem; font-weight: 700;">
                                    <i class="bi bi-check-circle"></i> Agregar Items al Pedido
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
// Función para actualizar estado de item
async function updateItemStatusHandler(event) {
    const btn = event.currentTarget;
    const itemId = btn.dataset.itemId;
    const newStatus = btn.dataset.status;
    
    const statusLabels = {
            'PENDIENTE': 'PENDIENTE',
            'EN_PREPARACION': 'EN PREPARACIÓN',
            'LISTO': 'LISTO',
            'ENTREGADO': 'ENTREGADO'
        };
        
        const confirmResult = await Swal.fire({
            icon: 'question',
            title: '¿Cambiar estado?',
            text: `¿Marcar este item como ${statusLabels[newStatus]}?`,
            showCancelButton: true,
            confirmButtonColor: '#1e8081',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar'
        });
        
        if (!confirmResult.isConfirmed) {
            return;
        }
        
        // Mostrar loading
        Swal.fire({
            title: 'Actualizando...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        try {
            const url = `{{ route('orders.update-item-status', ['item' => '__ITEM_ID__']) }}`.replace('__ITEM_ID__', itemId);
            const response = await fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    status: newStatus
                })
            });
            
            const contentType = response.headers.get('content-type');
            let data;
            
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                const text = await response.text();
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('Respuesta no válida del servidor');
                }
            }
            
            if (data.success) {
                // Actualizar badge de estado
                const statusBadge = document.getElementById(`item-status-${itemId}`);
                const statusColors = {
                    'PENDIENTE': 'secondary',
                    'EN_PREPARACION': 'warning',
                    'LISTO': 'info',
                    'ENTREGADO': 'success'
                };
                
                statusBadge.textContent = newStatus;
                statusBadge.className = `badge bg-${statusColors[newStatus]}`;
                
                // Actualizar fila
                const row = document.querySelector(`tr[data-item-id="${itemId}"]`);
                if (newStatus === 'ENTREGADO') {
                    row.classList.add('table-success');
                } else {
                    row.classList.remove('table-success');
                }
                
                // Actualizar botones de acción
                const actionCell = row.querySelector('td:last-child');
                const nextStatus = {
                    'PENDIENTE': { status: 'EN_PREPARACION', icon: 'bi-gear', color: 'warning', title: 'Marcar en preparación' },
                    'EN_PREPARACION': { status: 'LISTO', icon: 'bi-check2-circle', color: 'info', title: 'Marcar como listo' },
                    'LISTO': { status: 'ENTREGADO', icon: 'bi-check-circle', color: 'success', title: 'Marcar como entregado' }
                };
                
                if (nextStatus[newStatus]) {
                    const newBtn = document.createElement('button');
                    newBtn.className = `btn btn-sm btn-${nextStatus[newStatus].color} update-item-status`;
                    newBtn.setAttribute('data-item-id', itemId);
                    newBtn.setAttribute('data-status', nextStatus[newStatus].status);
                    newBtn.setAttribute('title', nextStatus[newStatus].title);
                    newBtn.innerHTML = `<i class="bi ${nextStatus[newStatus].icon}"></i>`;
                    
                    // Agregar event listener
                    newBtn.addEventListener('click', updateItemStatusHandler);
                    
                    actionCell.innerHTML = '';
                    actionCell.appendChild(newBtn);
                } else {
                    actionCell.innerHTML = '<span class="text-muted">-</span>';
                }
                
                // Actualizar contador de progreso
                if (data.stats) {
                    const progressBar = document.querySelector('.progress-bar');
                    const progressText = progressBar.querySelector('strong');
                    const percentage = (data.stats.completed / data.stats.total) * 100;
                    
                    progressBar.style.width = `${percentage}%`;
                    progressBar.setAttribute('aria-valuenow', data.stats.completed);
                    progressText.textContent = `${data.stats.completed}/${data.stats.total} items completados`;
                    
                    if (data.stats.completed === data.stats.total) {
                        progressBar.classList.remove('bg-primary');
                        progressBar.classList.add('bg-success');
                    } else {
                        progressBar.classList.remove('bg-success');
                        progressBar.classList.add('bg-primary');
                    }
                    
                    // Actualizar badge
                    const badge = document.querySelector('.badge.bg-success.fs-6');
                    if (badge) {
                        badge.textContent = data.stats.completed;
                    }
                }
                
                Swal.fire({
                    icon: 'success',
                    title: '¡Estado actualizado!',
                    text: `Item marcado como ${statusLabels[newStatus]}`,
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                throw new Error(data.message || 'Error al actualizar el estado');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Error al actualizar el estado del item',
                confirmButtonColor: '#dc3545'
            });
        }
}

// Agregar event listeners a todos los botones de actualizar estado cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.update-item-status').forEach(btn => {
        btn.addEventListener('click', updateItemStatusHandler);
    });
    
    // También agregar listeners después de un pequeño delay por si hay contenido dinámico
    setTimeout(function() {
        document.querySelectorAll('.update-item-status').forEach(btn => {
            // Verificar si ya tiene el listener para evitar duplicados
            if (!btn.hasAttribute('data-listener-added')) {
                btn.addEventListener('click', updateItemStatusHandler);
                btn.setAttribute('data-listener-added', 'true');
            }
        });
    }, 100);
});

// Mostrar alerta de éxito
@if(session('success'))
    @if(session('order_delivered'))
        // Alerta flotante especial para pedidos entregados
        Swal.fire({
            icon: 'success',
            title: '✅ Pedido Entregado',
            html: `Pedido #{{ session('order_delivered.order_number') }} entregado{{ session('order_delivered.table_number') ? ' en Mesa ' . session('order_delivered.table_number') : (session('order_delivered.customer_name') ? ' a ' . session('order_delivered.customer_name') : '') }}`,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
    @else
        Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: '{{ session('success') }}',
            confirmButtonColor: '#1e8081',
            confirmButtonText: 'Entendido'
        });
    @endif
@endif

// Mostrar alerta de error
@if(session('error'))
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '{{ session('error') }}',
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Entendido'
    });
@endif

let addItemsList = [];
let addItemsCounter = 0;

// Búsqueda de productos en modal de agregar items
document.getElementById('addItemsProductSearch')?.addEventListener('input', function() {
    filterAddItemsProducts(this.value.toLowerCase().trim());
});

function filterAddItemsProducts(term) {
    document.querySelectorAll('#addItemsProductsAccordion .category-section-modal').forEach(section => {
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

// Agregar producto a la lista de items a agregar
document.querySelectorAll('.add-product-to-order-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = parseInt(this.dataset.productId);
        const productName = this.dataset.productName;
        const productPrice = parseFloat(this.dataset.productPrice);
        
        const existingItem = addItemsList.find(item => item.product_id === productId);
        
        if (existingItem) {
            existingItem.quantity++;
        } else {
            addItemsList.push({
                product_id: productId,
                name: productName,
                price: productPrice,
                quantity: 1,
                observations: ''
            });
        }
        
        renderAddItemsList();
    });
});

// Renderizar lista de items a agregar
function renderAddItemsList() {
    const container = document.getElementById('addItemsList');
    const emptyMsg = document.getElementById('addItemsEmpty');
    const totalEl = document.getElementById('addItemsTotal');
    const confirmBtn = document.getElementById('addItemsConfirmBtn');
    
    if (addItemsList.length === 0) {
        container.innerHTML = '';
        emptyMsg.style.display = 'block';
        totalEl.textContent = '$0.00';
        confirmBtn.disabled = true;
        return;
    }
    
    emptyMsg.style.display = 'none';
    
    let total = 0;
    let html = '';
    
    addItemsList.forEach((item, index) => {
        const subtotal = item.price * item.quantity;
        total += subtotal;
        
        html += `
            <div class="card mb-2">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="flex-grow-1">
                            <strong>${item.name}</strong>
                            <div class="text-muted small">$${item.price.toFixed(2)} c/u</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="updateAddItemsQuantity(${index}, -1)">
                                <i class="bi bi-dash"></i>
                            </button>
                            <span class="fw-bold">${item.quantity}</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="updateAddItemsQuantity(${index}, 1)">
                                <i class="bi bi-plus"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeAddItemsItem(${index})">
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
    totalEl.textContent = `$${total.toFixed(2)}`;
    confirmBtn.disabled = false;
}

// Actualizar cantidad
function updateAddItemsQuantity(index, change) {
    if (addItemsList[index]) {
        addItemsList[index].quantity += change;
        if (addItemsList[index].quantity <= 0) {
            addItemsList.splice(index, 1);
        }
        renderAddItemsList();
    }
}

// Eliminar item
function removeAddItemsItem(index) {
    addItemsList.splice(index, 1);
    renderAddItemsList();
}

// Confirmar agregar items
document.getElementById('addItemsConfirmBtn')?.addEventListener('click', async function() {
    if (addItemsList.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin items',
            text: 'Debes agregar al menos un producto',
            confirmButtonColor: '#ffc107'
        });
        return;
    }
    
    // Mostrar loading
    Swal.fire({
        title: 'Agregando items...',
        text: 'Por favor espera',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Agregar items uno por uno
    let successCount = 0;
    let errorMessages = [];
    
    for (const item of addItemsList) {
        try {
            const response = await fetch('/orders/{{ $order->id }}/items', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    product_id: item.product_id,
                    quantity: item.quantity,
                    observations: item.observations || ''
                })
            });
            
            // Verificar si la respuesta es JSON
            const contentType = response.headers.get('content-type');
            let data;
            
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                const text = await response.text();
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('Respuesta no válida del servidor');
                }
            }
            
            if (data.success || response.ok) {
                successCount++;
            } else {
                const errorMsg = data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Error');
                errorMessages.push(`${item.name}: ${errorMsg}`);
            }
        } catch (error) {
            console.error('Error al agregar item:', error);
            errorMessages.push(`${item.name}: ${error.message || 'Error de conexión'}`);
        }
    }
    
    if (successCount > 0) {
        Swal.fire({
            icon: 'success',
            title: '¡Items agregados!',
            html: `
                <p>Se agregaron ${successCount} item(s) al pedido.</p>
                ${errorMessages.length > 0 ? `<p class="text-danger small">Errores: ${errorMessages.join(', ')}</p>` : ''}
            `,
            confirmButtonColor: '#1e8081'
        }).then(() => {
            // Cerrar modal y recargar página
            const modal = bootstrap.Modal.getInstance(document.getElementById('addItemsModal'));
            if (modal) {
                modal.hide();
            }
            window.location.reload();
        });
    } else {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            html: `<p>No se pudieron agregar los items:</p><p class="text-danger small">${errorMessages.join(', ')}</p>`,
            confirmButtonColor: '#dc3545'
        });
    }
});

// Limpiar modal al cerrar
document.getElementById('addItemsModal')?.addEventListener('hidden.bs.modal', function() {
    addItemsList = [];
    addItemsCounter = 0;
    document.getElementById('addItemsProductSearch').value = '';
    renderAddItemsList();
    filterAddItemsProducts('');
});
</script>
@endpush
@endsection

