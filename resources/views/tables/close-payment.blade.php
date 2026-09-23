@extends('layouts.app')

@section('title', 'Cerrar Mesa - Procesar Pago')

@section('content')
<style>
    .payment-summary-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }

    .payment-summary-header {
        background: linear-gradient(135deg, var(--conurbania-primary), var(--conurbania-secondary));
        color: white;
        padding: 1.5rem;
        border-radius: 15px 15px 0 0;
        margin: -2rem -2rem 2rem -2rem;
    }

    .payment-item-row {
        display: flex;
        justify-content: space-between;
        padding: 1rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .payment-item-row:last-child {
        border-bottom: none;
    }

    .payment-total {
        background: linear-gradient(135deg, rgba(30, 128, 129, 0.1), rgba(34, 86, 94, 0.1));
        padding: 1.5rem;
        border-radius: 15px;
        margin-top: 1rem;
        border: 2px solid var(--conurbania-primary);
    }

    .payment-method-card {
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }

    .payment-method-card:hover {
        border-color: var(--conurbania-primary);
        box-shadow: 0 4px 15px rgba(30, 128, 129, 0.2);
    }

    .payment-method-card.active {
        border-color: var(--conurbania-primary);
        background: linear-gradient(135deg, rgba(30, 128, 129, 0.05), rgba(34, 86, 94, 0.05));
    }

    .payment-amount-input {
        font-size: 1.5rem;
        font-weight: 700;
        text-align: right;
        border: 2px solid var(--conurbania-primary);
        border-radius: 10px;
        padding: 0.75rem;
    }

    .payment-total-display {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--conurbania-primary);
        text-align: center;
        margin: 1rem 0;
    }

    .payment-remaining {
        font-size: 1.25rem;
        font-weight: 600;
        text-align: center;
        padding: 1rem;
        border-radius: 10px;
        margin: 1rem 0;
    }

    .payment-remaining.positive {
        background: #c6f6d5;
        color: #22543d;
    }

    .payment-remaining.negative {
        background: #fed7d7;
        color: #742a2a;
    }

    .payment-remaining.zero {
        background: #bee3f8;
        color: #2c5282;
    }

    .payment-method-detail {
        background: #f8fafc;
        border: 1px dashed #cbd5e0;
        border-radius: 10px;
        padding: 1rem;
        margin-top: 0.5rem;
    }

    .payment-method-detail .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 0;
    }

    .payment-method-detail img {
        max-height: 160px;
        display: block;
        margin: 0 auto 0.5rem;
        border-radius: 8px;
    }
</style>

