{{-- Selector de mesa para orders/create: chip fijo o mapa visual --}}
@php
    $selectedTable = $selectedTable ?? null;
    $tablesBySector = collect($tables ?? [])->groupBy(fn ($t) => $t->sector->name ?? 'Sin sector');
@endphp

<div class="mb-3 order-table-picker" data-order-table-picker>
    <label class="form-label">Mesa</label>

    @if($selectedTable)
        <div data-table-chip>
            <div class="order-table-chip mb-2">
                <span class="table-status-pill table-status-{{ strtolower($selectedTable->status) }}">{{ $selectedTable->status }}</span>
                <strong>Mesa {{ $selectedTable->number }}</strong>
                @if($selectedTable->sector)
                    <span class="text-muted">— {{ $selectedTable->sector->name }}</span>
                @endif
            </div>
            <button type="button" class="btn btn-link btn-sm p-0" data-change-table>
                Cambiar mesa
            </button>
            <input type="hidden" name="table_id" id="table_id" value="{{ $selectedTable->id }}" required>
        </div>
        <div class="d-none" data-table-map>
            <p class="small text-muted mb-2">Elegí una mesa ocupada:</p>
            @include('orders.partials.table-map-grid', ['tablesBySector' => $tablesBySector, 'selectedId' => $selectedTable->id])
            <input type="hidden" id="table_id_map" value="{{ $selectedTable->id }}" disabled>
        </div>
    @else
        <p class="small text-muted mb-2">Elegí una mesa ocupada del mapa:</p>
        @include('orders.partials.table-map-grid', ['tablesBySector' => $tablesBySector, 'selectedId' => null])
        <input type="hidden" name="table_id" id="table_id" value="" required>
        @if(($tables ?? collect())->isEmpty())
            <p class="text-warning small mb-0 mt-2">
                No hay mesas ocupadas. Marcá una mesa como ocupada desde
                <a href="{{ route('tables.index') }}">Mesas</a>.
            </p>
        @endif
    @endif
</div>
