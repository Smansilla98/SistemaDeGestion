<div class="d-flex flex-column gap-3">
    {{-- Mesas como chips --}}
    <div>
        <h6 class="mb-2 text-muted text-uppercase small">Mesa</h6>
        <div class="d-flex flex-wrap gap-2">
            @foreach($mesas as $mesaModel)
                <button type="button"
                        wire:click="selectMesa({{ $mesaModel->id }})"
                        class="btn {{ $mesa === $mesaModel->id ? 'btn-primary' : 'btn-outline-secondary' }}"
                        style="min-height: 48px; min-width: 72px;">
                    {{ $mesaModel->number }}
                    @if($mesaModel->status === 'OCUPADA')
                        <span class="d-block small opacity-75">ocupada</span>
                    @endif
                </button>
            @endforeach
        </div>
        @error('mesa') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>

    {{-- Buscador + grilla de productos --}}
    <div>
        <h6 class="mb-2 text-muted text-uppercase small">Productos</h6>
        <input type="text"
               wire:model.live.debounce.300ms="busqueda"
               class="form-control form-control-lg mb-2"
               placeholder="Buscar…"
               autocomplete="off"
               style="font-size: 16px; min-height: 48px;">

        <div class="row g-2" style="max-height: 42vh; overflow-y: auto;">
            @forelse($productos as $producto)
                <div class="col-6">
                    <button type="button"
                            wire:click="selectProducto({{ $producto->id }})"
                            class="btn w-100 text-start {{ $productoSeleccionado === $producto->id ? 'btn-primary' : 'btn-outline-light' }}"
                            style="min-height: 64px;">
                        <div class="fw-semibold text-truncate">{{ $producto->name }}</div>
                        <small>${{ number_format($producto->price, 2) }}</small>
                    </button>
                </div>
            @empty
                <div class="col-12 text-muted small">Sin productos</div>
            @endforelse
        </div>

        <div class="d-flex align-items-center gap-2 mt-2">
            <div class="input-group" style="max-width: 140px;">
                <button type="button" class="btn btn-outline-secondary" style="min-height: 48px;" wire:click="$set('cantidad', {{ max(1, $cantidad - 1) }})">−</button>
                <input type="number" wire:model="cantidad" class="form-control text-center" style="min-height: 48px; font-size: 16px;" min="1" inputmode="numeric">
                <button type="button" class="btn btn-outline-secondary" style="min-height: 48px;" wire:click="$set('cantidad', {{ $cantidad + 1 }})">+</button>
            </div>
            <button wire:click="agregarItem"
                    class="btn btn-success flex-grow-1"
                    style="min-height: 48px;"
                    @disabled(! $productoSeleccionado)
                    wire:loading.attr="disabled">
                Agregar
            </button>
        </div>
        @if($precioActual)
            <div class="small text-muted mt-1">
                ${{ number_format($precioActual, 2) }}
                @if(!is_null($stockDisponible)) · stock {{ $stockDisponible }} @endif
            </div>
        @endif
    </div>

    {{-- Carrito --}}
    <div class="border-top pt-3">
        <h6 class="mb-2 text-muted text-uppercase small">Pedido</h6>
        @if(count($items) === 0)
            <p class="text-muted small">Todavía no agregaste productos.</p>
        @else
            @php $total = 0; @endphp
            <ul class="list-unstyled mb-2">
                @foreach($items as $index => $item)
                    @php $total += $item['subtotal']; @endphp
                    <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div class="me-2">
                            <div class="fw-semibold">{{ $item['name'] }}</div>
                            <small class="text-muted">${{ number_format($item['price'], 2) }}</small>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="bumpQty({{ $index }}, -1)">−</button>
                            <span class="px-2">{{ $item['quantity'] }}</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="bumpQty({{ $index }}, 1)">+</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" wire:click="quitarItem({{ $index }})"><i class="bi bi-x"></i></button>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="d-flex justify-content-between mb-2">
                <strong>Total</strong>
                <strong class="fs-5">${{ number_format($total, 2) }}</strong>
            </div>
        @endif

        <textarea wire:model="observaciones" class="form-control mb-2" rows="2" placeholder="Observaciones" style="font-size: 16px;"></textarea>

        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch" id="enviarCocina" wire:model="enviarCocina" style="min-width: 48px; min-height: 24px;">
            <label class="form-check-label ms-2" for="enviarCocina">Enviar a cocina</label>
        </div>

        <button wire:click="confirmarPedido"
                class="btn btn-primary w-100"
                style="min-height: 52px;"
                wire:loading.attr="disabled"
                @disabled(count($items) === 0 || ! $mesa)>
            <span wire:loading.remove>Confirmar pedido</span>
            <span wire:loading>Confirmando…</span>
        </button>
    </div>
</div>