<div class="row mb-4">
    <div class="col-12 d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-cash-coin"></i> Cerrar Mesa - Procesar Pago</h1>
            <p class="text-white mb-1">Mesa {{ $table->number }} - {{ $table->sector->name ?? 'Sin sector' }}</p>
            @if($table->currentSession && $table->currentSession->waiter)
                <p class="text-white mb-0">
                    <i class="bi bi-person-badge"></i> Mozo: {{ $table->currentSession->waiter->name }}
                </p>
            @endif
        </div>
        <a href="{{ route('tables.print-consolidated-receipt', $table) }}" target="_blank" class="btn btn-lg btn-outline-light">
            <i class="bi bi-printer"></i> Imprimir ticket primero
        </a>
    </div>
    <div class="col-12">
        <p class="text-white-50 small mb-0">Podés imprimir el recibo de la mesa antes de cargar los métodos de pago y cerrar.</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="payment-summary-card">
            <div class="payment-summary-header">
                <h3 class="mb-0"><i class="bi bi-receipt"></i> Resumen de Consumo</h3>
            </div>

            <div class="mb-3">
                <h5>Items Consolidados</h5>
                @foreach($allItems as $item)
                <div class="payment-item-row">
                    <div>
                        <strong>{{ $item['product_name'] }}</strong>
                        <div class="text-muted small">
                            {{ $item['quantity'] }} x ${{ number_format($item['unit_price'], 2) }}
                        </div>
                        @if(!empty($item['observations']))
                            <div class="text-muted small">
                                <i class="bi bi-info-circle"></i> {{ $item['observations'] }}
                            </div>
                        @endif
                    </div>
                    <div class="text-end">
                        <strong>${{ number_format($item['subtotal'], 2) }}</strong>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="payment-total">
                <div class="row mb-3">
                    <div class="col-12">
                        <label for="discount_type_id" class="form-label"><i class="bi bi-percent"></i> Tipo de Descuento</label>
                        <select class="form-select" id="discount_type_id" name="discount_type_id">
                            <option value="">Sin descuento</option>
                            @foreach($discountTypes as $discountType)
                            <option value="{{ $discountType->id }}" 
                                    data-percentage="{{ $discountType->percentage }}"
                                    data-name="{{ $discountType->name }}">
                                {{ $discountType->name }} ({{ $discountType->percentage }}%)
                            </option>
                            @endforeach
                        </select>
                        @if($discountTypes->isEmpty())
                        <small class="text-muted">No hay tipos de descuento configurados. <a href="#" onclick="alert('Contacta al administrador para configurar descuentos')">Configurar</a></small>
                        @endif
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="text-muted">Subtotal:</div>
                        <div class="fs-5"><strong id="displaySubtotal">${{ number_format($totalSubtotal, 2) }}</strong></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted">Descuento:</div>
                        <div class="fs-5"><strong id="displayDiscount" class="text-danger">${{ number_format($totalDiscount, 2) }}</strong></div>
                    </div>
                </div>
                <hr>
                <div class="payment-total-display">
                    Total a Pagar: $<span id="displayTotal">{{ number_format($totalAmount, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="payment-summary-card">
            <div class="payment-summary-header">
                <h3 class="mb-0"><i class="bi bi-credit-card"></i> Métodos de Pago</h3>
            </div>

            <form id="paymentForm" action="{{ route('tables.process-payment', $table) }}" method="POST">
                @csrf
                <div id="paymentsContainer">
                    <!-- Los métodos de pago se agregarán dinámicamente aquí -->
                </div>

                <p class="text-muted small mb-2">Podés dividir la cuenta: en partes iguales (ej. 4 personas) o que cada uno abone lo suyo y agregar el restante.</p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button type="button" class="btn btn-outline-primary" onclick="addPaymentMethod()">
                        <i class="bi bi-plus-circle"></i> Agregar Método de Pago
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="splitEqually()" title="Ej: 4 personas, cada una paga la misma parte">
                        <i class="bi bi-people"></i> Dividir en partes iguales
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="addRemainingAmount()" title="Agregar una fila con el monto restante a pagar">
                        <i class="bi bi-cash-stack"></i> Completar con el restante
                    </button>
                </div>

                <div id="paymentSummary" class="payment-total" style="display: none;">
                    <div class="text-center mb-2">
                        <strong>Total Pagado:</strong>
                        <div class="fs-4" id="totalPaid">$0.00</div>
                    </div>
                    <div id="paymentRemaining" class="payment-remaining zero">
                        Restante: ${{ number_format($totalAmount, 2) }}
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <a href="{{ route('tables.print-consolidated-receipt', $table) }}" target="_blank" class="btn btn-outline-primary">
                        <i class="bi bi-printer"></i> Imprimir ticket (antes de cerrar)
                    </a>
                    <button type="submit" class="btn btn-success btn-lg" id="submitPaymentBtn" disabled>
                        <i class="bi bi-check-circle"></i> Procesar Pago y Cerrar Mesa
                    </button>
                    <a href="{{ route('tables.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
let paymentMethods = [];
let baseSubtotal = {{ $totalSubtotal }};
let baseDiscount = {{ $totalDiscount }};
let totalAmount = {{ $totalAmount }};
let currentDiscount = {{ $totalDiscount }};
let paymentCounter = 0;

// Íconos por tipo — el resto (label, alias/CVU/QR/instrucciones) viene
// configurado desde "Configuración → Medios de cobro" (paymentMethodConfigs).
const methodIcons = {
    'EFECTIVO': 'bi-cash',
    'DEBITO': 'bi-credit-card',
    'CREDITO': 'bi-credit-card-2-front',
    'TRANSFERENCIA': 'bi-bank',
    'QR': 'bi-qr-code',
};

// Medios activos configurados por el restaurante (fallback a los 4 clásicos
// si todavía no configuró nada — ver PaymentMethodConfigurationService::active).
const paymentMethodConfigs = @json($activeMethods->map(fn ($m) => [
    'type' => $m->type,
    'label' => $m->label,
    'alias' => $m->alias,
    'cvu' => $m->cvu,
    'cbu' => $m->cbu,
    'account_holder' => $m->account_holder,
    'cuit' => $m->cuit,
    'qr_image_url' => $m->qr_image_url,
    'instructions' => $m->instructions,
]));

const paymentMethodOptions = {};
paymentMethodConfigs.forEach((cfg) => {
    paymentMethodOptions[cfg.type] = { icon: methodIcons[cfg.type] || 'bi-wallet2', label: cfg.label, color: '#1e8081' };
});
if (Object.keys(paymentMethodOptions).length === 0) {
    // Nunca debería pasar (el backend siempre manda al menos los 4 clásicos),
    // pero por las dudas no dejamos el selector vacío.
    paymentMethodOptions['EFECTIVO'] = { icon: 'bi-cash', label: 'Efectivo', color: '#28a745' };
}

function configFor(type) {
    return paymentMethodConfigs.find((c) => c.type === type) || null;
}

function copyToClipboard(text, label) {
    navigator.clipboard.writeText(text).then(() => {
        Swal.fire({ icon: 'success', title: `${label} copiado`, timer: 1200, showConfirmButton: false });
    }).catch(() => {
        Swal.fire({ icon: 'error', title: 'No se pudo copiar', text: 'Copialo manualmente.' });
    });
}

function renderMethodDetail(method) {
    const cfg = configFor(method);
    if (!cfg || (method !== 'TRANSFERENCIA' && method !== 'QR')) return '';

    if (method === 'TRANSFERENCIA') {
        if (!cfg.alias && !cfg.cvu && !cfg.cbu) return '';
        return `
            <div class="payment-method-detail">
                ${cfg.alias ? `<div class="detail-row"><span><strong>Alias:</strong> ${cfg.alias}</span><button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('${cfg.alias}', 'Alias')">Copiar</button></div>` : ''}
                ${cfg.cvu ? `<div class="detail-row"><span><strong>CVU:</strong> ${cfg.cvu}</span><button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('${cfg.cvu}', 'CVU')">Copiar</button></div>` : ''}
                ${cfg.cbu ? `<div class="detail-row"><span><strong>CBU:</strong> ${cfg.cbu}</span><button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('${cfg.cbu}', 'CBU')">Copiar</button></div>` : ''}
                ${cfg.account_holder ? `<div class="detail-row"><span><strong>Titular:</strong> ${cfg.account_holder}</span></div>` : ''}
                ${cfg.instructions ? `<div class="detail-row"><small class="text-muted">${cfg.instructions}</small></div>` : ''}
            </div>`;
    }

    if (method === 'QR') {
        if (!cfg.qr_image_url) return '<div class="payment-method-detail text-muted small">No hay un QR cargado — configuralo en Configuración → Medios de cobro.</div>';
        return `
            <div class="payment-method-detail text-center">
                <img src="${cfg.qr_image_url}" alt="QR">
                ${cfg.instructions ? `<div class="small text-muted">${cfg.instructions}</div>` : '<div class="small text-muted">Escaneá el código para realizar el pago</div>'}
            </div>`;
    }

    return '';
}

function defaultMethod() {
    return paymentMethodConfigs.find((c) => c.type === 'EFECTIVO') ? 'EFECTIVO' : (paymentMethodConfigs[0]?.type || 'EFECTIVO');
}

function addPaymentMethod() {
    paymentCounter++;
    const paymentId = `payment_${paymentCounter}`;

    paymentMethods.push({
        id: paymentId,
        method: defaultMethod(),
        amount: 0,
        operation_number: '',
        notes: ''
    });

    renderPayments();
    updatePaymentSummary();
}

function removePaymentMethod(paymentId) {
    paymentMethods = paymentMethods.filter(p => p.id !== paymentId);
    renderPayments();
    updatePaymentSummary();
}

// Dividir el total en N partes iguales (ej. 4 personas)
function splitEqually() {
    const total = totalAmount;
    if (total <= 0) return;
    Swal.fire({
        title: '¿En cuántas partes dividir?',
        input: 'number',
        inputLabel: 'Número de personas (o pagos)',
        inputMin: 2,
        inputMax: 20,
        inputValue: 2,
        showCancelButton: true,
        confirmButtonText: 'Aplicar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) return;
        const n = Math.max(2, Math.min(20, parseInt(result.value, 10) || 2));
        paymentMethods = [];
        paymentCounter = 0;
        const part = Math.floor(total * 100 / n) / 100;
        const lastPart = Math.round((total - part * (n - 1)) * 100) / 100;
        for (let i = 0; i < n; i++) {
            paymentCounter++;
            paymentMethods.push({
                id: `payment_${paymentCounter}`,
                method: defaultMethod(),
                amount: i === n - 1 ? lastPart : part,
                operation_number: '',
                notes: `Parte ${i + 1} de ${n}`
            });
        }
        renderPayments();
        updatePaymentSummary();
    });
}

// Agregar una fila con el monto restante a pagar
function addRemainingAmount() {
    const totalPaid = paymentMethods.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0);
    const remaining = Math.round((totalAmount - totalPaid) * 100) / 100;
    if (remaining <= 0) {
        Swal.fire({ icon: 'info', title: 'Cuenta cubierta', text: 'El total ya está cubierto. Si hay vuelto, podés ajustar montos.' });
        return;
    }
    paymentCounter++;
    paymentMethods.push({
        id: `payment_${paymentCounter}`,
        method: defaultMethod(),
        amount: remaining,
        operation_number: '',
        notes: 'Restante'
    });
    renderPayments();
    updatePaymentSummary();
}

function updatePaymentMethod(paymentId, field, value) {
    const payment = paymentMethods.find(p => p.id === paymentId);
    if (payment) {
        if (field === 'amount') {
            payment.amount = parseFloat(value) || 0;
        } else {
            payment[field] = value;
        }
        if (field === 'method') {
            renderPayments();
        }
        updatePaymentSummary();
    }
}

function renderPayments() {
    const container = document.getElementById('paymentsContainer');
    
    if (paymentMethods.length === 0) {
        container.innerHTML = '<p class="text-muted text-center">No hay métodos de pago agregados. Haz clic en "Agregar Método de Pago" para comenzar.</p>';
        return;
    }

    container.innerHTML = paymentMethods.map(payment => {
        const methodInfo = paymentMethodOptions[payment.method] || paymentMethodOptions['EFECTIVO'];
        return `
            <div class="payment-method-card" data-payment-id="${payment.id}">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h5><i class="bi ${methodInfo.icon}"></i> ${methodInfo.label}</h5>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePaymentMethod('${payment.id}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Método de Pago</label>
                    <select class="form-select" onchange="updatePaymentMethod('${payment.id}', 'method', this.value)">
                        ${Object.entries(paymentMethodOptions).map(([key, info]) =>
                            `<option value="${key}" ${payment.method === key ? 'selected' : ''}>${info.label}</option>`
                        ).join('')}
                    </select>
                    ${renderMethodDetail(payment.method)}
                </div>

                <div class="mb-3">
                    <label class="form-label">Monto <span class="text-danger">*</span></label>
                    <input type="number" 
                           class="form-control payment-amount-input" 
                           step="0.01" 
                           min="0.01"
                           value="${payment.amount.toFixed(2)}"
                           onchange="updatePaymentMethod('${payment.id}', 'amount', this.value)"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Número de Operación (Opcional)</label>
                    <input type="text" 
                           class="form-control" 
                           placeholder="Ej: 123456789"
                           value="${payment.operation_number}"
                           onchange="updatePaymentMethod('${payment.id}', 'operation_number', this.value)">
                    <small class="text-muted">Útil para transferencias o pagos con tarjeta</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notas (Opcional)</label>
                    <textarea class="form-control" 
                              rows="2" 
                              placeholder="Observaciones adicionales..."
                              onchange="updatePaymentMethod('${payment.id}', 'notes', this.value)">${payment.notes}</textarea>
                </div>
            </div>
        `;
    }).join('');
}

// Calcular descuento cuando se selecciona un tipo de descuento
document.getElementById('discount_type_id').addEventListener('change', function() {
    const select = this;
    const selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value === '') {
        // Sin descuento
        currentDiscount = baseDiscount;
    } else {
        // Calcular descuento basado en el porcentaje
        const percentage = parseFloat(selectedOption.dataset.percentage);
        const discountAmount = Math.round((baseSubtotal * percentage / 100) * 100) / 100;
        currentDiscount = discountAmount;
    }
    
    // Recalcular total
    totalAmount = baseSubtotal - currentDiscount;
    
    // Actualizar display
    document.getElementById('displayDiscount').textContent = '$' + currentDiscount.toFixed(2);
    document.getElementById('displayTotal').textContent = totalAmount.toFixed(2);
    
    // Actualizar el campo de restante si ya hay pagos
    if (paymentMethods.length > 0) {
        updatePaymentSummary();
    }
});

function updatePaymentSummary() {
    const totalPaid = paymentMethods.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0);
    const remaining = totalAmount - totalPaid;
    
    document.getElementById('totalPaid').textContent = `$${totalPaid.toFixed(2)}`;
    
    const remainingEl = document.getElementById('paymentRemaining');
    if (Math.abs(remaining) < 0.01) {
        remainingEl.textContent = '✓ Pago completo';
        remainingEl.className = 'payment-remaining zero';
    } else if (remaining > 0) {
        remainingEl.textContent = `Restante: $${remaining.toFixed(2)}`;
        remainingEl.className = 'payment-remaining positive';
    } else {
        remainingEl.textContent = `Vuelto: $${Math.abs(remaining).toFixed(2)}`;
        remainingEl.className = 'payment-remaining negative';
    }

    document.getElementById('paymentSummary').style.display = 'block';
    
    // Habilitar botón solo si el pago está completo o hay vuelto
    const submitBtn = document.getElementById('submitPaymentBtn');
    if (Math.abs(remaining) < 0.01 || remaining < 0) {
        submitBtn.disabled = false;
    } else {
        submitBtn.disabled = true;
    }
}

document.getElementById('paymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const totalPaid = paymentMethods.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0);
    const remaining = totalAmount - totalPaid;
    
    if (Math.abs(remaining) > 0.01 && remaining > 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Pago incompleto',
            text: `Falta pagar $${remaining.toFixed(2)}. El total pagado debe ser igual o mayor al total a pagar.`,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Entendido'
        });
        return;
    }

    if (paymentMethods.length === 0) {
        Swal.fire({
            icon: 'error',
            title: 'Sin métodos de pago',
            text: 'Debes agregar al menos un método de pago.',
            confirmButtonColor: '#c94a2d',
            confirmButtonText: 'Entendido'
        });
        return;
    }

    // Preparar datos del formulario
    const formData = new FormData(this);
    formData.delete('payments');
    
    // Agregar discount_type_id si está seleccionado
    const discountTypeId = document.getElementById('discount_type_id').value;
    if (discountTypeId) {
        formData.append('discount_type_id', discountTypeId);
    }
    
    paymentMethods.forEach((payment, index) => {
        formData.append(`payments[${index}][payment_method]`, payment.method);
        formData.append(`payments[${index}][amount]`, payment.amount);
        if (payment.operation_number) {
            formData.append(`payments[${index}][operation_number]`, payment.operation_number);
        }
        if (payment.notes) {
            formData.append(`payments[${index}][notes]`, payment.notes);
        }
    });

    // Mostrar confirmación
    Swal.fire({
        title: '¿Confirmar pago?',
        html: `
            <p>Total a pagar: <strong>$${totalAmount.toFixed(2)}</strong></p>
            <p>Total pagado: <strong>$${totalPaid.toFixed(2)}</strong></p>
            ${remaining < 0 ? `<p>Vuelto: <strong>$${Math.abs(remaining).toFixed(2)}</strong></p>` : ''}
            <p>Se procesará el pago y se cerrará la mesa.</p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1e8081',
        cancelButtonColor: '#7b7d84',
        confirmButtonText: 'Sí, procesar pago',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Procesando...',
                text: 'Por favor espera mientras se procesa el pago',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Enviar formulario
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async response => {
                // Verificar si la respuesta es una redirección
                if (response.redirected) {
                    window.location.href = response.url;
                    return;
                }

                // Verificar el content-type
                const contentType = response.headers.get('content-type');
                
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    
                    if (data.success) {
                        // Si hay una URL de redirección, usarla
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            window.location.reload();
                        }
                    } else {
                        // Mostrar errores de validación
                        let errorMessage = data.message || 'Ocurrió un error al procesar el pago';
                        
                        if (data.errors) {
                            const errorList = Object.values(data.errors).flat().join('<br>');
                            errorMessage = errorList;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            html: errorMessage,
                            confirmButtonColor: '#c94a2d'
                        });
                    }
                } else {
                    // Si no es JSON, probablemente es HTML (página de error)
                    const text = await response.text();
                    
                    // Intentar extraer mensajes de error del HTML si es posible
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: 'Ocurrió un error al procesar el pago. Por favor, verifica los datos e intenta nuevamente.',
                        confirmButtonColor: '#c94a2d'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo conectar con el servidor. Por favor, verifica tu conexión e intenta nuevamente.',
                    confirmButtonColor: '#c94a2d'
                });
            });
        }
    });
});

// Inicializar con un método de pago por defecto
document.addEventListener('DOMContentLoaded', function() {
    addPaymentMethod();
});
</script>
@endpush
@endsection

