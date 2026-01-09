@extends('layouts.app')

@section('title', 'Módulo de Caja')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1><i class="bi bi-cash-coin"></i> Módulo de Caja</h1>
        <p class="text-muted">Gestión de cajas y sesiones</p>
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

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Abrir Nueva Sesión</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('cash-register.open-session') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="cash_register_id" class="form-label">Caja</label>
                        <select class="form-select" id="cash_register_id" name="cash_register_id" required>
                            <option value="">Seleccionar caja</option>
                            @foreach($cashRegisters as $cashRegister)
                            <option value="{{ $cashRegister->id }}">{{ $cashRegister->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="initial_amount" class="form-label">Monto Inicial</label>
                        <input type="number" step="0.01" class="form-control" id="initial_amount" name="initial_amount" required min="0">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-cash-stack"></i> Abrir Sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

