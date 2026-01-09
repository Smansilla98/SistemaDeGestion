@extends('layouts.app')

@section('title', 'Editar Impresora')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <a href="{{ route('printers.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1><i class="bi bi-pencil-square"></i> Editar Impresora: {{ $printer->name }}</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Configuración de Impresora</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('printers.update', $printer) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name', $printer->name) }}" required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="type" class="form-label">Tipo *</label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="kitchen" {{ old('type', $printer->type) === 'kitchen' ? 'selected' : '' }}>Cocina</option>
                                <option value="bar" {{ old('type', $printer->type) === 'bar' ? 'selected' : '' }}>Barra</option>
                                <option value="cashier" {{ old('type', $printer->type) === 'cashier' ? 'selected' : '' }}>Cajero</option>
                                <option value="invoice" {{ old('type', $printer->type) === 'invoice' ? 'selected' : '' }}>Factura</option>
                            </select>
                            @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="connection_type" class="form-label">Tipo de Conexión *</label>
                            <select class="form-select @error('connection_type') is-invalid @enderror" 
                                    id="connection_type" name="connection_type" required onchange="toggleConnectionFields()">
                                <option value="network" {{ old('connection_type', $printer->connection_type) === 'network' ? 'selected' : '' }}>Red (Network)</option>
                                <option value="usb" {{ old('connection_type', $printer->connection_type) === 'usb' ? 'selected' : '' }}>USB</option>
                                <option value="file" {{ old('connection_type', $printer->connection_type) === 'file' ? 'selected' : '' }}>Archivo</option>
                            </select>
                            @error('connection_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div id="network_fields" style="display: {{ old('connection_type', $printer->connection_type) === 'network' ? 'block' : 'none' }};">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="ip_address" class="form-label">Dirección IP</label>
                                <input type="text" class="form-control @error('ip_address') is-invalid @enderror" 
                                       id="ip_address" name="ip_address" value="{{ old('ip_address', $printer->ip_address) }}" 
                                       placeholder="192.168.1.100">
                                @error('ip_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="port" class="form-label">Puerto</label>
                                <input type="number" class="form-control @error('port') is-invalid @enderror" 
                                       id="port" name="port" value="{{ old('port', $printer->port) }}" min="1" max="65535">
                                @error('port')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div id="file_fields" style="display: {{ old('connection_type', $printer->connection_type) === 'file' ? 'block' : 'none' }};">
                        <div class="mb-3">
                            <label for="path" class="form-label">Ruta del Archivo</label>
                            <input type="text" class="form-control @error('path') is-invalid @enderror" 
                                   id="path" name="path" value="{{ old('path', $printer->path) }}" 
                                   placeholder="/ruta/donde/guardar">
                            @error('path')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="paper_width" class="form-label">Ancho de Papel *</label>
                            <select class="form-select @error('paper_width') is-invalid @enderror" 
                                    id="paper_width" name="paper_width" required>
                                <option value="58" {{ old('paper_width', $printer->paper_width) == 58 ? 'selected' : '' }}>58mm</option>
                                <option value="80" {{ old('paper_width', $printer->paper_width) == 80 ? 'selected' : '' }}>80mm</option>
                            </select>
                            @error('paper_width')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="auto_print" name="auto_print" value="1" 
                                   {{ old('auto_print', $printer->auto_print) ? 'checked' : '' }}>
                            <label class="form-check-label" for="auto_print">
                                Impresión automática
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" 
                                   {{ old('is_active', $printer->is_active) ? 'checked' : '' }}>
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
                            <i class="bi bi-check-circle"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
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
</script>
@endsection

