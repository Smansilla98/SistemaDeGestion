@extends('layouts.mobile')

@section('title', 'Detalle Pedido')

@section('content')
<div class="container-fluid">
    <div class="mb-3">
        <h2 class="h5 mb-1">Pedido #{{ $order->number }}</h2>
        <p class="text-muted small mb-0">
            @if($order->table)
                Mesa {{ $order->table->number }}
            @else
                Pedido rápido
            @endif
            · Estado: {{ $order->status }}
        </p>
    </div>

    <div class="card bg-dark border-0 mb-3">
        <div class="card-body">
            <h6 class="card-title mb-2">Items</h6>
            <ul class="list-group list-group-flush">
                @foreach($order->items as $item)
                    <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">{{ $item->product->name }}</div>
                            <small class="text-muted">x{{ $item->quantity }}</small>
                        </div>
                        <div class="text-end">
                            <div>${{ number_format($item->subtotal, 2) }}</div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection

