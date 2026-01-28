@extends('layouts.app')

@section('title', 'Vista de Cocina')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1><i class="bi bi-fire"></i> Vista de Cocina</h1>
        <p class="text-muted">Gestión de pedidos en preparación</p>
    </div>
</div>

@if(isset($orders) && count($orders) > 0)
    @foreach(['ENVIADO', 'EN_PREPARACION', 'LISTO'] as $status)
        @if(isset($orders[$status]) && $orders[$status]->count() > 0)
        <div class="card mb-4">
            <div class="card-header bg-{{ $status === 'LISTO' ? 'success' : ($status === 'EN_PREPARACION' ? 'warning' : 'info') }}">
                <h5 class="mb-0 text-white">
                    {{ $status === 'ENVIADO' ? 'Pendientes' : ($status === 'EN_PREPARACION' ? 'En Preparación' : 'Listos') }}
                    <span class="badge bg-light text-dark">{{ $orders[$status]->count() }}</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($orders[$status] as $order)
                    <div class="col-md-6 mb-3">
                        <div class="card border-{{ $status === 'LISTO' ? 'success' : ($status === 'EN_PREPARACION' ? 'warning' : 'primary') }}">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $order->number }}</strong> - Mesa {{ $order->table->number }}
                                        <br>
                                        <small>{{ $order->created_at->format('H:i') }}</small>
                                    </div>
                                    @if($status === 'ENVIADO')
                                    <form action="{{ route('kitchen.mark-ready', $order) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning">
                                            Iniciar
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    @foreach($order->items as $item)
                                    <li class="mb-2">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong>{{ $item->quantity }}x</strong> {{ $item->product->name }}
                                                @if($item->observations)
                                                <br><small class="text-muted">{{ $item->observations }}</small>
                                                @endif
                                            </div>
                                            <form action="{{ route('kitchen.update-item-status', $item) }}" method="POST" class="d-inline js-item-status-form" data-item-name="{{ $item->product->name }}" data-table-name="{{ $order->table->number }}">
                                                @csrf
                                                <select name="status" class="form-select form-select-sm js-item-status-select" style="width: auto;">
                                                    <option value="PENDIENTE" {{ $item->status === 'PENDIENTE' ? 'selected' : '' }}>Pendiente</option>
                                                    <option value="EN_PREPARACION" {{ $item->status === 'EN_PREPARACION' ? 'selected' : '' }}>Preparando</option>
                                                    <option value="LISTO" {{ $item->status === 'LISTO' ? 'selected' : '' }}>Listo</option>
                                                    <option value="ENTREGADO" {{ $item->status === 'ENTREGADO' ? 'selected' : '' }}>Entregado</option>
                                                </select>
                                            </form>
                                        </div>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    @endforeach
@else
<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> No hay pedidos en cocina en este momento.
</div>
@endif

@push('scripts')
<script>
// Auto-refresh cada 30 segundos (solo si no estamos interactuando)
let kitchenInteracting = false;
setTimeout(function() {
    if (!kitchenInteracting) {
        location.reload();
    }
}, 30000);

document.querySelectorAll('.js-item-status-select').forEach((select) => {
    select.addEventListener('focus', () => kitchenInteracting = true);
    select.addEventListener('blur', () => kitchenInteracting = false);
});

document.querySelectorAll('.js-item-status-form').forEach((form) => {
    form.addEventListener('change', async (e) => {
        const select = form.querySelector('.js-item-status-select');
        const newStatus = select.value;

        const itemName = form.dataset.itemName || 'Item';
        const tableName = form.dataset.tableName || 'Mesa';

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({ status: newStatus }).toString(),
            });

            if (!res.ok) {
                throw new Error('No se pudo actualizar el estado');
            }

            if (newStatus === 'ENTREGADO') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `✅ Pedido “${itemName}” entregado en ${tableName}`,
                    showConfirmButton: false,
                    timer: 2500,
                    timerProgressBar: true,
                });
            } else {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Estado actualizado: ${itemName} → ${newStatus}`,
                    showConfirmButton: false,
                    timer: 1500,
                    timerProgressBar: true,
                });
            }
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo actualizar el estado del item.',
                confirmButtonColor: '#c94a2d',
            });
        }
    });
});
</script>
@endpush
@endsection

