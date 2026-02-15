@extends('layouts.app')

@section('title', 'Cerrar Cuenta - Pedido Rápido')

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
</style>

<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('orders.quick.show', $order) }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver al Pedido
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
            <i class="bi bi-cash-coin"></i> Cerrar Cuenta - Pedido Rápido
        </h1>
        <p class="text-muted">
            Pedido: <strong>{{ $order->number }}</strong> | 
            Cliente: <strong>{{ $order->customer_name }}</strong> |
            Sesión de Caja: <strong>{{ $activeSession->cashRegister->name }}</strong>
        </p>
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="payment-summary-card">
            <div class="payment-summary-header">
                <h3 class="mb-0"><i class="bi bi-receipt"></i> Resumen de Consumo</h3>
            </div>

            <div class="mb-3">
                <h5>Items del Pedido</h5>
                @foreach($groupedItems as $item)
                <div class="payment-item-row">
                    <div>
                        <strong>{{ $item['product']->name }}</strong>
                        <div class="text-muted small">
                            {{ $item['quantity'] }} x ${{ number_format($item['unit_price'], 2) }}
                        </div>
                        @if(!empty($item['observations']))
                            <div class="text-muted small">
                                <i class="bi bi-info-circle"></i> {{ $item['observations'] }}
                            </div>
                        @endif
                        @if(isset($item['modifiers']) && $item['modifiers']->count() > 0)
                            <div class="text-info small">
                                @foreach($item['modifiers'] as $modifier)
                                    + {{ $modifier->name }}
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="text-end">
                        <strong>${{ number_format($item->subtotal, 2) }}</strong>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="payment-total">
                <div class="row">
                    <div class="col-6">
                        <div class="text-muted">Subtotal:</div>
                        <div class="fs-5"><strong>${{ number_format($order->subtotal, 2) }}</strong></div>
                    </div>
                    @if($order->discount > 0)
                    <div class="col-6">
                        <div class="text-muted">Descuento:</div>
                        <div class="fs-5"><strong>${{ number_format($order->discount, 2) }}</strong></div>
                    </div>
                    @endif
                </div>
                <hr>
                <div class="payment-total-display">
                    Total a Pagar: ${{ number_format($order->total, 2) }}
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="payment-summary-card">
            <div class="payment-summary-header">
                <h3 class="mb-0"><i class="bi bi-credit-card"></i> Métodos de Pago</h3>
            </div>

            <form id="paymentForm" action="{{ route('orders.quick.process-payment', $order) }}" method="POST">
                @csrf
                <div id="paymentsContainer">
                    <!-- Los métodos de pago se agregarán dinámicamente aquí -->
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <button type="button" class="btn btn-outline-primary" onclick="addPaymentMethod()">
                        <i class="bi bi-plus-circle"></i> Agregar Método de Pago
                    </button>
                </div>

                <div id="paymentSummary" class="payment-total" style="display: none;">
                    <div class="text-center mb-2">
                        <strong>Total Pagado:</strong>
                        <div class="fs-4" id="totalPaid">$0.00</div>
                    </div>
                    <div id="paymentRemaining" class="payment-remaining zero">
                        Restante: ${{ number_format($order->total, 2) }}
                    </div>
                </div>

                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-success btn-lg" id="submitPaymentBtn" disabled>
                        <i class="bi bi-check-circle"></i> Procesar Pago y Cerrar Pedido
                    </button>
                    <a href="{{ route('orders.quick.show', $order) }}" class="btn btn-outline-secondary">
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
let totalAmount = {{ $order->total }};
let paymentCounter = 0;

const paymentMethodOptions = {
    'EFECTIVO': { icon: 'bi-cash', label: 'Efectivo', color: '#28a745' },
    'DEBITO': { icon: 'bi-credit-card', label: 'Tarjeta Débito', color: '#007bff' },
    'CREDITO': { icon: 'bi-credit-card-2-front', label: 'Tarjeta Crédito', color: '#6f42c1' },
    'TRANSFERENCIA': { icon: 'bi-bank', label: 'Transferencia', color: '#17a2b8' },
    'QR': { icon: 'bi-qr-code', label: 'QR', color: '#fd7e14' },
    'MIXTO': { icon: 'bi-wallet2', label: 'Mixto', color: '#6c757d' }
};

