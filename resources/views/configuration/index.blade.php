@extends('layouts.app')

@section('title', 'Configuración del Sistema')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;"><i class="bi bi-gear"></i> Configuración del Sistema</h1>
    </div>
</div>

<div class="row">
    <!-- Personalización Visual -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-palette"></i> Personalización Visual</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('configuration.update-visual') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- Logo -->
                    <div class="mb-4">
                        <label for="logo" class="form-label">Logo del Restaurante</label>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            @if($logo)
                            <div>
                                <img src="{{ Storage::url($logo) }}" alt="Logo" style="max-height: 100px; max-width: 200px;" class="img-thumbnail">
                            </div>
                            @endif
                            <div class="flex-grow-1">
                                <input type="file" 
                                       class="form-control @error('logo') is-invalid @enderror" 
                                       id="logo" 
                                       name="logo" 
                                       accept="image/*">
                                <small class="form-text text-muted">Formatos: JPG, PNG, GIF, SVG. Tamaño máximo: 2MB</small>
                                @error('logo')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Colores -->
                    <h6 class="mb-3"><i class="bi bi-paint-bucket"></i> Paleta de Colores</h6>
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <label for="primary_color" class="form-label">Color Primario</label>
                            <div class="input-group">
                                <input type="color" 
                                       class="form-control form-control-color" 
                                       id="primary_color" 
                                       name="primary_color" 
                                       value="{{ $colors['primary'] ?? '#111111' }}"
                                       title="Elige el color primario">
                                <input type="text" 
                                       class="form-control" 
                                       value="{{ $colors['primary'] ?? '#111111' }}"
                                       id="primary_color_text"
                                       pattern="^#[0-9A-Fa-f]{6}$"
                                       placeholder="#111111">
                            </div>
                            <small class="form-text text-muted">Color principal de la interfaz</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="secondary_color" class="form-label">Color Secundario</label>
                            <div class="input-group">
                                <input type="color" 
                                       class="form-control form-control-color" 
                                       id="secondary_color" 
                                       name="secondary_color" 
                                       value="{{ $colors['secondary'] ?? '#000000' }}"
                                       title="Elige el color secundario">
                                <input type="text" 
                                       class="form-control" 
                                       value="{{ $colors['secondary'] ?? '#000000' }}"
                                       id="secondary_color_text"
                                       pattern="^#[0-9A-Fa-f]{6}$"
                                       placeholder="#000000">
                            </div>
                            <small class="form-text text-muted">Color secundario de la interfaz</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="accent_color" class="form-label">Color de Acento</label>
                            <div class="input-group">
                                <input type="color" 
                                       class="form-control form-control-color" 
                                       id="accent_color" 
                                       name="accent_color" 
                                       value="{{ $colors['accent'] ?? '#5c5c5c' }}"
                                       title="Elige el color de acento">
                                <input type="text" 
                                       class="form-control" 
                                       value="{{ $colors['accent'] ?? '#5c5c5c' }}"
                                       id="accent_color_text"
                                       pattern="^#[0-9A-Fa-f]{6}$"
                                       placeholder="#5c5c5c">
                            </div>
                            <small class="form-text text-muted">Color para elementos destacados</small>
                        </div>
                    </div>

                    <hr>

                    <!-- Fuentes -->
                    <h6 class="mb-3"><i class="bi bi-type"></i> Fuentes</h6>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label for="primary_font" class="form-label">Fuente Primaria</label>
                            <select class="form-select" id="primary_font" name="primary_font">
                                <option value="Inter" {{ ($fonts['primary'] ?? 'Roboto') === 'Inter' ? 'selected' : '' }}>Inter</option>
                                <option value="Roboto" {{ ($fonts['primary'] ?? 'Roboto') === 'Roboto' ? 'selected' : '' }}>Roboto</option>
                                <option value="Open Sans" {{ ($fonts['primary'] ?? 'Roboto') === 'Open Sans' ? 'selected' : '' }}>Open Sans</option>
                                <option value="Lato" {{ ($fonts['primary'] ?? 'Roboto') === 'Lato' ? 'selected' : '' }}>Lato</option>
                                <option value="Montserrat" {{ ($fonts['primary'] ?? 'Roboto') === 'Montserrat' ? 'selected' : '' }}>Montserrat</option>
                                <option value="Poppins" {{ ($fonts['primary'] ?? 'Roboto') === 'Poppins' ? 'selected' : '' }}>Poppins</option>
                                <option value="Raleway" {{ ($fonts['primary'] ?? 'Roboto') === 'Raleway' ? 'selected' : '' }}>Raleway</option>
                                <option value="Nunito" {{ ($fonts['primary'] ?? 'Roboto') === 'Nunito' ? 'selected' : '' }}>Nunito</option>
                            </select>
                            <small class="form-text text-muted">Fuente principal del sistema</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="secondary_font" class="form-label">Fuente Secundaria</label>
                            <select class="form-select" id="secondary_font" name="secondary_font">
                                <option value="Roboto" {{ ($fonts['secondary'] ?? 'Roboto') === 'Roboto' ? 'selected' : '' }}>Roboto</option>
                                <option value="Inter" {{ ($fonts['secondary'] ?? 'Roboto') === 'Inter' ? 'selected' : '' }}>Inter</option>
                                <option value="Open Sans" {{ ($fonts['secondary'] ?? 'Roboto') === 'Open Sans' ? 'selected' : '' }}>Open Sans</option>
                                <option value="Lato" {{ ($fonts['secondary'] ?? 'Roboto') === 'Lato' ? 'selected' : '' }}>Lato</option>
                                <option value="Montserrat" {{ ($fonts['secondary'] ?? 'Roboto') === 'Montserrat' ? 'selected' : '' }}>Montserrat</option>
                                <option value="Poppins" {{ ($fonts['secondary'] ?? 'Roboto') === 'Poppins' ? 'selected' : '' }}>Poppins</option>
                                <option value="Raleway" {{ ($fonts['secondary'] ?? 'Roboto') === 'Raleway' ? 'selected' : '' }}>Raleway</option>
                                <option value="Nunito" {{ ($fonts['secondary'] ?? 'Roboto') === 'Nunito' ? 'selected' : '' }}>Nunito</option>
                            </select>
                            <small class="form-text text-muted">Fuente secundaria del sistema</small>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar Configuración Visual
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Vista Previa -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-eye"></i> Vista Previa</h5>
            </div>
            <div class="card-body">
                <div class="preview-box" style="border: 2px solid {{ $colors['primary'] ?? '#111111' }}; padding: 20px; border-radius: 8px; margin-bottom: 15px;">
                    <h6 style="color: {{ $colors['primary'] ?? '#111111' }}; font-family: {{ $fonts['primary'] ?? 'Roboto' }};">
                        Texto Primario
                    </h6>
                    <p style="color: {{ $colors['secondary'] ?? '#000000' }}; font-family: {{ $fonts['secondary'] ?? 'Roboto' }};">
                        Texto Secundario
                    </p>
                    <button class="btn btn-sm" style="background-color: {{ $colors['accent'] ?? '#5c5c5c' }}; color: white; border: none;">
                        Botón de Acento
                    </button>
                </div>
                <small class="text-muted">Los cambios se aplicarán después de guardar</small>
            </div>
        </div>
    </div>
