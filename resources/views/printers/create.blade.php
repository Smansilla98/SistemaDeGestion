@extends('layouts.app')

@section('title', 'Nueva Impresora')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('printers.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1><i class="bi bi-plus-circle"></i> Nueva Impresora</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Configuración de Impresora</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('printers.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">Tipo *</label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="">Seleccionar...</option>
                                <option value="kitchen" {{ old('type') === 'kitchen' ? 'selected' : '' }}>Cocina</option>
                                <option value="bar" {{ old('type') === 'bar' ? 'selected' : '' }}>Barra</option>
                                <option value="cashier" {{ old('type') === 'cashier' ? 'selected' : '' }}>Cajero</option>
                                <option value="invoice" {{ old('type') === 'invoice' ? 'selected' : '' }}>Factura</option>
                            </select>
                            @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="connection_type" class="form-label">Tipo de Conexión *</label>
                            <select class="form-select @error('connection_type') is-invalid @enderror" 
                                    id="connection_type" name="connection_type" required onchange="toggleConnectionFields()">
                                <option value="">Seleccionar...</option>
                                <option value="network" {{ old('connection_type') === 'network' ? 'selected' : '' }}>Red (Network)</option>
                                <option value="usb" {{ old('connection_type') === 'usb' ? 'selected' : '' }}>USB</option>
                                <option value="file" {{ old('connection_type') === 'file' ? 'selected' : '' }}>Archivo</option>
                            </select>
                            @error('connection_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div id="network_fields" style="display: none;">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="ip_address" class="form-label">Dirección IP</label>
                                <input type="text" class="form-control @error('ip_address') is-invalid @enderror" 
                                       id="ip_address" name="ip_address" value="{{ old('ip_address') }}" 
                                       placeholder="192.168.1.100">
                                @error('ip_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="port" class="form-label">Puerto</label>
                                <input type="number" class="form-control @error('port') is-invalid @enderror" 
                                       id="port" name="port" value="{{ old('port', 9100) }}" min="1" max="65535">
                                @error('port')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div id="file_fields" style="display: none;">
                        <div class="mb-3">
                            <label for="path" class="form-label">Ruta del Archivo</label>
                            <input type="text" class="form-control @error('path') is-invalid @enderror" 
                                   id="path" name="path" value="{{ old('path') }}" 
                                   placeholder="/ruta/donde/guardar">
                            @error('path')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Ruta donde se guardarán los archivos PDF</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="paper_width" class="form-label">Ancho de Papel *</label>
                            <select class="form-select @error('paper_width') is-invalid @enderror" 
                                    id="paper_width" name="paper_width" required>
                                <option value="58" {{ old('paper_width', 80) == 58 ? 'selected' : '' }}>58mm</option>
                                <option value="80" {{ old('paper_width', 80) == 80 ? 'selected' : '' }}>80mm</option>
                            </select>
                            @error('paper_width')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="auto_print" name="auto_print" value="1" 
                                   {{ old('auto_print') ? 'checked' : '' }}>
                            <label class="form-check-label" for="auto_print">
                                Impresión automática
                            </label>
                            <small class="form-text text-muted d-block">Imprimir automáticamente cuando se cree un pedido</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Impresora activa
                            </label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('printers.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Guardar Impresora
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Información</h5>
            </div>
            <div class="card-body">
                <p class="small text-muted">
                    <strong>Red (Network):</strong> Impresoras conectadas por IP en la red local. 
                    Requiere dirección IP y puerto (generalmente 9100).
                </p>
                <p class="small text-muted">
                    <strong>USB:</strong> Impresoras conectadas directamente al servidor. 
                    Requiere configuración adicional del sistema.
                </p>
                <p class="small text-muted">
                    <strong>Archivo:</strong> Guarda los PDFs en una ruta especificada 
                    para impresión manual o procesamiento externo.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function toggleConnectionFields() {
    const connectionType = document.getElementById('connection_type').value;
    document.getElementById('network_fields').style.display = connectionType === 'network' ? 'block' : 'none';
    document.getElementById('file_fields').style.display = connectionType === 'file' ? 'block' : 'none';
}

// Ejecutar al cargar
document.addEventListener('DOMContentLoaded', toggleConnectionFields);
</script>
@endsection

