@extends('layouts.app')

@section('title', 'Layout de Mesas')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-diagram-3"></i> Layout de Mesas</h1>
            <p class="text-muted">Organiza las mesas visualmente</p>
        </div>
        <div>
            <select id="sectorSelect" class="form-select d-inline-block" style="width: auto;" onchange="loadSector(this.value)">
                <option value="">Seleccionar sector</option>
                @foreach($sectors as $sector)
                <option value="{{ $sector->id }}" {{ $selectedSector && $selectedSector->id === $sector->id ? 'selected' : '' }}>
                    {{ $sector->name }}
                </option>
                @endforeach
            </select>
            @if($selectedSector)
            <button type="button" class="btn btn-primary ms-2" onclick="saveLayout()">
                <i class="bi bi-save"></i> Guardar Layout
            </button>
            <button type="button" class="btn btn-secondary ms-2" onclick="toggleEditMode()">
                <i class="bi bi-pencil"></i> <span id="editModeText">Modo Edición</span>
            </button>
            @endif
        </div>
    </div>
</div>

@if($selectedSector)
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Sector: {{ $selectedSector->name }}</h5>
    </div>
    <div class="card-body">
        <div id="layoutCanvas" style="position: relative; width: 100%; min-height: 600px; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px; overflow: hidden;">
            @foreach($tables as $table)
            <div class="table-item" 
                 data-table-id="{{ $table->id }}"
                 data-initial-x="{{ $table->position_x ?? 50 }}"
                 data-initial-y="{{ $table->position_y ?? 50 }}"
                 style="position: absolute; 
                        left: {{ $table->position_x ?? 50 }}px; 
                        top: {{ $table->position_y ?? 50 }}px;
                        width: 80px;
                        height: 80px;
                        background: {{ $table->status === 'LIBRE' ? '#28a745' : ($table->status === 'OCUPADA' ? '#ffc107' : '#6c757d') }};
                        border: 2px solid #000;
                        border-radius: 8px;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        cursor: move;
                        color: white;
                        font-weight: bold;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                        z-index: 10;
                        user-select: none;">
                <div style="font-size: 12px;">{{ $table->number }}</div>
                <div style="font-size: 10px;">{{ $table->capacity }}p</div>
                <div style="font-size: 9px; margin-top: 2px;">{{ $table->status }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@else
<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> Selecciona un sector para ver y editar su layout.
</div>
@endif

@push('styles')
<style>
.table-item {
    transition: transform 0.1s;
}
.table-item:hover {
    transform: scale(1.05);
    z-index: 20 !important;
}
.table-item.dragging {
    opacity: 0.7;
    z-index: 1000 !important;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
<script>
let editMode = true; // Modo edición activado por defecto
let draggedElement = null;

function toggleEditMode() {
    editMode = !editMode;
    const text = document.getElementById('editModeText');
    text.textContent = editMode ? 'Modo Visualización' : 'Modo Edición';
    
    const tables = document.querySelectorAll('.table-item');
    tables.forEach(table => {
        if (editMode) {
            table.style.cursor = 'move';
        } else {
            table.style.cursor = 'pointer';
        }
    });
    
    if (editMode) {
        initDragAndDrop();
    } else {
        destroyDragAndDrop();
    }
}

function initDragAndDrop() {
    interact('.table-item').draggable({
        listeners: {
            start(event) {
                event.target.classList.add('dragging');
                draggedElement = event.target;
            },
            move(event) {
                const target = event.target;
                const x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
                const y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;
                
                target.style.transform = `translate(${x}px, ${y}px)`;
                target.setAttribute('data-x', x);
                target.setAttribute('data-y', y);
            },
            end(event) {
                event.target.classList.remove('dragging');
            }
        },
        modifiers: [
            interact.modifiers.restrictRect({
                restriction: 'parent',
                endOnly: true
            })
        ],
        inertia: false
    });
}

function destroyDragAndDrop() {
    interact('.table-item').unset();
}

function loadSector(sectorId) {
    if (sectorId) {
        window.location.href = `{{ route('tables.layout') }}/${sectorId}`;
    } else {
        window.location.href = '{{ route('tables.layout') }}';
    }
}

function saveLayout() {
    if (!{{ $selectedSector ? 'true' : 'false' }}) {
        alert('Debes seleccionar un sector primero');
        return;
    }
    
    const tables = [];
    const canvas = document.getElementById('layoutCanvas');
    
    document.querySelectorAll('.table-item').forEach(item => {
        const tableId = item.getAttribute('data-table-id');
        
        // Obtener posición inicial desde el atributo style
        const initialLeft = parseFloat(item.style.left) || parseFloat(item.getAttribute('data-initial-x')) || 0;
        const initialTop = parseFloat(item.style.top) || parseFloat(item.getAttribute('data-initial-y')) || 0;
        
        // Obtener offset del drag (data-x y data-y)
        const offsetX = parseFloat(item.getAttribute('data-x')) || 0;
        const offsetY = parseFloat(item.getAttribute('data-y')) || 0;
        
        // Calcular posición final
        const positionX = Math.max(0, Math.round(initialLeft + offsetX));
        const positionY = Math.max(0, Math.round(initialTop + offsetY));
        
        tables.push({
            id: tableId,
            position_x: positionX,
            position_y: positionY
        });
    });
    
    fetch('{{ route('tables.update-layout') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            sector_id: {{ $selectedSector->id ?? 'null' }},
            tables: tables
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Layout guardado exitosamente');
            // Actualizar posiciones absolutas y resetear transformaciones
            document.querySelectorAll('.table-item').forEach(item => {
                const offsetX = parseFloat(item.getAttribute('data-x')) || 0;
                const offsetY = parseFloat(item.getAttribute('data-y')) || 0;
                const currentLeft = parseFloat(item.style.left) || 0;
                const currentTop = parseFloat(item.style.top) || 0;
                
                // Nueva posición absoluta
                const newLeft = currentLeft + offsetX;
                const newTop = currentTop + offsetY;
                
                item.style.left = newLeft + 'px';
                item.style.top = newTop + 'px';
                item.style.transform = '';
                item.setAttribute('data-initial-x', newLeft);
                item.setAttribute('data-initial-y', newTop);
                item.removeAttribute('data-x');
                item.removeAttribute('data-y');
            });
            
            // Recargar la página después de un breve delay para mostrar los cambios
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            alert('Error al guardar: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al guardar el layout');
    });
}

// Inicializar drag and drop si hay mesas
document.addEventListener('DOMContentLoaded', function() {
    const tables = document.querySelectorAll('.table-item');
    
    if (tables.length > 0) {
        // Activar modo edición por defecto si hay mesas
        editMode = true;
        document.getElementById('editModeText').textContent = 'Modo Visualización';
        initDragAndDrop();
    }
    
    // Hacer las mesas clickeables para ver detalles (solo en modo visualización)
    tables.forEach(item => {
        item.addEventListener('click', function(e) {
            if (!editMode && !this.classList.contains('dragging')) {
                const tableId = this.getAttribute('data-table-id');
                window.location.href = `{{ route('orders.create') }}/${tableId}`;
            }
        });
    });
});
</script>
@endpush
@endsection

