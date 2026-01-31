@php
    $sentAt = $order->sent_at ?? $order->created_at;
    $elapsedMinutes = $sentAt ? \Carbon\Carbon::parse($sentAt)->diffInMinutes(now()) : 0;
    $isNew = $elapsedMinutes < 2;
@endphp

<div class="kds-order-card {{ $status }} {{ $isNew ? 'new' : '' }}" data-sent-at="{{ $sentAt }}">
    <div class="kds-order-header">
        <div class="kds-order-info">
            <h4>Pedido #{{ $order->number }}</h4>
            <div>
                <strong><i class="bi bi-table"></i> Mesa {{ $order->table->number }}</strong>
                @if($order->table->sector)
                    <span class="badge bg-secondary ms-2">{{ $order->table->sector->name }}</span>
                @endif
            </div>
        </div>
        <div class="kds-order-meta">
            <div class="kds-time-badge" data-sent-at="{{ $sentAt }}">
                @if($elapsedMinutes < 1)
                    < 1 min
                @elseif($elapsedMinutes < 60)
                    {{ $elapsedMinutes }} min
                @else
                    {{ floor($elapsedMinutes / 60) }}h {{ $elapsedMinutes % 60 }}m
                @endif
            </div>
            @if($elapsedMinutes > 15)
                <span class="badge bg-danger mt-1">⚠️ Urgente</span>
            @endif
        </div>
    </div>

    <div class="kds-items-list">
        @foreach($order->items as $item)
            @php
                $itemStatus = strtolower($item->status);
                if ($itemStatus === 'entregado') {
                    $itemStatus = 'entregado';
                } elseif (in_array($item->status, ['PENDIENTE', 'ENVIADO'])) {
                    $itemStatus = 'pending';
                } elseif ($item->status === 'EN_PREPARACION') {
                    $itemStatus = 'preparing';
                } elseif ($item->status === 'LISTO') {
                    $itemStatus = 'ready';
                }
            @endphp
            <div class="kds-item {{ $itemStatus }}">
                <div class="kds-item-header">
                    <div class="kds-item-name">
                        {{ $item->product->name }}
                        @if($item->product->category)
                            <small class="text-muted">({{ $item->product->category->name }})</small>
                        @endif
                    </div>
                    <span class="kds-item-qty">{{ $item->quantity }}x</span>
                </div>
                
                @if($item->observations)
                    <div class="kds-item-obs">
                        <i class="bi bi-info-circle"></i> {{ $item->observations }}
                    </div>
                @endif

                @if($item->modifiers && $item->modifiers->count() > 0)
                    <div class="kds-item-obs">
                        <i class="bi bi-tags"></i> 
                        {{ $item->modifiers->pluck('name')->join(', ') }}
                    </div>
                @endif

                <div class="kds-item-status">
                    <form action="{{ route('kitchen.update-item-status', $item) }}" 
                          method="POST" 
                          data-item-id="{{ $item->id }}"
                          data-item-name="{{ $item->product->name }}"
                          data-table-name="{{ $order->table->number }}">
                        @csrf
                        <select name="status" 
                                class="kds-status-select {{ $itemStatus }}"
                                data-previous-value="{{ $item->status }}">
                            <option value="PENDIENTE" {{ $item->status === 'PENDIENTE' ? 'selected' : '' }}>Pendiente</option>
                            <option value="EN_PREPARACION" {{ $item->status === 'EN_PREPARACION' ? 'selected' : '' }}>En Preparación</option>
                            <option value="LISTO" {{ $item->status === 'LISTO' ? 'selected' : '' }}>Listo</option>
                            <option value="ENTREGADO" {{ $item->status === 'ENTREGADO' ? 'selected' : '' }}>Entregado</option>
                        </select>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</div>

