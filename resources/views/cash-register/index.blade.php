@extends('layouts.app')

@section('title', 'Módulo de Caja')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-cash-coin"></i> Módulo de Caja</h1>
        </div>
        @if(auth()->user()->role === 'ADMIN')
        <a href="{{ route('cash-register.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nueva Caja
        </a>
        @endif
    </div>
</div>

@if($activeSessions->count() > 0)
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-warning">
            <h5><i class="bi bi-exclamation-triangle"></i> Sesiones Abiertas</h5>
            @foreach($activeSessions as $session)
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <strong>{{ $session->cashRegister->name }}</strong> - 
                    Abierta por {{ $session->user->name }} a las {{ $session->opened_at->format('H:i') }}
                </div>
                <a href="{{ route('cash-register.session', $session) }}" class="btn btn-sm btn-primary">
                    Ver Sesión
                </a>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

@if(auth()->user()->role === 'ADMIN')
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Gestión de Cajas</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Sesiones</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cashRegisters as $cashRegister)
                            <tr>
                                <td><strong>{{ $cashRegister->name }}</strong></td>
                                <td>
                                    <span class="badge bg-info">{{ $cashRegister->sessions_count }}</span>
                                </td>
                                <td>
                                    @if($cashRegister->is_active)
                                    <span class="badge bg-success">Activa</span>
                                    @else
                                    <span class="badge bg-secondary">Inactiva</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('cash-register.edit', $cashRegister) }}" class="btn btn-outline-secondary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('cash-register.destroy', $cashRegister) }}" method="POST" class="d-inline" id="deleteCashRegisterForm{{ $cashRegister->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-outline-danger" onclick="confirmDeleteCashRegister({{ $cashRegister->id }}, '{{ addslashes($cashRegister->name) }}')" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">
                                    No hay cajas registradas. <a href="{{ route('cash-register.create') }}">Crear primera caja</a>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@if($cashRegisters->where('is_active', true)->count() > 0)
<div class="row">
    @foreach($cashRegisters->where('is_active', true) as $cashRegister)
    @php
        $hasActiveSession = $activeSessions->contains('cash_register_id', $cashRegister->id);
        $activeSession = $activeSessions->firstWhere('cash_register_id', $cashRegister->id);
    @endphp
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100 {{ $hasActiveSession ? 'border-success' : 'border-primary' }}">
            <div class="card-header {{ $hasActiveSession ? 'bg-success text-white' : 'bg-primary text-white' }}">
                <h5 class="mb-0">
                    <i class="bi bi-cash-register"></i> {{ $cashRegister->name }}
                    @if($hasActiveSession)
                        <span class="badge bg-light text-success ms-2">Abierta</span>
                    @endif
                </h5>
            </div>
            <div class="card-body">
                @if($hasActiveSession)
                    <div class="alert alert-success mb-3">
                        <p class="mb-1"><strong>Sesión activa</strong></p>
                        <p class="mb-1 small">Abierta por: {{ $activeSession->user->name }}</p>
                        <p class="mb-0 small">Desde: {{ $activeSession->opened_at->format('d/m/Y H:i') }}</p>
                        <p class="mb-0 small">Monto inicial: ${{ number_format($activeSession->initial_amount, 2) }}</p>
                    </div>
                    <a href="{{ route('cash-register.session', $activeSession) }}" class="btn btn-success w-100">
                        <i class="bi bi-eye"></i> Ver Sesión
                    </a>
                @else
                    <form action="{{ route('cash-register.open-session') }}" method="POST" class="open-session-form">
                        @csrf
                        <input type="hidden" name="cash_register_id" value="{{ $cashRegister->id }}">
                        <div class="mb-3">
                            <label for="initial_amount_{{ $cashRegister->id }}" class="form-label">Monto Inicial</label>
                            <input type="number" step="0.01" class="form-control" 
                                   id="initial_amount_{{ $cashRegister->id }}" 
                                   name="initial_amount" 
                                   required 
                                   min="0"
                                   value="0"
                                   placeholder="0.00">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-cash-stack"></i> Abrir Sesión
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="row">
    <div class="col-12">
        <div class="alert alert-warning">
            <h5><i class="bi bi-exclamation-triangle"></i> No hay cajas activas</h5>
            <p>No hay cajas activas disponibles. <a href="{{ route('cash-register.create') }}">Crear una caja</a></p>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
function confirmDeleteCashRegister(cashRegisterId, cashRegisterName) {
    Swal.fire({
        icon: 'warning',
        title: '¿Eliminar Caja?',
        html: `
            <p>¿Estás seguro de eliminar la caja <strong>${cashRegisterName}</strong>?</p>
            <div class="alert alert-warning mt-3">
                <small><i class="bi bi-info-circle"></i> No se puede eliminar una caja con sesiones abiertas o históricas.</small>
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
            Swal.fire({
                title: 'Eliminando...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            document.getElementById('deleteCashRegisterForm' + cashRegisterId).submit();
        }
    });
}
</script>
@endpush
@endsection


