@extends('layouts.app')

@section('title', 'Crear Usuario')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1><i class="bi bi-person-plus"></i> Crear Nuevo Usuario</h1>
        <p class="text-muted">Completa el formulario para crear un nuevo usuario</p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('users.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                           id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                           id="email" name="email" value="{{ old('email') }}" required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                           id="password" name="password" required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Mínimo 8 caracteres</small>
                </div>

                <div class="col-md-6">
                    <label for="password_confirmation" class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" 
                           id="password_confirmation" name="password_confirmation" required>
                </div>

                <div class="col-md-6">
                    <label for="role" class="form-label">Rol <span class="text-danger">*</span></label>
                    <select class="form-select @error('role') is-invalid @enderror" 
                            id="role" name="role" required>
                        <option value="">Seleccionar rol...</option>
                        @foreach($roles as $role)
                            <option value="{{ $role }}" {{ old('role') === $role ? 'selected' : '' }}>
                                {{ $role }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">
                        <strong>ADMIN:</strong> Acceso completo | 
                        <strong>SUPERVISOR:</strong> Supervisión y gestión | 
                        <strong>ENCARGADO:</strong> Gestión operativa | 
                        <strong>MOZO:</strong> Atención de mesas | 
                        <strong>COCINA:</strong> Preparación de pedidos | 
                        <strong>CAJERO:</strong> Gestión de pagos
                    </small>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Estado</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                               value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">
                            Usuario activo
                        </label>
                    </div>
                    <small class="text-muted">Los usuarios inactivos no pueden iniciar sesión</small>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Crear Usuario
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

