@extends('layouts.app')

@section('title', 'Puesta a punto')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-flag"></i> Puesta a punto</h1>
        <p class="text-white-50 mb-0">Punto de partida: frena todo lo que está en curso y deja el stock en lo que realmente hay.</p>
    </div>
</div>

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<form action="{{ route('stock.reset.store') }}" method="POST" id="stockResetForm">
    @csrf
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> Conteo real de stock</h5>
                    <input type="search" id="resetSearch" class="form-control form-control-sm" style="max-width: 220px;" placeholder="Buscar..." aria-label="Buscar producto">
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        Cargá lo que contaste. Cada campo arranca con el stock que figura hoy en el sistema;
                        lo que dejes vacío no se modifica.
                    </p>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-end">En sistema</th>
                                    <th style="width: 160px;">Stock real</th>
                                    <th class="text-end">Diferencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($preview['products'] as $product)
                                    <tr class="reset-row" data-name="{{ strtolower($product['name']) }}">
                                        <td>
                                            {{ $product['name'] }}
                                            <span class="badge {{ $product['type'] === 'INSUMO' ? 'bg-secondary' : 'bg-primary' }}">{{ $product['type'] === 'INSUMO' ? 'Insumo' : 'Producto' }}</span>
                                        </td>
                                        <td class="text-end">{{ $product['current_stock'] }} {{ $product['unit'] }}</td>
                                        <td>
                                            <input type="number" min="0" step="1" inputmode="numeric"
                                                   name="counts[{{ $product['id'] }}]"
                                                   class="form-control form-control-sm reset-count"
                                                   data-current="{{ $product['current_stock'] }}"
                                                   value="{{ old('counts.'.$product['id'], $product['current_stock']) }}"
                                                   aria-label="Stock real de {{ $product['name'] }}">
                                        </td>
                                        <td class="text-end reset-diff">0</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted">No hay productos ni insumos con control de stock.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-danger mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-exclamation-octagon"></i> Qué se va a bajar</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-3">
                        <li class="d-flex justify-content-between"><span>Pedidos en curso</span><strong>{{ $preview['active_orders'] }}</strong></li>
                        <li class="d-flex justify-content-between"><span>Total sin cobrar</span><strong>${{ number_format($preview['active_orders_total_cents'] / 100, 2, ',', '.') }}</strong></li>
                        <li class="d-flex justify-content-between"><span>Sesiones de mesa abiertas</span><strong>{{ $preview['open_sessions'] }}</strong></li>
                        <li class="d-flex justify-content-between"><span>Mesas ocupadas</span><strong>{{ $preview['busy_tables'] }}</strong></li>
                        <li class="d-flex justify-content-between"><span>Stocks a revisar</span><strong>{{ $preview['products']->count() }}</strong></li>
                    </ul>
                    <p class="small text-muted">
                        Los pedidos en curso se anulan (no se pueden recuperar), las mesas quedan libres y cocina queda vacía.
                        La caja no se toca: los pagos ya cobrados quedan registrados y el arqueo se hace aparte.
                    </p>

                    <div class="mb-2">
                        <label for="reset-notes" class="form-label small">Motivo (opcional)</label>
                        <input type="text" id="reset-notes" name="notes" maxlength="500" class="form-control form-control-sm" value="{{ old('notes') }}" placeholder="Ej. apertura de temporada">
                    </div>
                    <div class="mb-3">
                        <label for="reset-confirmation" class="form-label small">Para confirmar, escribí <strong>{{ $confirmation }}</strong></label>
                        <input type="text" id="reset-confirmation" name="confirmation" autocomplete="off"
                               class="form-control @error('confirmation') is-invalid @enderror" data-expected="{{ $confirmation }}">
                        @error('confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-danger w-100" id="resetSubmit" disabled>
                        <i class="bi bi-flag-fill"></i> Hacer puesta a punto
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0"><i class="bi bi-clock-history"></i> Últimas puestas a punto</h5></div>
                <div class="card-body">
                    @forelse($history as $reset)
                        <div class="border-bottom pb-2 mb-2 small">
                            <div class="d-flex justify-content-between">
                                <strong>#{{ $reset->id }}</strong>
                                <span class="text-muted">{{ $reset->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <div>{{ $reset->user?->name ?? '—' }}</div>
                            <div class="text-muted">
                                {{ $reset->orders_cancelled }} pedidos · {{ $reset->tables_freed }} mesas · {{ $reset->products_adjusted }} ajustes
                            </div>
                            @if($reset->notes)<div class="fst-italic">{{ $reset->notes }}</div>@endif
                        </div>
                    @empty
                        <p class="text-muted small mb-0">Todavía no se hizo ninguna.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
(function () {
    const confirmation = document.getElementById('reset-confirmation');
    const submit = document.getElementById('resetSubmit');
    const form = document.getElementById('stockResetForm');

    confirmation.addEventListener('input', () => {
        submit.disabled = confirmation.value.trim().toUpperCase() !== confirmation.dataset.expected;
    });
    form.addEventListener('submit', () => {
        confirmation.value = confirmation.value.trim().toUpperCase();
        submit.disabled = true;
    });

    function paintDiff(input) {
        const cell = input.closest('tr').querySelector('.reset-diff');
        if (input.value === '') {
            cell.textContent = 'sin cambios';
            cell.className = 'text-end reset-diff text-muted';
            return;
        }
        const diff = parseInt(input.value, 10) - parseInt(input.dataset.current, 10);
        cell.textContent = diff > 0 ? `+${diff}` : String(diff);
        cell.className = 'text-end reset-diff ' + (diff === 0 ? 'text-muted' : diff > 0 ? 'text-success fw-semibold' : 'text-danger fw-semibold');
    }
    document.querySelectorAll('.reset-count').forEach((input) => {
        input.addEventListener('input', () => paintDiff(input));
        paintDiff(input);
    });

    document.getElementById('resetSearch').addEventListener('input', function () {
        const term = this.value.toLowerCase().trim();
        document.querySelectorAll('.reset-row').forEach((row) => {
            row.style.display = !term || row.dataset.name.includes(term) ? '' : 'none';
        });
    });
})();
</script>
@endpush
@endsection