function addPaymentMethod() {
    paymentCounter++;
    const paymentId = `payment_${paymentCounter}`;
    
    paymentMethods.push({
        id: paymentId,
        method: 'EFECTIVO',
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

function updatePaymentMethod(paymentId, field, value) {
    const payment = paymentMethods.find(p => p.id === paymentId);
    if (payment) {
        if (field === 'amount') {
            payment.amount = parseFloat(value) || 0;
        } else {
            payment[field] = value;
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
                </div>

                <div class="mb-3">
                    <label class="form-label">Monto <span class="text-danger">*</span></label>
                    <input type="number" 
                           class="form-control payment-amount-input" 
                           step="0.01" 
                           min="0.01"
                           value="${payment.amount.toFixed(2)}"
                           oninput="updatePaymentMethod('${payment.id}', 'amount', this.value)"
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

document.getElementById('paymentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const totalPaid = paymentMethods.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0);
    const remaining = totalAmount - totalPaid;
    
    if (remaining > 0.01) {
        Swal.fire({
            icon: 'warning',
            title: 'Pago incompleto',
            text: `Faltan $${remaining.toFixed(2)} para completar el pago.`,
            confirmButtonColor: '#ffc107'
        });
        return;
    }
    
    // Confirmar
    const confirmResult = await Swal.fire({
        title: '¿Confirmar pago?',
        html: `
            <p>Total: <strong>$${totalAmount.toFixed(2)}</strong></p>
            <p>Total pagado: <strong>$${totalPaid.toFixed(2)}</strong></p>
            ${remaining < -0.01 ? `<p>Vuelto: <strong>$${Math.abs(remaining).toFixed(2)}</strong></p>` : ''}
            <p>Se procesará el pago y se cerrará el pedido.</p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1e8081',
        cancelButtonColor: '#7b7d84',
        confirmButtonText: 'Sí, procesar',
        cancelButtonText: 'Cancelar'
    });
    
    if (!confirmResult.isConfirmed) {
        return;
    }
    
    // Mostrar loading
    Swal.fire({
        title: 'Procesando pago...',
        text: 'Por favor espera',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Preparar datos
    const payments = paymentMethods.map((payment, index) => {
        const paymentData = {
            payment_method: payment.method,
            amount: payment.amount
        };
        if (payment.operation_number) {
            paymentData.operation_number = payment.operation_number;
        }
        if (payment.notes) {
            paymentData.notes = payment.notes;
        }
        return paymentData;
    });
    
    try {
        const response = await fetch('{{ route("orders.quick.process-payment", $order) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ payments: payments })
        });
        
        // Verificar si la respuesta es JSON
        const contentType = response.headers.get('content-type');
        let data;
        
        if (contentType && contentType.includes('application/json')) {
            data = await response.json();
        } else {
            // Si no es JSON, intentar parsear el texto
            const text = await response.text();
            try {
                data = JSON.parse(text);
            } catch (e) {
                // Si no se puede parsear, es probable que sea HTML de error
                throw new Error('El servidor devolvió una respuesta no válida. Por favor, verifica los datos e intenta nuevamente.');
            }
        }
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Pago procesado!',
                text: data.message,
                confirmButtonColor: '#1e8081'
            }).then(() => {
                // Abrir ticket si hay URL
                if (data.print_url) {
                    window.open(data.print_url, '_blank');
                }
                // Redirigir a la vista del pedido
                window.location.href = '{{ route("orders.quick.show", $order) }}';
            });
        } else {
            // Manejar errores de validación
            let errorMessage = data.message || 'Error al procesar el pago';
            if (data.errors) {
                const errorMessages = Object.values(data.errors).flat();
                errorMessage = errorMessages.join('<br>');
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: errorMessage,
                confirmButtonColor: '#dc3545'
            });
        }
    } catch (error) {
        console.error('Error al procesar pago:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error de conexión. Por favor intenta nuevamente.',
            confirmButtonColor: '#dc3545'
        });
    }
});
</script>
@endpush
@endsection

