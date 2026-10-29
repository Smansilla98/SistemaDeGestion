@extends('layouts.auth')

@section('title', 'Registro')
@section('subtitle', 'Creá tu cuenta en el espacio')

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
        <label for="role" class="form-label">
            <i class="bi bi-person-vcard"></i> ¿Cómo participás en el espacio?
        </label>
        <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
            <option value="" disabled @selected(! old('role'))>Elegí tu rol</option>
            <optgroup label="Comunidad (acceso inmediato)">
                @foreach($communityRoles as $value => $label)
                    <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                @endforeach
            </optgroup>
            <optgroup label="Equipo del espacio (requiere aprobación)">
                @foreach($staffRoles as $value => $label)
                    <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                @endforeach
            </optgroup>
        </select>
        @error('role')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text" id="roleHelp">Alumnos, profes, artistas y emprendedores entran al instante. Los roles del equipo los aprueba la administración.</div>
    </div>

    <div class="row">
        <div class="col-sm-6 mb-4">
            <label for="phone" class="form-label"><i class="bi bi-whatsapp"></i> Teléfono</label>
            <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" maxlength="40" autocomplete="tel" placeholder="Opcional">
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-sm-6 mb-4">
            <label for="email" class="form-label"><i class="bi bi-envelope"></i> Email</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" maxlength="150" autocomplete="email" placeholder="Opcional">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
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
