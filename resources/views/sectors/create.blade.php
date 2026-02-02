@extends('layouts.app')

@section('title', 'Nuevo Sector')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        @if($parentSector)
        <a href="{{ route('sectors.show', $parentSector) }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver a {{ $parentSector->name }}
        </a>
        @else
        <a href="{{ route('sectors.index') }}" class="btn btn-secondary mb-2">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        @endif
        <h1 class="text-white mb-2" style="font-weight: 700; font-size: 2.5rem;">
            <i class="bi bi-plus-circle"></i> {{ $parentSector ? 'Nuevo Subsector' : 'Nuevo Sector' }}
        </h1>
        @if($parentSector)
        <p class="text-muted">Creando subsector para: <strong>{{ $parentSector->name }}</strong></p>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Información del Sector</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('sectors.store') }}" method="POST">
                    @csrf

                    @if($parentSector)
                    <input type="hidden" name="parent_id" value="{{ $parentSector->id }}">
                    <input type="hidden" name="type" value="SUBSECTOR">
                    @else
                    <div class="mb-3">
                        <label for="type" class="form-label">Tipo *</label>
                        <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required onchange="toggleSubsectorFields()">
                            <option value="SECTOR" {{ old('type') === 'SECTOR' ? 'selected' : '' }}>Sector Principal</option>
                            <option value="SUBSECTOR" {{ old('type') === 'SUBSECTOR' ? 'selected' : '' }}>Subsector</option>
                        </select>
                        @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="parentSectorContainer" style="display: {{ old('type') === 'SUBSECTOR' ? 'block' : 'none' }};">
                        <label for="parent_id" class="form-label">Sector Padre *</label>
                        <select class="form-select @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id">
                            <option value="">Seleccionar sector...</option>
                            @foreach($sectors as $sector)
                            <option value="{{ $sector->id }}" {{ old('parent_id') == $sector->id ? 'selected' : '' }}>
                                {{ $sector->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('parent_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Selecciona el sector principal al que pertenecerá este subsector</small>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre *</label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}" 
                               required>
                        @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="3">{{ old('description') }}</textarea>
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3" id="capacityContainer" style="display: {{ ($parentSector || old('type') === 'SUBSECTOR') ? 'block' : 'none' }};">
                        <label for="capacity" class="form-label">Capacidad (Elementos)</label>
                        <input type="number" 
                               class="form-control @error('capacity') is-invalid @enderror" 
                               id="capacity" 
                               name="capacity" 
                               value="{{ old('capacity') }}" 
                               min="1"
                               placeholder="Ej: 4 para la barra">
                        @error('capacity')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Número de elementos/lugares que tendrá el subsector (se crearán automáticamente)</small>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="is_active" 
                                   name="is_active" 
                                   value="1" 
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                {{ $parentSector ? 'Subsector activo' : 'Sector activo' }}
                            </label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        @if($parentSector)
                        <a href="{{ route('sectors.show', $parentSector) }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        @else
                        <a href="{{ route('sectors.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                        @endif
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> {{ $parentSector ? 'Crear Subsector' : 'Crear Sector' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleSubsectorFields() {
    const type = document.getElementById('type').value;
    const parentContainer = document.getElementById('parentSectorContainer');
    const capacityContainer = document.getElementById('capacityContainer');
    
    if (type === 'SUBSECTOR') {
        parentContainer.style.display = 'block';
        capacityContainer.style.display = 'block';
        document.getElementById('parent_id').required = true;
    } else {
        parentContainer.style.display = 'none';
        capacityContainer.style.display = 'none';
        document.getElementById('parent_id').required = false;
        document.getElementById('parent_id').value = '';
    }
}
</script>
@endpush
@endsection

