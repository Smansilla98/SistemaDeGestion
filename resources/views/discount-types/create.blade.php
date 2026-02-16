@extends('layouts.app')

@section('title', 'Nuevo Tipo de Descuento')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('discount-types.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-plus-circle"></i> Nuevo Tipo de Descuento</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Información del Descuento</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('discount-types.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre *</label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}" 
                               placeholder="Ej: Alumno UNLA, Artista"
                               required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Nombre descriptivo del tipo de descuento</small>
                    </div>

                    <div class="mb-3">
                        <label for="percentage" class="form-label">Porcentaje de Descuento *</label>
                        <div class="input-group">
                            <input type="number" 
                                   class="form-control @error('percentage') is-invalid @enderror" 
                                   id="percentage" 
                                   name="percentage" 
                                   value="{{ old('percentage') }}" 
                                   min="0" 
                                   max="100" 
                                   step="0.01"
                                   placeholder="20.00"
                                   required>
                            <span class="input-group-text">%</span>
                        </div>
                        @error('percentage')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Porcentaje de descuento a aplicar (0-100%)</small>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="3"
                                  placeholder="Ej: Descuento del 20% para alumnos de la Universidad Nacional de Lanús">{{ old('description') }}</textarea>
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Descripción opcional del descuento</small>
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
                                Descuento activo
                            </label>
                        </div>
                        <small class="text-muted">Solo los descuentos activos aparecerán al cerrar mesas</small>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('discount-types.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Crear Descuento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

