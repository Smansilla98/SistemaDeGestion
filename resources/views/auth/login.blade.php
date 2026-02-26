@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<form method="POST" action="{{ route('login') }}" id="loginForm">
    @csrf

    <div class="mb-4">
        <label for="username" class="form-label">
            <i class="bi bi-person"></i> Usuario
        </label>
        <input type="text" 
               class="form-control @error('username') is-invalid @enderror" 
               id="username" 
               name="username" 
               value="{{ old('username') }}" 
               placeholder="Usuario"
               required 
               autofocus
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
               placeholder="••••••••"
               required>
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4 form-check">
        <input type="checkbox" class="form-check-input" id="remember" name="remember">
        <label class="form-check-label" for="remember">
            Recordarme
        </label>
    </div>

    <button type="submit" class="btn btn-primary w-100 mb-4">
        <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
    </button>
</form>

@if(\Illuminate\Support\Facades\Route::has('register'))
<div class="text-center mt-3">
    <a href="{{ route('register') }}" class="text-decoration-none" style="color: var(--conurbania-primary);">
        <i class="bi bi-person-plus"></i> Crear cuenta
    </a>
</div>
@endif

<div class="mt-4 pt-4 border-top d-none">
    <div class="text-center">
        <small class="text-muted d-block mb-2">
            <strong>Usuarios de prueba:</strong>
        </small>
        <div class="small text-muted">
            <div class="mb-1">
                <i class="bi bi-person-badge"></i> <strong>Admin:</strong> admin / admin123
            </div>
            <div class="mb-1">
                <i class="bi bi-person"></i> <strong>Mozo:</strong> mozo / mozo123
            </div>
            <div class="mb-1">
                <i class="bi bi-egg-fried"></i> <strong>Cocina:</strong> cocina / cocina123
            </div>
            <div>
                <i class="bi bi-cash-coin"></i> <strong>Caja:</strong> caja / caja123
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Iniciando sesión...';
});
</script>
@endpush
@endsection

