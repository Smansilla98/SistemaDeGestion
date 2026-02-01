@extends('layouts.app')

@section('title', 'Sesión de Caja')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('cash-register.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1><i class="bi bi-cash-coin"></i> Sesión de Caja: {{ $session->cashRegister->name }}</h1>
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
                <form action="{{ route('cash-register.close-session', $session) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="final_amount" class="form-label">Monto Final en Caja</label>
                        <input type="number" step="0.01" class="form-control" id="final_amount" name="final_amount" required min="0">
                        <small class="text-muted">Monto esperado: ${{ number_format($expectedAmount, 2) }}</small>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notas</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger">
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
                                        <small class="text-muted">{{ Str::limit($payment->notes, 30) }}</small>
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
            <div class="card-header">
                <h5 class="mb-0">Movimientos</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Descripción</th>
                                <th>Monto</th>
                                <th>Fecha</th>
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
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

