@extends('layouts.app')

@section('title', 'Reservar Mesa')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('tables.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-calendar-check"></i> Reservar Mesa: {{ $table->number }}</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Información de la Reserva</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('tables.reserve.store', $table) }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="customer_name" class="form-label">Nombre del Cliente *</label>
                        <input type="text" 
                               class="form-control @error('customer_name') is-invalid @enderror" 
                               id="customer_name" 
                               name="customer_name" 
                               value="{{ old('customer_name') }}" 
                               required>
                        @error('customer_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="customer_phone" class="form-label">Teléfono *</label>
                        <input type="text" 
                               class="form-control @error('customer_phone') is-invalid @enderror" 
                               id="customer_phone" 
                               name="customer_phone" 
                               value="{{ old('customer_phone') }}" 
                               required>
                        @error('customer_phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="reservation_date" class="form-label">Fecha *</label>
                            <input type="date" 
                                   class="form-control @error('reservation_date') is-invalid @enderror" 
                                   id="reservation_date" 
                                   name="reservation_date" 
                                   value="{{ old('reservation_date', date('Y-m-d')) }}" 
                                   min="{{ date('Y-m-d') }}"
                                   required>
                            @error('reservation_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="reservation_time" class="form-label">Hora *</label>
                            <input type="time" 
                                   class="form-control @error('reservation_time') is-invalid @enderror" 
                                   id="reservation_time" 
                                   name="reservation_time" 
                                   value="{{ old('reservation_time', '20:00') }}" 
                                   required>
                            @error('reservation_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="number_of_guests" class="form-label">Número de Comensales *</label>
                        <input type="number" 
                               class="form-control @error('number_of_guests') is-invalid @enderror" 
                               id="number_of_guests" 
                               name="number_of_guests" 
                               value="{{ old('number_of_guests', $table->capacity) }}" 
                               min="1"
                               max="{{ $table->capacity }}"
                               required>
                        @error('number_of_guests')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Capacidad máxima: {{ $table->capacity }} personas</small>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('tables.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Confirmar Reserva
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Información de la Mesa</h5>
            </div>
            <div class="card-body">
                <p><strong>Número:</strong> {{ $table->number }}</p>
                <p><strong>Capacidad:</strong> {{ $table->capacity }} personas</p>
                <p><strong>Sector:</strong> {{ $table->sector->name }}</p>
                <p><strong>Estado:</strong> 
                    <span class="badge bg-{{ $table->status === 'LIBRE' ? 'success' : 'warning' }}">
                        {{ $table->status }}
                    </span>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

