@extends('layouts.app')

@section('title', 'Pedido #' . $order->number)

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('orders.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-receipt"></i> Pedido: {{ $order->number }}</h1>
        <p class="text-muted">
            @if($order->table)
                Mesa: {{ $order->table->number }} | 
            @elseif($order->customer_name)
                Consumidor: <span class="badge bg-info">{{ $order->customer_name }}</span> | 
            @endif
            Mozo: {{ $order->user->name }} | 
            @php
                $displayStatus = $order->status === 'CERRADO'
                    ? 'Se cierra la mesa'
                    : ($order->status === 'ENTREGADO' ? 'Se entrega el producto' : 'Se toma el pedido');
                $badgeClass = in_array($order->status, ['CERRADO', 'ENTREGADO'], true) ? 'success' : 'secondary';
            @endphp
            Estado: <span class="badge bg-{{ $badgeClass }}">{{ $displayStatus }}</span>
        </p>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Items del Pedido</h5>
                @if($order->status === 'ABIERTO' && $order->table_id)
                <a href="{{ route('orders.create', ['tableId' => $order->table_id]) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus"></i> Agregar Item
                </a>
                @endif
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio Unit.</th>
                                <th>Subtotal</th>
                                <th style="width:180px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($groupedItems as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item['product']->name }}</strong>
                                    @if($item['product']->category)
                                        <br><small class="text-muted">{{ $item['product']->category->name }}</small>
                                    @endif
                                    @if(isset($item['modifiers']) && $item['modifiers']->count() > 0)
                                        <br><small class="text-info">
                                            @foreach($item['modifiers'] as $modifier)
                                                + {{ $modifier->name }} 
                                            @endforeach
                                        </small>
                                    @endif
                                    @if(!empty($item['observations']))
                                    <br><small class="text-muted">{{ $item['observations'] }}</small>
                                    @endif
                                </td>
                                <td>{{ $item['quantity'] }}</td>
                                <td>${{ number_format($item['unit_price'], 2) }}</td>
                                <td><strong>${{ number_format($item['subtotal'], 2) }}</strong></td>
                                <td>
                                    @php
                                        $oldIds = implode(',', $item['order_item_ids'] ?? []);
                                        $qty = (int) ($item['quantity'] ?? 1);
                                        $obs = $item['observations'] ?? '';
                                    @endphp

                                    @can('update', $order)
                                        <form action="{{ route('orders.items-group.remove', $order) }}" method="POST" onsubmit="return confirm('¿Eliminar este subitem y reponer stock?');">
                                            @csrf
                                            <input type="hidden" name="old_item_ids" value="{{ $oldIds }}">
                                            <button type="submit" class="btn btn-danger btn-sm w-100">
                                                Eliminar
                                            </button>
                                        </form>

                                        <button
                                            type="button"
                                            class="btn btn-secondary btn-sm w-100 mt-2"
                                            data-old-ids="{{ $oldIds }}"
                                            data-qty="{{ $qty }}"
                                            data-obs="{{ $obs }}"
                                            @disabled(!($order->status === 'ABIERTO' || $order->status === 'EN_PREPARACION'))
                                            onclick="openReplaceItemModalFromBtn(this)"
                                        >
                                            Cambiar
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3">Subtotal</th>
                                <th>${{ number_format($order->subtotal, 2) }}</th>
                                <th></th>
                            </tr>
                            @if($order->discount > 0)
                            <tr>
                                <th colspan="3">Descuento</th>
                                <th>-${{ number_format($order->discount, 2) }}</th>
                                <th></th>
                            </tr>
                            @endif
                            <tr>
                                <th colspan="3">Total</th>
                                <th>${{ number_format($order->total, 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        @if($order->observations)
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Observaciones</h5>
            </div>
            <div class="card-body">
                <p>{{ $order->observations }}</p>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Acciones</h5>
            </div>
            <div class="card-body">
                @if(in_array(auth()->user()->role, ['ADMIN', 'MOZO']))
                    @if(!in_array($order->status, ['ENTREGADO', 'CERRADO', 'CANCELADO'], true))
                        <form action="{{ route('orders.update-status', $order) }}" method="POST" class="mb-2">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="status" value="ENTREGADO">
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('¿Se entrega el producto?')">
                                <i class="bi bi-check-circle"></i> Se entrega el producto
                            </button>
                        </form>
                    @endif
                @endif

                @if($order->status === 'ENTREGADO' || $order->status === 'CERRADO')
                @can('update', $order)
                <form action="{{ route('orders.close', $order) }}" method="POST" class="mb-2">
                    @csrf
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check-circle"></i> Se cierra la mesa
                    </button>
                </form>
                @endcan
                @endif

                <div class="mt-3">
                    <h6>Imprimir:</h6>
                    <div class="d-grid gap-2">
                        <a href="{{ route('orders.print.kitchen', $order) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-printer"></i> Ticket Cocina
                        </a>
                        <a href="{{ route('orders.print.invoice', $order) }}" target="_blank" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-printer"></i> Recibo / Factura
                        </a>
                        <a href="{{ route('orders.print.ticket', $order) }}" target="_blank" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-printer"></i> Ticket Simple
                        </a>
                    </div>
                </div>

                @if($order->status === 'CERRADO' && $order->payments->count() > 0)
                <div class="mt-3">
                    <h6>Pagos:</h6>
                    @foreach($order->payments as $payment)
                    <div class="small mb-2">
                        <strong>{{ $payment->payment_method }}:</strong> 
                        ${{ number_format($payment->amount, 2) }}
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Información</h5>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    <strong>Fecha:</strong><br>
                    {{ $order->created_at->format('d/m/Y H:i') }}
                </p>
                @if($order->sent_at)
                <p class="mb-2">
                    <strong>Enviado:</strong><br>
                    {{ $order->sent_at->format('d/m/Y H:i') }}
                </p>
                @endif
                @if($order->closed_at)
                <p class="mb-2">
                    <strong>Cerrado:</strong><br>
                    {{ $order->closed_at->format('d/m/Y H:i') }}
                </p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal: Cambiar item por otro producto -->
<div class="modal fade" id="replaceItemModal" tabindex="-1" aria-labelledby="replaceItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form action="{{ route('orders.items-group.replace', $order) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="replaceItemModalLabel">Cambiar subitem</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="old_item_ids" id="replaceOldItemIds">
                    <input type="hidden" name="observations" id="replaceObservations">

                    <div class="mb-3">
                        <label class="form-label">Producto nuevo</label>
                        <select name="new_product_id" id="replaceNewProductId" class="form-select" required>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}{{ $product->category ? ' - '.$product->category->name : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Cantidad</label>
                        <input type="number" name="quantity" id="replaceQuantity" class="form-control" min="1" value="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Reemplazar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
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
    @if(session('order_delivered'))
        // MÓDULO 2: Alerta flotante especial para pedidos entregados
        Swal.fire({
            icon: 'success',
            title: '✅ Pedido Entregado',
            html: `Pedido #{{ session('order_delivered.order_number') }} entregado en Mesa {{ session('order_delivered.table_number') }}`,
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

@if(session('kitchen_ticket_url'))
    window.open('{{ session('kitchen_ticket_url') }}', 'kitchen_print', 'noopener,noreferrer,width=450,height=700');
@endif
</script>

<script>
    function openReplaceItemModal(oldIds, qty, obs) {
        const modalEl = document.getElementById('replaceItemModal');
        if (!modalEl) return;

        const oldIdsInput = document.getElementById('replaceOldItemIds');
        const qtyInput = document.getElementById('replaceQuantity');
        const obsInput = document.getElementById('replaceObservations');

        oldIdsInput.value = oldIds || '';
        qtyInput.value = qty || 1;
        obsInput.value = obs || '';

        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    function openReplaceItemModalFromBtn(btn) {
        if (!btn) return;
        openReplaceItemModal(btn.dataset.oldIds || '', parseInt(btn.dataset.qty || '1', 10), btn.dataset.obs || '');
    }
</script>
@endpush
@endsection

