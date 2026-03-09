<div class="d-flex flex-column gap-3">
    <div class="card bg-dark border-0">
        <div class="card-body">
            <h6 class="mb-3">Mesa</h6>
            <div class="row g-2">
                <div class="col-12">
                    <select wire:model="mesa" class="form-select form-select-lg" style="min-height: 48px;">
                        <option value="">Seleccioná una mesa</option>
                        @foreach($mesas as $mesaModel)
                            <option value="{{ $mesaModel->id }}">Mesa {{ $mesaModel->number }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card bg-dark border-0">
        <div class="card-body">
            <h6 class="mb-3">Productos</h6>
            <div class="mb-2">
                <input type="text"
                       wire:model.live="busqueda"
                       class="form-control form-control-lg"
                       placeholder="Buscar producto..."
                       autocomplete="off"
                       style="font-size: 16px; min-height: 48px;">
            </div>
            <div class="mb-2">
                <select wire:model="productoSeleccionado" class="form-select form-select-lg" style="min-height: 48px;">
                    <option value="">Elegí un producto</option>
                    @foreach($productos as $producto)
                        <option value="{{ $producto->id }}">{{ $producto->name }} — ${{ number_format($producto->price, 2) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <input type="number"
                       wire:model="cantidad"
                       class="form-control form-control-lg"
                       style="max-width: 120px; min-height: 48px; font-size: 16px;"
                       min="1"
                       inputmode="numeric">
                <button wire:click="agregarItem"
                        class="btn btn-success flex-grow-1"
                        style="min-height: 48px;"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove>Agregar</span>
                    <span wire:loading>Agregando...</span>
                </button>
            </div>
            @if($precioActual)
                <div class="small text-muted">
                    Precio: ${{ number_format($precioActual, 2) }}
                    @if(!is_null($stockDisponible))
                        · Stock: {{ $stockDisponible }}
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="card bg-dark border-0">
        <div class="card-body">
            <h6 class="mb-3">Items del pedido</h6>
            @if(count($items) === 0)
                <p class="text-muted small mb-0">Todavía no agregaste productos.</p>
            @else
                <ul class="list-group list-group-flush mb-3">
                    @php $total = 0; @endphp
                    @foreach($items as $index => $item)
                        @php $total += $item['subtotal']; @endphp
                        <li class="list-group-item bg-dark text-white d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">{{ $item['name'] }}</div>
                                <small class="text-muted">x{{ $item['quantity'] }} · ${{ number_format($item['price'], 2) }}</small>
                            </div>
                            <div class="text-end">
                                <div class="mb-1">${{ number_format($item['subtotal'], 2) }}</div>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        style="min-height: 32px;"
                                        wire:click="quitarItem({{ $index }})">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold">Total</span>
                    <span class="fw-bold fs-5">${{ number_format($total, 2) }}</span>
                </div>
            @endif

            <div class="mb-2">
                <textarea wire:model="observaciones"
                          class="form-control"
                          rows="2"
                          placeholder="Observaciones (opcional)"
                          style="font-size: 16px;"></textarea>
            </div>

            <button wire:click="confirmarPedido"
                    class="btn btn-primary w-100"
                    style="min-height: 52px;"
                    wire:loading.attr="disabled">
                <span wire:loading.remove>Confirmar pedido</span>
                <span wire:loading>Confirmando...</span>
            </button>
        </div>
    </div>
</div>

