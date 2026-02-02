@extends('layouts.app')

@section('title', 'Nueva Caja')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('cash-register.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-plus-circle"></i> Nueva Caja</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Información de la Caja</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('cash-register.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre *</label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}" 
                               placeholder="Ej: Caja Principal, Caja 1, etc."
                               required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="is_active" 
                                   name="is_active" 
                                   value="1" 
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Caja activa
                            </label>
                            <small class="form-text text-muted d-block">Solo las cajas activas pueden abrir sesiones</small>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('cash-register.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Crear Caja
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

