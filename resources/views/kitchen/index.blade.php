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
                                            <form action="{{ route('kitchen.update-item-status', $item) }}" method="POST" class="d-inline">
                                                @csrf
                                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto;">
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

<script>
// Auto-refresh cada 30 segundos
setTimeout(function() {
    location.reload();
}, 30000);
</script>
@endsection

