@extends('layouts.app')

@section('title', 'Sesión de Caja')

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('cash-register.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-cash-coin"></i> Sesión de Caja: {{ $session->cashRegister->name }}</h1>
        <p class="text-muted">
            Abierta por {{ $session->user->name }} - 
            {{ $session->opened_at->format('d/m/Y H:i') }}
        </p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h6>Monto Inicial</h6>
                <h3>${{ number_format($session->initial_amount, 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h6>Ventas</h6>
                <h3>${{ number_format($totalPayments, 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h6>Monto Esperado</h6>
                <h3>${{ number_format($expectedAmount, 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h6>Estado</h6>
                <h3>{{ $session->status }}</h3>
            </div>
        </div>
    </div>
</div>

@if($session->status === 'ABIERTA')
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Cerrar Sesión</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('cash-register.close-session', $session) }}" method="POST" id="closeSessionForm">
                    @csrf
                    <div class="mb-3">
                        <label for="final_amount" class="form-label">Monto Final en Caja</label>
                        <input type="number" step="0.01" class="form-control" id="final_amount" name="final_amount" required min="0" value="{{ number_format($expectedAmount, 2, '.', '') }}">
                        <small class="text-muted">Monto esperado: <strong>${{ number_format($expectedAmount, 2) }}</strong></small>
                        <div id="differenceAlert" class="mt-2" style="display: none;"></div>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notas (opcional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Observaciones sobre el cierre..."></textarea>
                    </div>
                    <button type="button" class="btn btn-danger w-100" onclick="confirmCloseSession()">
                        <i class="bi bi-lock"></i> Cerrar Sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Pagos</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Mesa</th>
                                <th>Mozo</th>
                                <th>Método</th>
                                <th>Monto</th>
                                <th>Fecha</th>
                                <th>Notas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($session->payments as $payment)
                            <tr>
                                <td>
                                    @if($payment->order)
                                        {{ $payment->order->number }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($payment->order && $payment->order->table)
                                        Mesa {{ $payment->order->table->number }}
                                    @elseif($payment->notes && str_contains($payment->notes, 'Mesa:'))
                                        @php
                                            preg_match('/Mesa:\s*(\d+)/', $payment->notes, $matches);
                                            echo isset($matches[1]) ? 'Mesa ' . $matches[1] : 'N/A';
                                        @endphp
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($payment->order && $payment->order->user)
                                        {{ $payment->order->user->name }}
                                    @elseif($payment->notes && str_contains($payment->notes, 'Mozo:'))
                                        @php
                                            preg_match('/Mozo:\s*([^|]+)/', $payment->notes, $matches);
                                            echo isset($matches[1]) ? trim($matches[1]) : 'N/A';
                                        @endphp
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $payment->payment_method === 'EFECTIVO' ? 'success' : ($payment->payment_method === 'DEBITO' ? 'primary' : ($payment->payment_method === 'CREDITO' ? 'info' : 'secondary')) }}">
                                        {{ $payment->payment_method }}
                                    </span>
                                </td>
                                <td><strong>${{ number_format($payment->amount, 2) }}</strong></td>
                                <td>{{ $payment->created_at->format('d/m H:i') }}</td>
                                <td>
                                    @if($payment->notes && !str_contains($payment->notes, 'Mesa:') && !str_contains($payment->notes, 'Mozo:'))
                                        <small class="text-muted">{{ \Illuminate\Support\Str::limit($payment->notes, 30) }}</small>
                                    @elseif($payment->operation_number)
                                        <small class="text-muted">Op: {{ $payment->operation_number }}</small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    No hay pagos registrados en esta sesión
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($session->payments->count() > 0)
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Total:</th>
                                <th>${{ number_format($session->payments->sum('amount'), 2) }}</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">Movimientos</h5>
                @if($session->status === 'ABIERTA')
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#movementModal">
                    <i class="bi bi-plus-lg"></i> Registrar salida / ingreso
                </button>
                @endif
            </div>
            <div class="card-body">
                @if($session->cashMovements->count() > 0)
                <p class="small text-muted mb-2">
                    <strong>Ingresos:</strong> ${{ number_format($totalIngresos, 2) }} —
                    <strong>Egresos:</strong> ${{ number_format($totalEgresos, 2) }}
                </p>
                @endif
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Descripción</th>
                                <th>Monto</th>
                                <th>Fecha</th>
                                @if(auth()->user()->role === 'ADMIN' && $session->status === 'ABIERTA')
                                <th>Acciones</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($session->cashMovements as $movement)
                            <tr>
                                <td>
                                    <span class="badge bg-{{ $movement->type === 'INGRESO' ? 'success' : 'danger' }}">
                                        {{ $movement->type }}
                                    </span>
                                </td>
                                <td>{{ $movement->description }}</td>
                                <td>${{ number_format($movement->amount, 2) }}</td>
                                <td>{{ $movement->created_at->format('H:i') }}</td>
                                @if(auth()->user()->role === 'ADMIN' && $session->status === 'ABIERTA')
                                <td>
                                    <form action="{{ route('cash-register.destroy-movement', $movement) }}" 
                                          method="POST" 
                                          class="d-inline" 
                                          id="deleteMovementForm{{ $movement->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmDeleteMovement({{ $movement->id }}, '{{ addslashes($movement->description) }}')"
                                                title="Eliminar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@if($session->status === 'ABIERTA')
<!-- Modal Registrar movimiento de caja (salida/ingreso) -->
<div class="modal fade" id="movementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('cash-register.session.store-movement', $session) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cash-stack"></i> Registrar movimiento de caja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Usá <strong>Salida (egreso)</strong> para pagos a empleados, gastos o retiros. <strong>Ingreso</strong> para entradas de efectivo (ej. cambio, reintegro).</p>
                    <div class="mb-3">
                        <label for="movement_type" class="form-label">Tipo <span class="text-danger">*</span></label>
                        <select class="form-select" id="movement_type" name="type" required>
                            <option value="EGRESO" {{ old('type') === 'INGRESO' ? '' : 'selected' }}>Salida (egreso)</option>
                            <option value="INGRESO" {{ old('type') === 'INGRESO' ? 'selected' : '' }}>Ingreso</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="movement_amount" class="form-label">Monto ($) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="movement_amount" name="amount" value="{{ old('amount') }}" placeholder="0.00" required>
                        @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="movement_description" class="form-label">Descripción <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="movement_description" name="description" value="{{ old('description') }}" placeholder="Ej: Pago empleados fin de jornada" maxlength="500" required>
                        @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="movement_reference" class="form-label">Referencia (opcional)</label>
                        <input type="text" class="form-control" id="movement_reference" name="reference" value="{{ old('reference') }}" placeholder="Ej: Recibo Nº 001" maxlength="255">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
// Calcular diferencia al cambiar monto final
document.getElementById('final_amount').addEventListener('input', function() {
    const finalAmount = parseFloat(this.value) || 0;
    const expectedAmount = {{ $expectedAmount }};
    const difference = finalAmount - expectedAmount;
    const alertDiv = document.getElementById('differenceAlert');
    
    if (Math.abs(difference) > 0.01) {
        alertDiv.style.display = 'block';
        if (difference > 0) {
            alertDiv.className = 'alert alert-warning mt-2';
            alertDiv.innerHTML = `<i class="bi bi-arrow-up-circle"></i> Sobrante: $${Math.abs(difference).toFixed(2)}`;
        } else {
            alertDiv.className = 'alert alert-danger mt-2';
            alertDiv.innerHTML = `<i class="bi bi-arrow-down-circle"></i> Faltante: $${Math.abs(difference).toFixed(2)}`;
        }
    } else {
        alertDiv.style.display = 'none';
    }
});

function confirmCloseSession() {
    const finalAmount = parseFloat(document.getElementById('final_amount').value) || 0;
    const expectedAmount = {{ $expectedAmount }};
    const difference = finalAmount - expectedAmount;
    const notes = document.getElementById('notes').value;
    
    let message = `<p>¿Estás seguro de cerrar esta sesión?</p>`;
    message += `<p><strong>Monto esperado:</strong> $${expectedAmount.toFixed(2)}</p>`;
    message += `<p><strong>Monto final:</strong> $${finalAmount.toFixed(2)}</p>`;
    
    if (Math.abs(difference) > 0.01) {
        if (difference > 0) {
            message += `<p class="text-warning"><strong>Sobrante:</strong> $${difference.toFixed(2)}</p>`;
        } else {
            message += `<p class="text-danger"><strong>Faltante:</strong> $${Math.abs(difference).toFixed(2)}</p>`;
        }
    } else {
        message += `<p class="text-success"><strong>✓ Cuadra perfecto</strong></p>`;
    }
    
    Swal.fire({
        icon: 'question',
        title: 'Cerrar Sesión',
        html: message,
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-lock"></i> Sí, cerrar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('closeSessionForm').submit();
        }
    });
}

function confirmDeleteMovement(movementId, description) {
    Swal.fire({
        icon: 'warning',
        title: '¿Eliminar Movimiento?',
        html: `
            <p>¿Estás seguro de eliminar el movimiento:</p>
            <p><strong>${description}</strong>?</p>
            <div class="alert alert-warning mt-3">
                <small><i class="bi bi-info-circle"></i> Solo se pueden eliminar movimientos de sesiones abiertas.</small>
            </div>
            <p class="text-danger small mt-2"><strong>Esta acción no se puede deshacer.</strong></p>
        `,
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-trash"></i> Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Eliminando...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Enviar formulario
            document.getElementById('deleteMovementForm' + movementId).submit();
        }
    });
}
</script>
@endpush
@endsection

