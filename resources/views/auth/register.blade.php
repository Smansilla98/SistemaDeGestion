@extends('layouts.auth')

@section('title', 'Registro')
@section('subtitle', 'Creá tu cuenta')

@section('content')
<form method="POST" action="{{ route('register') }}" id="registerForm">
    @csrf

    <div class="mb-4">
        <label for="name" class="form-label">
            <i class="bi bi-person-badge"></i> Nombre completo
        </label>
        <input type="text"
               class="form-control @error('name') is-invalid @enderror"
               id="name"
               name="name"
               value="{{ old('name') }}"
               placeholder="Tu nombre"
               required
               autofocus
               autocomplete="name">
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="username" class="form-label">
            <i class="bi bi-person"></i> Usuario
        </label>
        <input type="text"
               class="form-control @error('username') is-invalid @enderror"
               id="username"
               name="username"
               value="{{ old('username') }}"
               placeholder="Nombre de usuario"
               required
               autocomplete="username">
        @error('username')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="password" class="form-label">
            <i class="bi bi-lock"></i> Contraseña
        </label>
        <input type="password"
               class="form-control @error('password') is-invalid @enderror"
               id="password"
               name="password"
               placeholder="Mínimo 8 caracteres"
               required
               autocomplete="new-password">
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="password_confirmation" class="form-label">
            <i class="bi bi-lock-fill"></i> Confirmar contraseña
        </label>
        <input type="password"
               class="form-control"
               id="password_confirmation"
               name="password_confirmation"
               placeholder="Repetir contraseña"
               required
               autocomplete="new-password">
    </div>

    <button type="submit" class="btn btn-primary w-100 mb-4">
        <i class="bi bi-person-plus"></i> Crear cuenta
    </button>
</form>

<div class="text-center">
    <a href="{{ route('login') }}" class="text-decoration-none" style="color: var(--conurbania-primary);">
        <i class="bi bi-box-arrow-in-right"></i> Ya tengo cuenta, iniciar sesión
    </a>
</div>

@push('scripts')
<script>
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creando cuenta...';
});
</script>
@endpush
@endsection
