@foreach($tablesBySector as $sectorName => $sectorTables)
    <div class="mb-3">
        <div class="small fw-semibold text-muted mb-2">{{ $sectorName }}</div>
        <div class="tables-grid">
            @foreach($sectorTables as $table)
                <button
                    type="button"
                    class="table-pick-card {{ (int) ($selectedId ?? 0) === (int) $table->id ? 'is-selected' : '' }}"
                    data-pick-table
                    data-table-id="{{ $table->id }}"
                    data-table-number="{{ $table->number }}"
                    data-table-status="{{ $table->status }}"
                    data-sector-name="{{ $sectorName }}"
                >
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                        <span class="table-pick-number">{{ $table->number }}</span>
                        <span class="table-status-pill table-status-{{ strtolower($table->status) }}">{{ $table->status }}</span>
                    </div>
                    <div class="small text-muted">Cap. {{ $table->capacity }}</div>
                </button>
            @endforeach
        </div>
    </div>
@endforeach
