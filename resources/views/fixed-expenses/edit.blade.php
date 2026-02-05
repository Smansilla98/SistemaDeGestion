@extends('layouts.app')

@section('title', 'Editar Gasto/Ingreso Fijo')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
            <i class="bi bi-pencil"></i> Editar Gasto/Ingreso Fijo
        </h1>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('fixed-expenses.update', $fixedExpense) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="name" class="form-label">Nombre *</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                       id="name" name="name" value="{{ old('name', $fixedExpense->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Descripción</label>
                <textarea class="form-control @error('description') is-invalid @enderror" 
                          id="description" name="description" rows="3">{{ old('description', $fixedExpense->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="type" class="form-label">Tipo *</label>
                        <select class="form-select @error('type') is-invalid @enderror" 
                                id="type" name="type" required>
                            <option value="GASTO" {{ old('type', $fixedExpense->type) == 'GASTO' ? 'selected' : '' }}>Gasto</option>
                            <option value="INGRESO" {{ old('type', $fixedExpense->type) == 'INGRESO' ? 'selected' : '' }}>Ingreso</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="category" class="form-label">Categoría *</label>
                        <select class="form-select @error('category') is-invalid @enderror" 
                                id="category" name="category" required>
                            <option value="ALQUILER" {{ old('category', $fixedExpense->category) == 'ALQUILER' ? 'selected' : '' }}>Alquiler</option>
                            <option value="SERVICIOS" {{ old('category', $fixedExpense->category) == 'SERVICIOS' ? 'selected' : '' }}>Servicios</option>
                            <option value="PERSONAL" {{ old('category', $fixedExpense->category) == 'PERSONAL' ? 'selected' : '' }}>Personal</option>
                            <option value="OPERATIVOS" {{ old('category', $fixedExpense->category) == 'OPERATIVOS' ? 'selected' : '' }}>Operativos</option>
                            <option value="TALLER" {{ old('category', $fixedExpense->category) == 'TALLER' ? 'selected' : '' }}>Taller</option>
                            <option value="OTROS" {{ old('category', $fixedExpense->category) == 'OTROS' ? 'selected' : '' }}>Otros</option>
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Monto *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" class="form-control @error('amount') is-invalid @enderror" 
                                   id="amount" name="amount" value="{{ old('amount', $fixedExpense->amount) }}" required min="0">
                        </div>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="frequency" class="form-label">Frecuencia *</label>
                        <select class="form-select @error('frequency') is-invalid @enderror" 
                                id="frequency" name="frequency" required>
                            <option value="DIARIO" {{ old('frequency', $fixedExpense->frequency) == 'DIARIO' ? 'selected' : '' }}>Diario</option>
                            <option value="SEMANAL" {{ old('frequency', $fixedExpense->frequency) == 'SEMANAL' ? 'selected' : '' }}>Semanal</option>
                            <option value="QUINCENAL" {{ old('frequency', $fixedExpense->frequency) == 'QUINCENAL' ? 'selected' : '' }}>Quincenal</option>
                            <option value="MENSUAL" {{ old('frequency', $fixedExpense->frequency) == 'MENSUAL' ? 'selected' : '' }}>Mensual</option>
                            <option value="ANUAL" {{ old('frequency', $fixedExpense->frequency) == 'ANUAL' ? 'selected' : '' }}>Anual</option>
                        </select>
                        @error('frequency')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Fecha de Inicio *</label>
                        <input type="date" class="form-control @error('start_date') is-invalid @enderror" 
                               id="start_date" name="start_date" 
                               value="{{ old('start_date', $fixedExpense->start_date->format('Y-m-d')) }}" required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="end_date" class="form-label">Fecha de Fin (Opcional)</label>
                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" 
                               id="end_date" name="end_date" 
                               value="{{ old('end_date', $fixedExpense->end_date ? $fixedExpense->end_date->format('Y-m-d') : '') }}">
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Dejar vacío para indefinido</small>
                    </div>
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                       {{ old('is_active', $fixedExpense->is_active) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">
                    Activo
                </label>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('fixed-expenses.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Actualizar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