</div>

<!-- Web pública -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i class="bi bi-globe"></i> Web pública</h5>
                <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-sm btn-light"><i class="bi bi-box-arrow-up-right"></i> Ver la web</a>
            </div>
            <div class="card-body">
                <p class="text-muted">Lo que ve el público en la portada. Los talleres con inscripción abierta o en curso aparecen solos, con su formulario de inscripción; la agenda muestra eventos, ferias y fechas especiales de los próximos 90 días.</p>
                <form action="{{ route('configuration.update-public') }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <label for="pub_about" class="form-label">Presentación</label>
                        <textarea id="pub_about" name="about" rows="2" maxlength="400" class="form-control @error('about') is-invalid @enderror">{{ old('about', $public['about']) }}</textarea>
                        @error('about')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="pub_address" class="form-label">Dirección</label>
                        <input id="pub_address" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $restaurant->address) }}" maxlength="255">
                    </div>
                    <div class="col-md-6">
                        <label for="pub_hours" class="form-label">Horarios</label>
                        <input id="pub_hours" name="hours" class="form-control @error('hours') is-invalid @enderror" value="{{ old('hours', $public['hours']) }}" maxlength="150" placeholder="Mar a dom de 17 a 23 h">
                    </div>
                    <div class="col-md-3">
                        <label for="pub_instagram" class="form-label">Instagram</label>
                        <input id="pub_instagram" name="instagram" class="form-control @error('instagram') is-invalid @enderror" value="{{ old('instagram', $public['instagram']) }}" placeholder="@patuka">
                        @error('instagram')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label for="pub_whatsapp" class="form-label">WhatsApp</label>
                        <input id="pub_whatsapp" name="whatsapp" class="form-control" value="{{ old('whatsapp', $public['whatsapp']) }}" placeholder="5491122334455">
                    </div>
                    <div class="col-md-3">
                        <label for="pub_phone" class="form-label">Teléfono</label>
                        <input id="pub_phone" name="phone" class="form-control" value="{{ old('phone', $restaurant->phone) }}" maxlength="40">
                    </div>
                    <div class="col-md-3">
                        <label for="pub_email" class="form-label">Email</label>
                        <input id="pub_email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $restaurant->email) }}">
                    </div>
                    <div class="col-12"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Medios de cobro -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-credit-card-2-front"></i> Medios de cobro</h5>
            </div>
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <p class="mb-0">Configurá el alias/CVU de transferencia y el QR que van a ver los mozos al cobrar una mesa.</p>
                <a href="{{ route('configuration.payment-methods.index') }}" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-right"></i> Configurar
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Mantenimiento del Sistema -->
<div class="row">
    <div class="col-12">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Mantenimiento del Sistema</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <h6><i class="bi bi-info-circle"></i> Resetear Base de Datos</h6>
                    <p class="mb-2">
                        Esta acción eliminará <strong>TODOS</strong> los datos del sistema excepto los usuarios:
                    </p>
                    <ul class="mb-3">
                        <li>Pedidos y comandas</li>
                        <li>Mesas (se resetearán a estado LIBRE)</li>
                        <li>Facturas y pagos</li>
                        <li>Sesiones de caja y movimientos</li>
                        <li>Productos y categorías</li>
                        <li>Sectores</li>
                        <li>Impresoras y cajas registradoras</li>
                        <li>Movimientos de stock</li>
                        <li>Registros de auditoría</li>
                    </ul>
                    <p class="text-danger mb-0">
                        <strong>⚠️ ADVERTENCIA:</strong> Esta acción es <strong>IRREVERSIBLE</strong>. 
                        Se mantendrán únicamente los usuarios del sistema.
                    </p>
                </div>

                <form action="{{ route('configuration.reset-database') }}" method="POST" id="resetDatabaseForm">
                    @csrf
                    <div class="mb-3">
                        <label for="confirm_text" class="form-label">
                            Para confirmar, escribe <strong>RESETEAR</strong> en el siguiente campo:
                        </label>
                        <input type="text" 
                               class="form-control @error('confirm_text') is-invalid @enderror" 
                               id="confirm_text" 
                               name="confirm_text" 
                               placeholder="Escribe RESETEAR aquí"
                               required>
                        @error('confirm_text')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="button" 
                            class="btn btn-danger" 
                            onclick="confirmResetDatabase()">
                        <i class="bi bi-trash"></i> Resetear Base de Datos
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Sincronizar inputs de color
document.getElementById('primary_color').addEventListener('input', function(e) {
    document.getElementById('primary_color_text').value = e.target.value;
});
document.getElementById('primary_color_text').addEventListener('input', function(e) {
    if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
        document.getElementById('primary_color').value = e.target.value;
    }
});

