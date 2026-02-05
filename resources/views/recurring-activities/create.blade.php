@extends('layouts.app')

@section('title', 'Nueva Actividad Recurrente')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
            <i class="bi bi-calendar-repeat"></i> Nueva Actividad Recurrente
        </h1>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('recurring-activities.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label">Nombre *</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                       id="name" name="name" value="{{ old('name') }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Descripción</label>
                <textarea class="form-control @error('description') is-invalid @enderror" 
                          id="description" name="description" rows="3">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="day_of_week" class="form-label">Día de la Semana *</label>
                        <select class="form-select @error('day_of_week') is-invalid @enderror" 
                                id="day_of_week" name="day_of_week" required>
                            <option value="MONDAY" {{ old('day_of_week') == 'MONDAY' ? 'selected' : '' }}>Lunes</option>
                            <option value="TUESDAY" {{ old('day_of_week') == 'TUESDAY' ? 'selected' : '' }}>Martes</option>
                            <option value="WEDNESDAY" {{ old('day_of_week') == 'WEDNESDAY' ? 'selected' : '' }}>Miércoles</option>
                            <option value="THURSDAY" {{ old('day_of_week') == 'THURSDAY' ? 'selected' : '' }}>Jueves</option>
                            <option value="FRIDAY" {{ old('day_of_week') == 'FRIDAY' ? 'selected' : '' }}>Viernes</option>
                            <option value="SATURDAY" {{ old('day_of_week') == 'SATURDAY' ? 'selected' : '' }}>Sábado</option>
                            <option value="SUNDAY" {{ old('day_of_week') == 'SUNDAY' ? 'selected' : '' }}>Domingo</option>
                        </select>
                        @error('day_of_week')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="start_time" class="form-label">Hora de Inicio *</label>
                        <input type="time" class="form-control @error('start_time') is-invalid @enderror" 
                               id="start_time" name="start_time" value="{{ old('start_time') }}" required>
                        @error('start_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="end_time" class="form-label">Hora de Fin</label>
                        <input type="time" class="form-control @error('end_time') is-invalid @enderror" 
                               id="end_time" name="end_time" value="{{ old('end_time') }}">
                        @error('end_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="expected_attendance" class="form-label">Asistencia Esperada</label>
                        <input type="number" class="form-control @error('expected_attendance') is-invalid @enderror" 
                               id="expected_attendance" name="expected_attendance" 
                               value="{{ old('expected_attendance') }}" min="0">
                        @error('expected_attendance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Cantidad de personas esperadas</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="expected_revenue" class="form-label">Ingreso Esperado</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" class="form-control @error('expected_revenue') is-invalid @enderror" 
                                   id="expected_revenue" name="expected_revenue" 
                                   value="{{ old('expected_revenue') }}" min="0">
                        </div>
                        @error('expected_revenue')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Ingreso económico esperado</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Fecha de Inicio (Opcional)</label>
                        <input type="date" class="form-control @error('start_date') is-invalid @enderror" 
                               id="start_date" name="start_date" value="{{ old('start_date') }}">
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Dejar vacío para iniciar inmediatamente</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="end_date" class="form-label">Fecha de Fin (Opcional)</label>
                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" 
                               id="end_date" name="end_date" value="{{ old('end_date') }}">
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Dejar vacío para indefinido</small>
                    </div>
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                       {{ old('is_active', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">
                    Activa
                </label>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('recurring-activities.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

