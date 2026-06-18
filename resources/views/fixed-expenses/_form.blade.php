@php
    $fixedExpense = $fixedExpense ?? null;
    $selectedType = old('type', $fixedExpense?->type ?? 'INGRESO');
    $expenseCategories = \App\Models\FixedExpense::categoriesForType(\App\Models\FixedExpense::TYPE_GASTO);
    $incomeCategories = \App\Models\FixedExpense::categoriesForType(\App\Models\FixedExpense::TYPE_INGRESO);
    $frequencyLabels = \App\Models\FixedExpense::frequencyLabels();
@endphp

<div class="mb-3">
    <label for="name" class="form-label">Nombre *</label>
    <input type="text" class="form-control @error('name') is-invalid @enderror"
           id="name" name="name" value="{{ old('name', $fixedExpense?->name) }}" required
           placeholder="Ej: Canon local, Alquiler, Taller gastronómico">
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="description" class="form-label">Descripción / referencia</label>
    <textarea class="form-control @error('description') is-invalid @enderror"
              id="description" name="description" rows="2"
              placeholder="Detalle para trazabilidad: contrato, responsable, cuenta, etc.">{{ old('description', $fixedExpense?->description) }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="type" class="form-label">Tipo *</label>
            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                <option value="INGRESO" {{ $selectedType === 'INGRESO' ? 'selected' : '' }}>Ingreso fijo</option>
                <option value="GASTO" {{ $selectedType === 'GASTO' ? 'selected' : '' }}>Gasto fijo</option>
            </select>
            @error('type')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="category" class="form-label">Categoría *</label>
            <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
                <optgroup label="Ingresos" id="category-group-ingreso">
                    @foreach($incomeCategories as $key => $label)
                        <option value="{{ $key }}" data-type="INGRESO"
                            {{ old('category', $fixedExpense?->category) == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </optgroup>
                <optgroup label="Gastos" id="category-group-gasto">
                    @foreach($expenseCategories as $key => $label)
                        <option value="{{ $key }}" data-type="GASTO"
                            {{ old('category', $fixedExpense?->category) == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </optgroup>
            </select>
            @error('category')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="mb-3">
            <label for="amount" class="form-label">Monto por ocurrencia *</label>
            <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" step="0.01" class="form-control @error('amount') is-invalid @enderror"
                       id="amount" name="amount" value="{{ old('amount', $fixedExpense?->amount) }}" required min="0">
            </div>
            <small class="text-muted">Según la frecuencia elegida</small>
            @error('amount')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="frequency" class="form-label">Frecuencia *</label>
            <select class="form-select @error('frequency') is-invalid @enderror" id="frequency" name="frequency" required>
                @foreach($frequencyLabels as $key => $label)
                    <option value="{{ $key }}"
                        {{ old('frequency', $fixedExpense?->frequency ?? 'MENSUAL') == $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('frequency')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label for="due_day" class="form-label">Día de cobro / pago</label>
            <input type="number" min="1" max="31" class="form-control @error('due_day') is-invalid @enderror"
                   id="due_day" name="due_day" value="{{ old('due_day', $fixedExpense?->due_day) }}"
                   placeholder="Ej: 5">
            <small class="text-muted">Opcional. Día del mes en que se espera el movimiento</small>
            @error('due_day')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="alert alert-light border mb-3" id="monthly-preview">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <strong><i class="bi bi-calculator"></i> Equivalente mensual estimado:</strong>
            <span class="fs-5 ms-2" id="monthly-equivalent-amount">$0,00</span>
        </div>
        <small class="text-muted" id="monthly-preview-detail">Cargá monto y frecuencia para ver la proyección</small>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label for="start_date" class="form-label">Vigencia desde *</label>
            <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                   id="start_date" name="start_date"
                   value="{{ old('start_date', $fixedExpense?->start_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required>
            @error('start_date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label for="end_date" class="form-label">Vigencia hasta</label>
            <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                   id="end_date" name="end_date"
                   value="{{ old('end_date', $fixedExpense?->end_date?->format('Y-m-d')) }}">
            <small class="text-muted">Vacío = vigencia indefinida</small>
            @error('end_date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="mb-3 form-check">
    <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
           {{ old('is_active', $fixedExpense?->is_active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_active">Activo (incluir en proyecciones)</label>
</div>

@push('scripts')
<script>
(function () {
    const typeSelect = document.getElementById('type');
    const categorySelect = document.getElementById('category');
    const amountInput = document.getElementById('amount');
    const frequencySelect = document.getElementById('frequency');
    const previewAmount = document.getElementById('monthly-equivalent-amount');
    const previewDetail = document.getElementById('monthly-preview-detail');

    const multipliers = {
        MENSUAL: 1,
        QUINCENAL: 2,
        SEMANAL: 52 / 12,
        DIARIO: 30,
        ANUAL: 1 / 12,
    };

    const frequencyLabels = @json($frequencyLabels);

    function filterCategories() {
        const type = typeSelect.value;
        Array.from(categorySelect.options).forEach(function (opt) {
            if (!opt.dataset.type) return;
            const visible = opt.dataset.type === type;
            opt.hidden = !visible;
            opt.disabled = !visible;
        });

        const selected = categorySelect.selectedOptions[0];
        if (!selected || selected.dataset.type !== type) {
            const first = Array.from(categorySelect.options).find(function (o) {
                return o.dataset.type === type && !o.disabled;
            });
            if (first) categorySelect.value = first.value;
        }
    }

    function updateMonthlyPreview() {
        const amount = parseFloat(amountInput.value) || 0;
        const frequency = frequencySelect.value;
        const multiplier = multipliers[frequency] || 1;
        const monthly = amount * multiplier;
        const typeLabel = typeSelect.value === 'INGRESO' ? 'ingreso' : 'gasto';

        previewAmount.textContent = '$' + monthly.toLocaleString('es-AR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
        previewDetail.textContent = typeLabel.charAt(0).toUpperCase() + typeLabel.slice(1)
            + ' fijo de referencia mensual (' + (frequencyLabels[frequency] || frequency).toLowerCase() + ')';
        previewAmount.className = 'fs-5 ms-2 text-' + (typeSelect.value === 'INGRESO' ? 'success' : 'danger');
    }

    typeSelect.addEventListener('change', function () {
        filterCategories();
        updateMonthlyPreview();
    });
    amountInput.addEventListener('input', updateMonthlyPreview);
    frequencySelect.addEventListener('change', updateMonthlyPreview);

    filterCategories();
    updateMonthlyPreview();
})();
</script>
@endpush