document.getElementById('secondary_color').addEventListener('input', function(e) {
    document.getElementById('secondary_color_text').value = e.target.value;
});
document.getElementById('secondary_color_text').addEventListener('input', function(e) {
    if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
        document.getElementById('secondary_color').value = e.target.value;
    }
});

document.getElementById('accent_color').addEventListener('input', function(e) {
    document.getElementById('accent_color_text').value = e.target.value;
});
document.getElementById('accent_color_text').addEventListener('input', function(e) {
    if (/^#[0-9A-Fa-f]{6}$/.test(e.target.value)) {
        document.getElementById('accent_color').value = e.target.value;
    }
});

function confirmResetDatabase() {
    const confirmText = document.getElementById('confirm_text').value;
    
    if (confirmText !== 'RESETEAR') {
        Swal.fire({
            icon: 'error',
            title: 'Confirmación Incorrecta',
            text: 'Debes escribir exactamente "RESETEAR" para confirmar esta acción.',
        });
        return;
    }

    Swal.fire({
        icon: 'warning',
        title: '¿Resetear Base de Datos?',
        html: `
            <div class="text-start">
                <p class="mb-3">Esta acción eliminará <strong>TODOS</strong> los datos del sistema excepto los usuarios:</p>
                <ul class="text-start mb-3">
                    <li>Pedidos y comandas</li>
                    <li>Mesas (reseteadas a LIBRE)</li>
                    <li>Facturas y pagos</li>
                    <li>Sesiones de caja</li>
                    <li>Productos y categorías</li>
                    <li>Sectores</li>
                    <li>Impresoras y cajas</li>
                    <li>Movimientos de stock</li>
                    <li>Registros de auditoría</li>
                </ul>
                <div class="alert alert-danger mt-3">
                    <strong>⚠️ ADVERTENCIA:</strong> Esta acción es <strong>IRREVERSIBLE</strong>.
                </div>
                <p class="text-success mt-3"><strong>✓ Los usuarios se mantendrán intactos</strong></p>
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-trash"></i> Sí, resetear todo',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        width: '600px'
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar loading
            Swal.fire({
                title: 'Reseteando Base de Datos...',
                html: 'Esto puede tomar unos momentos. Por favor, no cierres esta ventana.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Enviar formulario
            document.getElementById('resetDatabaseForm').submit();
        }
    });
}
</script>
@endpush
@endsection

