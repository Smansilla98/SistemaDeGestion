@extends('layouts.app')

@section('title', 'Editar Mesa')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('tables.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1><i class="bi bi-pencil-square"></i> Editar Mesa: {{ $table->number }}</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Información de la Mesa</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('tables.update', $table) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="number" class="form-label">Número de Mesa *</label>
                        <input type="text" 
                               class="form-control @error('number') is-invalid @enderror" 
                               id="number" 
                               name="number" 
                               value="{{ old('number', $table->number) }}" 
                               required>
                        @error('number')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="capacity" class="form-label">Capacidad (personas) *</label>
                        <input type="number" 
                               class="form-control @error('capacity') is-invalid @enderror" 
                               id="capacity" 
                               name="capacity" 
                               value="{{ old('capacity', $table->capacity) }}" 
                               min="1" 
                               required>
                        @error('capacity')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="sector_id" class="form-label">Sector *</label>
                        <select class="form-select @error('sector_id') is-invalid @enderror" 
                                id="sector_id" 
                                name="sector_id" 
                                required>
                            <option value="">Seleccionar sector</option>
                            @foreach($sectors as $sector)
                            <option value="{{ $sector->id }}" 
                                    {{ old('sector_id', $table->sector_id) == $sector->id ? 'selected' : '' }}>
                                {{ $sector->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('sector_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Estado *</label>
                        <select class="form-select @error('status') is-invalid @enderror" 
                                id="status" 
                                name="status" 
                                required>
                            <option value="LIBRE" {{ old('status', $table->status) === 'LIBRE' ? 'selected' : '' }}>Libre</option>
                            <option value="OCUPADA" {{ old('status', $table->status) === 'OCUPADA' ? 'selected' : '' }}>Ocupada</option>
                            <option value="RESERVADA" {{ old('status', $table->status) === 'RESERVADA' ? 'selected' : '' }}>Reservada</option>
                            <option value="CERRADA" {{ old('status', $table->status) === 'CERRADA' ? 'selected' : '' }}>Cerrada</option>
                        </select>
                        @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="position_x" class="form-label">Posición X (para layout)</label>
                        <input type="number" 
                               class="form-control @error('position_x') is-invalid @enderror" 
                               id="position_x" 
                               name="position_x" 
                               value="{{ old('position_x', $table->position_x ?? 0) }}" 
                               min="0">
                        @error('position_x')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Posición horizontal en el layout visual</small>
                    </div>

                    <div class="mb-3">
                        <label for="position_y" class="form-label">Posición Y (para layout)</label>
                        <input type="number" 
                               class="form-control @error('position_y') is-invalid @enderror" 
                               id="position_y" 
                               name="position_y" 
                               value="{{ old('position_y', $table->position_y ?? 0) }}" 
                               min="0">
                        @error('position_y')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Posición vertical en el layout visual</small>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('tables.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Información Actual</h5>
            </div>
            <div class="card-body">
                <p><strong>Número:</strong> {{ $table->number }}</p>
                <p><strong>Capacidad:</strong> {{ $table->capacity }} personas</p>
                <p><strong>Sector:</strong> {{ $table->sector->name }}</p>
                <p><strong>Estado:</strong> 
                    <span class="badge bg-{{ $table->status === 'LIBRE' ? 'success' : ($table->status === 'OCUPADA' ? 'warning' : 'secondary') }}">
                        {{ $table->status }}
                    </span>
                </p>
                @if($table->current_order_id)
                <div class="alert alert-info mt-3">
                    <strong>Pedido Activo:</strong><br>
                    <a href="{{ route('orders.show', $table->currentOrder) }}" class="btn btn-sm btn-primary mt-2">
                        Ver Pedido
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

